<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use pocketmine\player\Player;
use UnsolicitedDev\EssentialsSD\api\SkinAPI;
use Heisenburger69\BurgerCustomArmor\BurgerCustomArmor;

class CapeAbility extends TogglableAbility
{
    public function __construct(
        private string $file
    ) {
    }

    public function on(Player $player): void
    {
        SkinAPI::setSkin($player, null, null, SkinAPI::createCapeData(BurgerCustomArmor::getInstance()->getDataFolder() . $this->file));
    }

    public function off(Player $player): void
    {
        SkinAPI::setSkin($player, null, null, '');
    }
}