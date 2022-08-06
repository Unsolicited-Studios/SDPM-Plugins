<?php

namespace SchdowNVIDIA\ECTUI;

use pocketmine\Server;
use pocketmine\item\Axe;
use pocketmine\item\Bow;
use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\item\Armor;
use pocketmine\item\Sword;
use pocketmine\block\Block;
use pocketmine\item\Shears;
use pocketmine\item\Shovel;
use pocketmine\item\Pickaxe;
use pocketmine\player\Player;
use pocketmine\item\FishingRod;
use pocketmine\item\FlintSteel;
use pocketmine\lang\Translatable;
use pocketmine\plugin\PluginBase;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\math\AxisAlignedBB;
use pocketmine\item\enchantment\Rarity;
use pocketmine\inventory\ArmorInventory;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\enchantment\StringToEnchantmentParser;

class Main extends PluginBase
{
    public function onEnable(): void
    {
        $this->initEnchantments();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    public function initEnchantments(): void
    {
        EnchantmentIdMap::getInstance()->register(EnchantmentIds::FORTUNE, new Enchantment("fortune", Rarity::UNCOMMON, ItemFlags::DIG, ItemFlags::NONE, 3));
        EnchantmentIdMap::getInstance()->register(EnchantmentIds::LOOTING, new Enchantment("looting", Rarity::UNCOMMON, ItemFlags::SWORD, ItemFlags::NONE, 3));
    }

    public static function formEnchants(Item $toEnchant, float $levelSub): array
    {
        if (!$toEnchant instanceof Tool && !$toEnchant instanceof Armor) {
            return [];
        }

        $enchants = self::getCompatibleEnchants($toEnchant);
        if (count($enchants) < 3) {
            for ($i = 0; $i < (3 - count($enchants)); $i++) {
                $enchants[] = $enchants[array_rand($enchants)];
            }
        }
        $arrayRand = array_rand($enchants, 3);

        $enchantNames = [
            $enchants[$arrayRand[0]]->getName(),
            $enchants[$arrayRand[1]]->getName(),
            $enchants[$arrayRand[2]]->getName()
        ];

        return [
            0 =>  [
                "name" => $enchantNames[0] instanceof Translatable ? Server::getInstance()->getLanguage()->translateString($enchantNames[0]->getText()) : $enchantNames[0],
                "rarity" => $enchants[$arrayRand[0]]->getRarity(),
                "level" => rand(1, intval($enchants[$arrayRand[0]]->getMaxLevel() * ($levelSub - 0.15))),
                "xp" => rand(intval(2 * ($levelSub + 1)), intval(6 * ($levelSub + 1)))
            ],
            1 =>  [
                "name" => $enchantNames[1] instanceof Translatable ? Server::getInstance()->getLanguage()->translateString($enchantNames[1]->getText()) : $enchantNames[1],
                "rarity" => $enchants[$arrayRand[1]]->getRarity(),
                "level" => rand(1, intval($enchants[$arrayRand[1]]->getMaxLevel() * ($levelSub - 0.10))),
                "xp" => rand(intval(6 * ($levelSub + 1)), intval(10 * ($levelSub + 1)))
            ],
            2 =>  [
                "name" => $enchantNames[2] instanceof Translatable ? Server::getInstance()->getLanguage()->translateString($enchantNames[2]->getText()) : $enchantNames[2],
                "rarity" => $enchants[$arrayRand[2]]->getRarity(),
                "level" => rand(2, intval($enchants[$arrayRand[2]]->getMaxLevel() * ($levelSub))),
                "xp" => rand(intval(10 * ($levelSub + 1)), intval(15 * ($levelSub + 1)))
            ]
        ];
    }

    public static function getCompatibleEnchants(Item $toEnchant): array
    {
        // TODO: Enchantments from other plugins (e.g. VanillaX)
        $enchants = VanillaEnchantments::getAll();

        $itemFlags = match (true) {
            $toEnchant instanceof Armor => [ItemFlags::ARMOR, self::getArmorFlags($toEnchant), ItemFlags::ALL],
            $toEnchant instanceof Sword => [ItemFlags::SWORD],
            $toEnchant instanceof Bow => [ItemFlags::BOW],
            $toEnchant instanceof Hoe => [ItemFlags::HOE],
            $toEnchant instanceof Shears => [ItemFlags::DIG, ItemFlags::SHEARS],
            $toEnchant instanceof FlintSteel => [ItemFlags::FLINT_AND_STEEL],
            $toEnchant instanceof Axe => [ItemFlags::DIG, ItemFlags::AXE],
            $toEnchant instanceof Pickaxe => [ItemFlags::DIG, ItemFlags::PICKAXE],
            $toEnchant instanceof Shovel => [ItemFlags::DIG, ItemFlags::SHOVEL],
            $toEnchant instanceof FishingRod => [ItemFlags::FISHING_ROD],
            default => [ItemFlags::ALL]
            // TODO: Carrot Stick
            // TODO: Elytra
            // TODO: Trident
        };
        $compatibleEnchants = [];

        /** @var Enchantment $enchant */
        foreach ($enchants as $enchant) {
            foreach ($itemFlags as $itemFlag) {
                if ($enchant->getPrimaryItemFlags() === $itemFlag || $enchant->getSecondaryItemFlags() === $itemFlag) {
                    $compatibleEnchants[] = $enchant;
                    continue;
                }
            }
        }
        return $compatibleEnchants;
    }

    public static function getArmorFlags(Armor $armor): int
    {
        return match ($armor->getArmorSlot()) {
            ArmorInventory::SLOT_HEAD => ItemFlags::HEAD,
            ArmorInventory::SLOT_CHEST => ItemFlags::TORSO,
            ArmorInventory::SLOT_LEGS => ItemFlags::LEGS,
            ArmorInventory::SLOT_FEET => ItemFlags::FEET
        };
    }

    public static function generateEnchants(Item $toEnchant, Block $enchantmentTable): array
    {
        $bookShelves = self::getBookshelves($enchantmentTable);
        $levelSub = match (true) {
            $bookShelves > 15 => 1.00,
            $bookShelves > 10 => 0.70,
            $bookShelves > 5 => 0.40,
            default => 0.20
        };

        return Main::formEnchants($toEnchant, $levelSub);
    }

    public static function getBookshelves(Block $enchantmentTable): int
    {
        $bx = (int) $enchantmentTable->getPosition()->getX();
        $by = (int) $enchantmentTable->getPosition()->getY();
        $bz = (int) $enchantmentTable->getPosition()->getZ();

        $aaBB = new AxisAlignedBB($bx - 2, $by - 1, $bz - 2, $bx + 2, $by + 1, $bz + 2);

        return count($enchantmentTable->getPosition()->getWorld()->getCollisionBlocks($aaBB));
    }

    public static function openEnchantUI(Player $player, Item $toEnchant, Block $enchantmentTable): void
    {
        $player->getWorld()->addSound($player->getPosition(), new AnvilFallSound());

        $enchants = self::generateEnchants($toEnchant, $enchantmentTable);
        if (count($enchants) === 0) {
            $player->sendMessage("§8(§b!§8) §7There are no enchantments available for this item!");
            return;
        }

        $form = new SimpleForm(function (Player $player, int $data = null) use ($toEnchant, $enchants) {
            if ($data === null) {
                return;
            }

            if ($toEnchant->getId() !== $player->getInventory()->getItemInHand()->getId()) {
                $player->sendMessage("§8(§b!§8) §7Are you trying to swindle me?");
                return;
            }

            switch ($data) {
                case 0:
                    if ($player->getXpManager()->getXpLevel() < $enchants[0]["xp"]) {
                        $player->sendMessage("§8(§b!§8) §7You don't have enough levels!");
                        return;
                    }

                    /** @var Enchantment $enchant */
                    $enchant = StringToEnchantmentParser::getInstance()->parse($enchants[0]["name"]);
                    if ($toEnchant->getEnchantment($enchant) instanceof EnchantmentInstance) {
                        $player->sendMessage("§8(§b!§8) §7You can't enchant the same enchantment again!");
                        return;
                    }

                    $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $enchants[0]["xp"]);
                    $level = $enchants[0]["level"];
                    if ($level <= 0) {
                        $level = 1;
                    }
                    break;
                case 1:
                    if ($player->getXpManager()->getXpLevel() < $enchants[1]["xp"]) {
                        $player->sendMessage("§8(§b!§8) §7You don't have enough levels!");
                        return;
                    }

                    /** @var Enchantment $enchant */
                    $enchant = StringToEnchantmentParser::getInstance()->parse($enchants[1]["name"]);
                    if ($toEnchant->getEnchantment($enchant) instanceof EnchantmentInstance) {
                        $player->sendMessage("§8(§b!§8) §7You can't enchant the same enchantment again!");
                        return;
                    }

                    $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $enchants[1]["xp"]);
                    $level = $enchants[1]["level"];
                    if ($level <= 0) {
                        $level = 1;
                    }
                    break;
                case 2:
                    if ($player->getXpManager()->getXpLevel() < $enchants[2]["xp"]) {
                        $player->sendMessage("§8(§b!§8) §7You don't have enough levels!");
                        return;
                    }

                    /** @var Enchantment $enchant */
                    $enchant = StringToEnchantmentParser::getInstance()->parse($enchants[2]["name"]);
                    if ($toEnchant->getEnchantment($enchant) instanceof EnchantmentInstance) {
                        $player->sendMessage("§8(§b!§8) §7You can't enchant the same enchantment again!");
                        return;
                    }

                    $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $enchants[2]["xp"]);
                    $level = $enchants[2]["level"];
                    if ($level <= 0) {
                        $level = 1;
                    }
                    break;
            }
            $toEnchant->addEnchantment(new EnchantmentInstance($enchant, (int) $level));
            
            $player->getWorld()->addSound($player->getPosition(), new AnvilUseSound());
            $player->getInventory()->setItemInHand($toEnchant);
        });

        $form->setTitle("§d§l«§r §bENCHANTMENT TABLE §d§l»§r§8 " . $toEnchant->getName());
        foreach ($enchants as $enchant) {
            $lvl = $enchant["level"];
            if ($lvl <= 0) {
                $lvl = 1;
            }
            $form->addButton("§d" . $enchant["name"] . "§e (" . $lvl . ")§r§a " . $enchant["xp"] . " LVL", 1, "https://cdn-icons-png.flaticon.com/128/167/167755.png");
        }
        $form->addButton("§l§cEXIT\n§r§8Tap to exit", 0, "textures/ui/cancel");
        $form->setContent("§bHello §e{$player->getName()}\n\n§bEnchant the current item you are holding in your hand:");

        $player->sendForm($form);
    }
}
