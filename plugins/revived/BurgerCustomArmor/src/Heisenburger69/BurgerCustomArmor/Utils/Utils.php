<?php

namespace Heisenburger69\BurgerCustomArmor\Utils;

use GdImage;
use pocketmine\item\Item;
use pocketmine\entity\Skin;
use pocketmine\world\World;
use pocketmine\player\Player;
use UnsolicitedDev\EssentialsSD\api\ImageAPI;
use Heisenburger69\BurgerCustomArmor\BurgerCustomArmor;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldHelmet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronHelmet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Chain\ChainBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Chain\ChainHelmet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldLeggings;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronLeggings;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherCap;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Chain\ChainLeggings;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldChestplate;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronChestplate;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Diamond\DiamondBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherPants;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherTunic;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Chain\ChainChestplate;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Diamond\DiamondHelmet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Diamond\DiamondLeggings;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Diamond\DiamondChestplate;

class Utils
{
    /**
     * @param World $level
     * @return bool
     */
    public static function checkProtectionLevel(World $level): bool
    {
        $blacklist = BurgerCustomArmor::getInstance()->getConfig()->get("enable-world-blacklist");
        $whitelist = BurgerCustomArmor::getInstance()->getConfig()->get("enable-world-whitelist");
        $levelName = $level->getFolderName();

        if ($blacklist === $whitelist) {
            return true;
        }

        switch (true) {
            case $blacklist:
                $disallowedWorlds = BurgerCustomArmor::getInstance()->getConfig()->get("blacklisted-worlds");
                if (!is_array($disallowedWorlds)) return false;
                if (in_array($levelName, $disallowedWorlds)) return false;
                return true;
            case $whitelist:
                $allowedWorlds = BurgerCustomArmor::getInstance()->getConfig()->get("whitelisted-worlds");
                if (!is_array($allowedWorlds)) return false;
                if (in_array($levelName, $allowedWorlds)) return true;
                return false;
        }
        return false;
    }

    public static function isHelmet(Item $item): bool
    {
        if (
            $item instanceof DiamondHelmet ||
            $item instanceof GoldHelmet ||
            $item instanceof IronHelmet ||
            $item instanceof ChainHelmet ||
            $item instanceof LeatherCap
        ) {
            return true;
        }
        return false;
    }

    public static function isChestplate(Item $item): bool
    {
        if (
            $item instanceof DiamondChestplate ||
            $item instanceof IronChestplate ||
            $item instanceof GoldChestplate ||
            $item instanceof ChainChestplate ||
            $item instanceof LeatherTunic
        ) {
            return true;
        }
        return false;
    }

    public static function isLeggings(Item $item): bool
    {
        if (
            $item instanceof DiamondLeggings ||
            $item instanceof GoldLeggings ||
            $item instanceof IronLeggings ||
            $item instanceof ChainLeggings ||
            $item instanceof LeatherPants
        ) {
            return true;
        }
        return false;
    }

    public static function isBoots(Item $item): bool
    {
        if (
            $item instanceof DiamondBoots ||
            $item instanceof GoldBoots ||
            $item instanceof IronBoots ||
            $item instanceof ChainBoots ||
            $item instanceof LeatherBoots
        ) {
            return true;
        }
        return false;
    }
}
