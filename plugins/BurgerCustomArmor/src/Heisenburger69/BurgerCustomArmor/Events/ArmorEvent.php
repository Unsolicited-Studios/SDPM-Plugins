<?php

namespace Heisenburger69\BurgerCustomArmor\Events;

use pocketmine\event\Event;
use pocketmine\player\Player;
use Heisenburger69\BurgerCustomArmor\ArmorSets\CustomArmorSet;

abstract class ArmorEvent extends Event
{
    public function __construct(
        private Player $player,
        private CustomArmorSet $armorSet
    ){
    }

    abstract public function call(): void;

    public function onCall(): void
    {
        parent::call();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getArmorSet(): CustomArmorSet
    {
        return $this->armorSet;
    }
}
