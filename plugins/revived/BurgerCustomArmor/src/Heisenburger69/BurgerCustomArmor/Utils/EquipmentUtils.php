<?php

namespace Heisenburger69\BurgerCustomArmor\Utils;

use pocketmine\item\Item;
use pocketmine\player\Player;
use Heisenburger69\BurgerCustomArmor\BurgerCustomArmor;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;
use Heisenburger69\BurgerCustomArmor\Events\CustomSetEquippedEvent;
use Heisenburger69\BurgerCustomArmor\Abilities\Togglable\TogglableAbility;

class EquipmentUtils
{

    /**
     * @param Player $player
     * @param Item $item
     * @param string $setName
     */
    public static function removeUsingSet(Player $player, Item $item, string $setName): void
    {
        $playerName = $player->getName();
        if (!isset(BurgerCustomArmor::$instance->using[$setName][$playerName])) {
            self::initPlayer($playerName, $setName);
        }

        switch (true) {
            case Utils::isHelmet($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["helmet"] = false;
                break;
            case Utils::isChestplate($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["chestplate"] = false;
                break;
            case Utils::isLeggings($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["leggings"] = false;
                break;
            case Utils::isBoots($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["boots"] = false;
                break;
        }
    }

    /**
     * @param Player $player
     * @param Item $item
     * @param string $setName
     */
    public static function addUsingSet(Player $player, Item $item, string $setName): void
    {
        $playerName = $player->getName();
        if (!isset(BurgerCustomArmor::$instance->using[$setName][$playerName])) {
            self::initPlayer($playerName, $setName);
        }

        switch (true) {
            case Utils::isHelmet($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["helmet"] = true;
                break;
            case Utils::isChestplate($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["chestplate"] = true;
                break;
            case Utils::isLeggings($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["leggings"] = true;
                break;
            case Utils::isBoots($item):
                BurgerCustomArmor::$instance->using[$setName][$playerName]["boots"] = true;
                break;
        }
    }

    /**
     * @param string $playerName
     * @param string $setName
     */
    public static function initPlayer(string $playerName, string $setName): void
    {
        BurgerCustomArmor::$instance->using[$setName][$playerName] = [
            "helmet" => false,
            "chestplate" => false,
            "leggings" => false,
            "boots" => false
        ];
    }

    /**
     * @param Player $player
     * @param string $setName
     * @return bool
     */
    public static function canUseSet(Player $player, string $setName): bool
    {
        $playerName = $player->getName();
        if (
            isset(BurgerCustomArmor::$instance->using[$setName][$playerName]) &&
            BurgerCustomArmor::$instance->using[$setName][$playerName]["helmet"] === true &&
            BurgerCustomArmor::$instance->using[$setName][$playerName]["chestplate"] === true &&
            BurgerCustomArmor::$instance->using[$setName][$playerName]["leggings"] === true &&
            BurgerCustomArmor::$instance->using[$setName][$playerName]["boots"] === true
        ) {
            return true;
        }
        return false;
    }

    /**
     * Checks the players armor and adds the player back to the array of players using CustomSets
     *
     * @param Player $player
     */
    public static function updateSetUsage(Player $player): void
    {
        $setName = null;
        $armorSet = null;
        foreach ($player->getArmorInventory()->getContents() as $item) {
            if (($nbt = $item->getNamedTag()->getTag("burgercustomarmor")) === null) {
                continue;
            }

            $setName = $nbt->getValue();
            if (!is_string($setName) || !isset(BurgerCustomArmor::$instance->customSets[$setName])) {
                continue;
            }

            $armorSet = BurgerCustomArmor::$instance->customSets[$setName];
            self::addUsingSet($player, $item, $setName);
        }

        if (
            !is_string($setName) || !$armorSet instanceof CustomArmorSet ||
            !self::canUseSet($player, $setName)
        ) {
            return;
        }
        
        foreach ($armorSet->getAbilities() as $ability) {
            if (
                Utils::checkProtectionLevel($player->getWorld()) &&
                $ability instanceof TogglableAbility
            ) {
                $ability->on($player);
            }
        }
        (new CustomSetEquippedEvent($player, $armorSet))->call();
    }
}
