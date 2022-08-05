<?php

namespace Heisenburger69\BurgerCustomArmor\Pocketmine;

use pocketmine\item\Armor;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\item\ItemUseResult;
use Heisenburger69\BurgerCustomArmor\Utils\EquipmentUtils;

class BurgerArmor extends Armor
{
    public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult
    {
        $existing = $player->getArmorInventory()->getItem($this->getArmorSlot());
        $thisCopy = clone $this;
        $new = $thisCopy->pop();

        $player->getArmorInventory()->setItem($this->getArmorSlot(), $new);
        if ($thisCopy->getCount() === 0) {
            $player->getInventory()->setItemInHand($existing);
        } else { //if the stack size was bigger than 1 (usually won't happen, but might be caused by plugins
            $player->getInventory()->setItemInHand($thisCopy);
            $player->getInventory()->addItem($existing);
        }
        EquipmentUtils::updateSetUsage($player);
        
        return ItemUseResult::SUCCESS();
    }
}
