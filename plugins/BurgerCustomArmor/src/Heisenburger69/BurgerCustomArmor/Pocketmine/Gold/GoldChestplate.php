<?php

namespace Heisenburger69\BurgerCustomArmor\Pocketmine\Gold;

use pocketmine\item\ItemIds;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\ItemIdentifier;
use pocketmine\inventory\ArmorInventory;
use Heisenburger69\BurgerCustomArmor\Main;
use Heisenburger69\BurgerCustomArmor\Pocketmine\BurgerArmor;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;

class GoldChestplate extends BurgerArmor
{
    /** @var float */
    protected $metaFloat = 0.0;

    public function __construct(int $meta = 0)
    {
        parent::__construct(new ItemIdentifier(ItemIds::GOLDEN_CHESTPLATE, $meta), "Golden Chestplate", new ArmorTypeInfo(5, 113, ArmorInventory::SLOT_CHEST));
    }

    public function getMaxDurability(): int
    {
        if (($nbt = $this->getNamedTag()->getTag("burgercustomarmor")) !== null) {
            $setName = $nbt->getValue();
            $armorSet = Main::$instance->customSets[$setName];
            if ($armorSet instanceof CustomArmorSet) {
                return isset($armorSet->durabilities["chestplate"]) ? $armorSet->durabilities["chestplate"] : parent::getMaxDurability();
            }
        }
        return parent::getMaxDurability();
    }

    public function applyDamage(int $amount): bool
    {
        if ($this->isUnbreakable() or $this->isBroken()) {
            return false;
        }

        $amount -= $this->getUnbreakingDamageReduction($amount);
        $factor = $this->getMaxDurability() / parent::getMaxDurability();
        $this->metaFloat = ($this->metaFloat + ($amount / $factor));
        $this->setDamage(min((int)round($this->metaFloat), parent::getMaxDurability()));
        if ($this->isBroken()) {
            $this->onBroken();
        }

        return true;
    }
}