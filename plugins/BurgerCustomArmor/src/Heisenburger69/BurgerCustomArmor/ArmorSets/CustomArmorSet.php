<?php

namespace Heisenburger69\BurgerCustomArmor\ArmorSets;

use pocketmine\item\Item;
use pocketmine\color\Color;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat as C;
use Pushkar\MagicCore\Main as MagicCore;
use Heisenburger69\BurgerCustomArmor\Main;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use Heisenburger69\BurgerCustomArmor\Abilities\ArmorAbility;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherCap;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherPants;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Leather\LeatherTunic;

class CustomArmorSet
{
    public const TIER_DIAMOND = 5;
    public const TIER_IRON = 4;
    public const TIER_GOLD = 3;
    public const TIER_CHAIN = 2;
    public const TIER_LEATHER = 1;

    private string $name;
    private int $tier;
    private bool $glint;
    /** @var ArmorAbility[] */
    private array $abilities;
    private Color $color;
    private array $strength;
    private array $names;
    private array $lores;
    private array $setBonusLore;
    public array $durabilities;
    private array $equippedCommands;
    private array $unequippedCommands;
    private array $equippedMessages;
    private array $unequippedMessages;

    private EnchantmentInstance $fakeEnchant;

    /**
     * CustomArmorSet constructor.
     * @param string $name
     * @param int $tier
     * @param bool $glint
     * @param array $abilities
     * @param Color $color
     * @param array $strength
     * @param array $durabilities
     * @param array $names
     * @param array $lores
     * @param array $setBonusLore
     */
    public function __construct(string $name, int $tier, bool $glint, array $abilities, Color $color, array $strength, array $durabilities, array $names, array $lores, array $setBonusLore, array $equippedCommands = [], array $unequippedCommands = [], array $equippedMessages = [], array $unequippedMessages = [])
    {
        $this->name = $name;
        $this->tier = $tier;
        $this->glint = $glint;
        $this->abilities = $abilities;
        $this->color = $color;
        $this->strength = $strength;
        $this->durabilities = $durabilities;
        $this->names = $names;
        $this->lores = $lores;
        $this->setBonusLore = $setBonusLore;
        $this->equippedCommands = $equippedCommands;
        $this->unequippedCommands = $unequippedCommands;
        $this->equippedMessages = $equippedMessages;
        $this->unequippedMessages = $unequippedMessages;

        /** @phpstan-ignore-next-line */
        $this->fakeEnchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(Main::FAKE_ENCH_ID));
    }

    /**
     * @return Item[]
     */
    public function getSetPieces(): array
    {
        $pieces = [
            $this->getHelmet(),
            $this->getChestplate(),
            $this->getLeggings(),
            $this->getBoots()
        ];
        return $pieces;
    }

    public function getHelmet(): Item
    {
        $item = ArmorSetUtils::getHelmetFromTier($this->tier);
        $item->setCustomName(C::RESET . C::colorize($this->names["helmet"]));

        $item = $this->setNecessities($item);

        $lore = ArmorSetUtils::getHelmetLore($this->lores, $this->setBonusLore);
        $item->setLore($lore);

        return $item;
    }

    public function getChestplate(): Item
    {
        $item = ArmorSetUtils::getChestplateFromTier($this->tier);
        $item->setCustomName(C::RESET . C::colorize($this->names["chestplate"]));

        $item = $this->setNecessities($item);

        $lore = ArmorSetUtils::getChestplateLore($this->lores, $this->setBonusLore);
        $item->setLore($lore);

        return $item;
    }

    public function getLeggings(): Item
    {
        $item = ArmorSetUtils::getLeggingsFromTier($this->tier);
        $item->setCustomName(C::RESET . C::colorize($this->names["leggings"]));

        $item = $this->setNecessities($item);

        $lore = ArmorSetUtils::getLeggingsLore($this->lores, $this->setBonusLore);
        $item->setLore($lore);

        return $item;
    }

    public function getBoots(): Item
    {
        $item = ArmorSetUtils::getBootsFromTier($this->tier);
        $item->setCustomName(C::RESET . C::colorize($this->names["boots"]));

        $item = $this->setNecessities($item);

        $lore = ArmorSetUtils::getBootsLore($this->lores, $this->setBonusLore);
        $item->setLore($lore);

        return $item;
    }

    private function setNecessities(Item $item): Item
    {
        if ($this->glint) {
            $item->addEnchantment($this->fakeEnchant);
        }
        if (
            $item instanceof LeatherCap ||
            $item instanceof LeatherTunic ||
            $item instanceof LeatherPants ||
            $item instanceof LeatherBoots
        ) {
            $item->setCustomColor($this->color);
        }
        
        $item->getNamedTag()->setTag("burgercustomarmor", new StringTag($this->name));
        return $item;
    }

    public function getArmorDefensePoints(): float
    {
        return $this->getHelmetDefensePoints() +
            $this->getChestplateDefensePoints() +
            $this->getLeggingsDefensePoints() +
            $this->getBootsDefensePoints();
    }

    public function getHelmetDefensePoints(): float
    {
        $itemPoints = ArmorSetUtils::getHelmetFromTier($this->tier)->getDefensePoints();
        if (isset($this->strength["helmet"])) {
            $itemPoints = $this->strength["helmet"];
        }
        return $itemPoints;
    }

    public function getChestplateDefensePoints(): float
    {
        $itemPoints = ArmorSetUtils::getChestplateFromTier($this->tier)->getDefensePoints();
        if (isset($this->strength["chestplate"])) {
            $itemPoints = $this->strength["chestplate"];
        }

        return $itemPoints;
    }

    public function getLeggingsDefensePoints(): float
    {
        $itemPoints = ArmorSetUtils::getLeggingsFromTier($this->tier)->getDefensePoints();
        if (isset($this->strength["leggings"])) {
            $itemPoints = $this->strength["leggings"];
        }

        return $itemPoints;
    }

    public function getBootsDefensePoints(): float
    {
        $itemPoints = ArmorSetUtils::getBootsFromTier($this->tier)->getDefensePoints();
        if (isset($this->strength["boots"])) {
            $itemPoints = $this->strength["boots"];
        }

        return $itemPoints;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function isGlint(): bool
    {
        return $this->glint;
    }

    /**
     * @return ArmorAbility[]
     */
    public function getAbilities(): array
    {
        return $this->abilities;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function getStrength(): array
    {
        return $this->strength;
    }

    public function getNames(): array
    {
        return $this->names;
    }

    public function getLores(): array
    {
        return $this->lores;
    }

    public function getSetBonusLore(): array
    {
        return $this->setBonusLore;
    }

    public function getEquippedCommands(): array
    {
        return $this->equippedCommands;
    }

    public function getUnequippedCommands(): array
    {
        return $this->unequippedCommands;
    }

    public function getEquippedMessages(): array
    {
        return $this->equippedMessages;
    }

    public function getUnequippedMessages(): array
    {
        return $this->unequippedMessages;
    }
}
