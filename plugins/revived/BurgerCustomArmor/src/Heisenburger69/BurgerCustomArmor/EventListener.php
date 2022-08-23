<?php

namespace Heisenburger69\BurgerCustomArmor;

use pocketmine\item\Armor;
use pocketmine\nbt\tag\Tag;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\ClosureTask;
use pocketmine\inventory\ArmorInventory;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use Heisenburger69\BurgerCustomArmor\Utils\Utils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use Heisenburger69\BurgerCustomArmor\Utils\EquipmentUtils;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;
use Heisenburger69\BurgerCustomArmor\Events\CustomSetUnequippedEvent;
use Heisenburger69\BurgerCustomArmor\Abilities\Togglable\TogglableAbility;
use Heisenburger69\BurgerCustomArmor\Abilities\Reactive\Defensive\DefensiveAbility;
use Heisenburger69\BurgerCustomArmor\Abilities\Reactive\Offensive\OffensiveAbility;

class EventListener implements Listener
{
    public function __construct(
        private Main $plugin
    ) {
    }

    /**
     * @param PlayerJoinEvent $event
     * @priority HIGH
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        EquipmentUtils::updateSetUsage($event->getPlayer());
    }

    /**
     * Removes the user from the array of players using CustomSets to prevent wasting memory
     *
     * @param PlayerQuitEvent $event
     * @priority HIGH
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        foreach (Main::$instance->using as $setName => $players) {
            if (!is_array($players)) {
                continue;
            }
            foreach ($players as $playerName => $using) {
                if ($playerName === $event->getPlayer()->getName()) {
                    unset(Main::$instance->using[$setName][$playerName]);
                }
            }
        }
        //TODO: Do I call CustomSetUnequippedEvent here? Seems kinda redundant to do so once the player has quit.
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onOffensiveAbility(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        if (
            !$player instanceof Player || !$damager instanceof Player ||
            ($nbt = $damager->getArmorInventory()->getHelmet()->getNamedTag()->getTag("burgercustomarmor")) === null
        ) {
            return;
        }

        $setName = $nbt->getValue();
        if (!is_string($setName) || !EquipmentUtils::canUseSet($damager, $setName)) {
            return;
        }

        $armorSet = $this->plugin->customSets[$setName];
        if (!$armorSet instanceof CustomArmorSet) {
            return;
        }

        foreach ($armorSet->getAbilities() as $ability) {
            if (
                Utils::checkProtectionLevel($player->getWorld()) && 
                $ability instanceof OffensiveAbility && $ability->canActivate($damager)
            ) {
                $ability->activate($event);
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDefensiveAbility(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        if (
            !$player instanceof Player || !$damager instanceof Player ||
            ($nbt = $player->getArmorInventory()->getHelmet()->getNamedTag()->getTag("burgercustomarmor")) === null
        ) {
            return;
        }

        $setName = $nbt->getValue();
        if (!is_string($setName) || !EquipmentUtils::canUseSet($player, $setName)) {
            return;
        }

        $armorSet = $this->plugin->customSets[$setName];
        if (!$armorSet instanceof CustomArmorSet) {
            return;
        }

        foreach ($armorSet->getAbilities() as $ability) {
            if (
                Utils::checkProtectionLevel($player->getWorld()) &&
                $ability instanceof DefensiveAbility && $ability->canActivate($damager)
            ) {
                $ability->activate($event);
            }
        }
    }

    /**
     * Overwriting the defense points of each armor piece if they're part of a Custom Armor Set
     * Enchantment modifier not affected
     * @param EntityDamageEvent $event
     * @priority MONITOR
     */
    public function onModifier(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();
        if ($event->isCancelled() || !$player instanceof Player) {
            return;
        }

        $items = $player->getArmorInventory()->getContents();
        $totalP = 0;
        foreach ($items as $item) {
            $itemP = $item->getDefensePoints();
            if (($nbt = $item->getNamedTag()->getTag("burgercustomarmor")) !== null) {
                $armorSet = $this->plugin->customSets[$nbt->getValue()];
                $itemP = match (true) {
                    Utils::isHelmet($item) => $armorSet->getHelmetDefensePoints(),
                    Utils::isChestplate($item) => $armorSet->getChestplateDefensePoints(),
                    Utils::isLeggings($item) => $armorSet->getLeggingsDefensePoints(),
                    Utils::isBoots($item) => $armorSet->getBootsDefensePoints(),
                    default => $itemP
                };
            }
            $totalP += $itemP;
        }
        $event->setModifier(-$event->getBaseDamage() * $totalP * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $player->getArmorInventory();

        $sourceItem = $event->getItem();
        if ($sourceItem instanceof Armor) {
            $index = $sourceItem->getArmorSlot();
        } else {
            $index = -1;
        }

        $targetItem = match ($index) {
            ArmorInventory::SLOT_FEET => $inventory->getBoots(),
            ArmorInventory::SLOT_LEGS => $inventory->getLeggings(),
            ArmorInventory::SLOT_CHEST => $inventory->getChestplate(),
            ArmorInventory::SLOT_HEAD => $inventory->getHelmet(),
            default => -1
        };

        if ($index === -1 || $targetItem === -1) {
            return;
        }
        EquipmentUtils::updateSetUsage($player);

        $nbt = $sourceItem->getNamedTag()->getTag("burgercustomarmor");
        $oldNbt = $targetItem->getNamedTag()->getTag("burgercustomarmor");
        if (
            $nbt === null ||
            ($oldNbt !== null && $nbt->getValue() === $oldNbt->getValue())
        ) {
            return;
        }

        if ($inventory->getItem($index) instanceof Armor) {
            $setName = $nbt->getValue();
            if (!isset($this->plugin->using[$setName]) || !is_string($setName)) {
                return;
            }

            $fullSetWorn = false;
            if (EquipmentUtils::canUseSet($player, $setName)) {
                $fullSetWorn = true;
            }

            EquipmentUtils::removeUsingSet($player, $targetItem, $setName);

            $armorSet = $this->plugin->customSets[$setName];
            if (!$armorSet instanceof CustomArmorSet) {
                return;
            }

            if ($fullSetWorn) {
                ($event = new CustomSetUnequippedEvent($player, $armorSet))->call();
                foreach ($armorSet->getAbilities() as $ability) {
                    if ($ability instanceof TogglableAbility) {
                        $ability->off($player);
                    }
                }
            }
        }
    }

    public function onEquip(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        foreach ($transaction->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();

                $targetItem = $action->getTargetItem();
                $sourceItem = $action->getSourceItem();

                if ($inventory instanceof ArmorInventory) {
                    EquipmentUtils::updateSetUsage($player);

                    $nbt = $sourceItem->getNamedTag()->getTag("burgercustomarmor");
                    $oldNbt = $targetItem->getNamedTag()->getTag("burgercustomarmor");
                    if (
                        $nbt === null ||
                        ($oldNbt !== null && $nbt->getValue() === $oldNbt->getValue())
                    ) {
                        return;
                    }

                    if ($inventory->getItem($action->getSlot()) instanceof Armor) {
                        $setName = $nbt->getValue();
                        if (!isset($this->plugin->using[$setName]) || !is_string($setName)) {
                            return;
                        }

                        $fullSetWorn = false;
                        if (EquipmentUtils::canUseSet($player, $setName)) {
                            $fullSetWorn = true;
                        }

                        EquipmentUtils::removeUsingSet($player, $targetItem, $setName);
                        
                        $armorSet = $this->plugin->customSets[$setName];
                        if (!$armorSet instanceof CustomArmorSet) {
                            return;
                        }

                        if ($fullSetWorn) {
                            ($event = new CustomSetUnequippedEvent($player, $armorSet))->call();
                            foreach ($armorSet->getAbilities() as $ability) {
                                if ($ability instanceof TogglableAbility) {
                                    $ability->off($player);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param CraftItemEvent $event
     *
     * Lots of shitty code to support results with a custom lore and such
     */
    public function onCraft(CraftItemEvent $event): void
    {
        $outputs = $event->getOutputs();
        $craftingSet = null;
        foreach ($outputs as $output) {
            if ($output->getNamedTag()->getTag("burgercustomarmor") !== null) {
                $craftingSet = $output;
                break;
            }
        }
        
        if ($craftingSet === null) {
            return;
        }

        $tag = $craftingSet->getNamedTag()->getTag("burgercustomarmor");
        if (!$tag instanceof Tag) {
            return;
        }

        $setName = $tag->getValue();
        $armorSet = $this->plugin->customSets[$setName];
        if (!$armorSet instanceof CustomArmorSet) {
            return;
        }

        $player = $event->getPlayer();

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $craftingSet, $armorSet): void {
            if ($player->getInventory()->contains($craftingSet)) {
                $player->getInventory()->removeItem($craftingSet);
            } else {
                $player->getCursorInventory()->removeItem($craftingSet);
            }

            $player->getInventory()->addItem(match (true) {
                Utils::isHelmet($craftingSet) => $armorSet->getHelmet(),
                Utils::isChestplate($craftingSet) => $armorSet->getChestplate(),
                Utils::isLeggings($craftingSet) => $armorSet->getLeggings(),
                Utils::isBoots($craftingSet) => $armorSet->getBoots(),
                default => VanillaBlocks::AIR()->asItem()
            });
        }), 1);
    }
}
