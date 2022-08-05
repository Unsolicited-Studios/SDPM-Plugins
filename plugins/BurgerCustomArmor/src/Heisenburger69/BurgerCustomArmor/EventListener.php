<?php

namespace Heisenburger69\BurgerCustomArmor;

use pocketmine\item\Armor;
use pocketmine\nbt\tag\Tag;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use AGTHARN\MagicSync\MagicSync;
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
    /**
     * @var Main
     */
    private $plugin;

    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
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
                if ($playerName !== $event->getPlayer()->getName()) continue;
                unset(Main::$instance->using[$setName][$playerName]);
            }
        }
        //TODO: Do I call CustomSetUnequippedEvent here? Seems kinda redundant to do so once the player has quit.
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDefensiveAbility(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        if (!$player instanceof Player || !$damager instanceof Player) {
            return;
        }
        if (($nbt = $player->getArmorInventory()->getHelmet()->getNamedTag()->getTag("burgercustomarmor")) === null) {
            return;
        }
        $setName = $nbt->getValue();
        if (!is_string($setName)) {
            return;
        }
        if (!EquipmentUtils::canUseSet($player, $setName)) {
            return;
        }
        $armorSet = $this->plugin->customSets[$setName];
        if (!$armorSet instanceof CustomArmorSet) {
            return;
        }
        foreach ($armorSet->getAbilities() as $ability) {
            if (!Utils::checkProtectionLevel($player->getWorld())) {
                return;
            }
            if ($ability instanceof DefensiveAbility && $ability->canActivate($damager)) {
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
        if (!$player instanceof Player) return;
        if ($event->isCancelled()) return;
        $items = $player->getArmorInventory()->getContents();
        $totalP = 0;
        foreach ($items as $item) {
            $itemP = $item->getDefensePoints();
            if (($nbt = $item->getNamedTag()->getTag("burgercustomarmor")) !== null) {
                $armorSet = $this->plugin->customSets[$nbt->getValue()];
                if (Utils::isHelmet($item)) {
                    $itemP = $armorSet->getHelmetDefensePoints();
                } elseif (Utils::isChestplate($item)) {
                    $itemP = $armorSet->getChestplateDefensePoints();
                } elseif (Utils::isLeggings($item)) {
                    $itemP = $armorSet->getLeggingsDefensePoints();
                } elseif (Utils::isBoots($item)) {
                    $itemP = $armorSet->getBootsDefensePoints();
                }
            }
            $totalP += $itemP;
        }
        $event->setModifier(-$event->getBaseDamage() * $totalP * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onOffensiveAbility(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        if (!$player instanceof Player || !$damager instanceof Player) {
            return;
        }
        if (($nbt = $damager->getArmorInventory()->getHelmet()->getNamedTag()->getTag("burgercustomarmor")) === null) {
            return;
        }
        $setName = $nbt->getValue();
        if (!is_string($setName)) {
            return;
        }
        if (!EquipmentUtils::canUseSet($damager, $setName)) {
            return;
        }
        $armorSet = $this->plugin->customSets[$setName];
        if (!$armorSet instanceof CustomArmorSet) {
            return;
        }
        foreach ($armorSet->getAbilities() as $ability) {
            if (!Utils::checkProtectionLevel($player->getWorld())) {
                return;
            }
            if ($ability instanceof OffensiveAbility && $ability->canActivate($damager)) {
                $ability->activate($event);
            }
        }
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $player->getArmorInventory();

        $sourceItem = $event->getItem();
        $index = match ($sourceItem->getId()) {
            ItemIds::LEATHER_BOOTS, ItemIds::GOLD_BOOTS, ItemIds::IRON_BOOTS, ItemIds::CHAIN_BOOTS, ItemIds::DIAMOND_BOOTS => ArmorInventory::SLOT_FEET,
            ItemIds::LEATHER_LEGGINGS, ItemIds::GOLD_LEGGINGS, ItemIds::IRON_LEGGINGS, ItemIds::CHAIN_LEGGINGS, ItemIds::DIAMOND_LEGGINGS => ArmorInventory::SLOT_LEGS,
            ItemIds::LEATHER_CHESTPLATE, ItemIds::GOLD_CHESTPLATE, ItemIds::IRON_CHESTPLATE, ItemIds::CHAIN_CHESTPLATE, ItemIds::DIAMOND_CHESTPLATE => ArmorInventory::SLOT_CHEST,
            ItemIds::LEATHER_HELMET, ItemIds::GOLD_HELMET, ItemIds::IRON_HELMET, ItemIds::CHAIN_HELMET, ItemIds::DIAMOND_HELMET => ArmorInventory::SLOT_HEAD,
            default => -1
        };
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
        if ($nbt === null || ($oldNbt !== null && $nbt->getValue() === $oldNbt->getValue())) {
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
                    if ($nbt === null || ($oldNbt !== null && $nbt->getValue() === $oldNbt->getValue())) {
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
            if (($nbt = $output->getNamedTag()->getTag("burgercustomarmor")) === null) continue;
            $craftingSet = $output;
            break;
        }
        $player = $event->getPlayer();
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

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $craftingSet, $armorSet): void {
            if ($player->getInventory()->contains($craftingSet)) $player->getInventory()->removeItem($craftingSet);
            else $player->getCursorInventory()->removeItem($craftingSet);
            if (Utils::isHelmet($craftingSet)) {
                $player->getInventory()->addItem($armorSet->getHelmet());
            } elseif (Utils::isChestplate($craftingSet)) {
                $player->getInventory()->addItem($armorSet->getChestplate());
            } elseif (Utils::isLeggings($craftingSet)) {
                $player->getInventory()->addItem($armorSet->getLeggings());
            } elseif (Utils::isBoots($craftingSet)) {
                $player->getInventory()->addItem($armorSet->getBoots());
            }
        }), 1);
    }
}
