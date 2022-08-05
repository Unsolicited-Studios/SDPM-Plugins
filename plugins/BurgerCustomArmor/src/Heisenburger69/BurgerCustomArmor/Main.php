<?php

declare(strict_types=1);

namespace Heisenburger69\BurgerCustomArmor;

use pocketmine\color\Color;
use pocketmine\utils\Config;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\utils\TextFormat as C;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\LegacyStringToItemParser;
use Heisenburger69\BurgerCustomArmor\Abilities\AbilityUtils;
use Heisenburger69\BurgerCustomArmor\ArmorSets\ArmorSetUtils;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronBoots;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Gold\GoldHelmet;
use Heisenburger69\BurgerCustomArmor\Pocketmine\Iron\IronHelmet;
use Heisenburger69\BurgerCustomArmor\Commands\CustomArmorCommand;
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

class Main extends PluginBase
{
    public const PREFIX = C::BOLD . C::AQUA . "Burger" . C::LIGHT_PURPLE . "CustomArmor" . "> " . C::RESET;

    public static Main $instance;

    private Config $craftingRecipes;
    private Config $armorSets;

    public array $customSets;
    public array $using;

    public const FAKE_ENCH_ID = -1;

    public function onEnable(): void
    {
        self::$instance = $this;

        $this->saveDefaultConfig();
        $this->saveResource("armorsets.yml");
        $this->saveResource("recipes.yml");
        $this->saveResource("FireCape.png");
        $this->armorSets = new Config($this->getDataFolder() . "armorsets.yml");
        $this->craftingRecipes = new Config($this->getDataFolder() . "recipes.yml");

        EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("Glow", 1, ItemFlags::ALL, ItemFlags::NONE, 1));

        $this->registerCustomItems();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->registerArmorSets();
        $this->registerRecipes();
        $this->getServer()->getCommandMap()->register("BurgerCustomArmor", new CustomArmorCommand($this));
    }

    public function registerArmorSets(): void
    {
        /** 
         * @var string $name
         * @var array $properties
         * */
        foreach ($this->armorSets->getAll() as $name => $properties) {
            $this->registerArmorSet($name, $properties);
        }
    }

    /**
     * @param string $name
     * @param array $properties
     */
    public function registerArmorSet(string $name, array $properties): void
    {
        $tier = ArmorSetUtils::getTierFromName($properties["tier"]);
        if (!is_int($tier)) {
            return;
        }

        $color = new Color(0, 0, 0);
        if (isset($properties["color"])) {
            $color = new Color($properties["color"]["r"], $properties["color"]["g"], $properties["color"]["b"]);
        }

        $abilities = [];
        if (is_array($properties["abilities"]) && count($properties["abilities"]) > 0) {
            foreach ($properties["abilities"] as $ability => $value) {
                if ($ability === "Effect") {
                    $abilities = array_merge($abilities, AbilityUtils::getEffectAbilities($ability, $value));
                    continue;
                }
                if (($armorAbility = AbilityUtils::getAbility($ability, $value)) !== null) {
                    $abilities[] = $armorAbility;
                }
            }
        }

        $this->customSets[$name] = new CustomArmorSet(
            $name,
            $tier,
            $properties["glint"],
            $abilities,
            $color,
            $properties["strength"],
            isset($properties["durability"]) ? $properties["durability"] : [],
            $properties["name"],
            $properties["lore"],
            $properties["setbonuslore"],
            isset($properties["equipped-commands"]) ? $properties["equipped-commands"] : [],
            isset($properties["unequipped-commands"]) ? $properties["unequipped-commands"] : [],
            isset($properties["equipped-messages"]) ? $properties["equipped-messages"] : [],
            isset($properties["unequipped-messages"]) ? $properties["unequipped-messages"] : [],
        );

        $this->using[$name] = [];
    }

    private function registerCustomItems(): void
    {
        $items = [
            new LeatherCap(),
            new LeatherTunic(),
            new LeatherPants(),
            new LeatherBoots(),

            new ChainHelmet(),
            new ChainChestplate(),
            new ChainLeggings(),
            new ChainBoots(),

            new GoldHelmet(),
            new GoldChestplate(),
            new GoldLeggings(),
            new GoldBoots(),

            new IronHelmet(),
            new IronChestplate(),
            new IronLeggings(),
            new IronBoots(),

            new DiamondHelmet(),
            new DiamondChestplate(),
            new DiamondLeggings(),
            new DiamondBoots(),
        ];
        foreach ($items as $item) {
            ItemFactory::getInstance()->register($item, true);
        }
    }

    /**
     * Definitely not stolen from PiggyBackpacks :)
     * thx pig <3
     */
    private function registerRecipes(): void
    {
        foreach ($this->craftingRecipes->getAll() as $name => $recipeData) {
            $customArmor = explode("-", (string)$name);
            $setName = $customArmor[0];
            $setPiece = $customArmor[1];

            $item = LegacyStringToItemParser::getInstance()->parse($setPiece);
            $item->getNamedTag()->setTag("burgercustomarmor", new StringTag($setName));
            $item->setCustomName(C::RESET . C::BOLD . $setName . C::RESET . " " . $item->getName());

            $requiredItems = [];
            /** 
             * @var string $materialSymbol 
             * @var array $materialData
             */
            foreach ($recipeData["materials"] as $materialSymbol => $materialData) {
                $requiredItems[$materialSymbol] = ItemFactory::getInstance()->get((int)$materialData["id"], (int)$materialData["meta"], (int)$materialData["count"]);
            }
            if (is_array($recipeData["shape"])) {
                $this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe($recipeData["shape"], $requiredItems, [$item]));
            }
        }
        //$this->getServer()->getCraftingManager()->buildCraftingDataCache();
    }
}
