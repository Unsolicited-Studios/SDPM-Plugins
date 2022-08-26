<?php

namespace Heisenburger69\BurgerCustomArmor\Pocketmine\Leather;

use pocketmine\item\ItemTypeIds;
use pocketmine\item\ArmorTypeInfo;
use pocketmine\item\ItemIdentifier;
use pocketmine\inventory\ArmorInventory;
use Heisenburger69\BurgerCustomArmor\BurgerCustomArmor;
use Heisenburger69\BurgerCustomArmor\Pocketmine\BurgerArmor;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;

class LeatherTunic extends BurgerArmor
{
    /** @var float */
    protected $metaFloat = 0.0;

    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::LEATHER_TUNIC), "Leather Tunic", new ArmorTypeInfo(3, 81, ArmorInventory::SLOT_CHEST));
    }

    public function getMaxDurability(): int
    {
        if (($nbt = $this->getNamedTag()->getTag("burgercustomarmor")) !== null) {
            $setName = $nbt->getValue();
            $armorSet = BurgerCustomArmor::getInstance()->customSets[$setName];
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