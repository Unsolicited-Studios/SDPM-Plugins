<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use Heisenburger69\BurgerCustomArmor\Main;
use pocketmine\player\Player;

class PermissionAbility extends TogglableAbility
{
    private string$permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function on(Player $player): void
    {
        $player->addAttachment(Main::$instance, $this->permission, true);
    }

    public function off(Player $player): void
    {
        $player->addAttachment(Main::$instance, $this->permission, false);
    }
}