<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Togglable;

use pocketmine\player\Player;
use pocketmine\entity\effect\EffectInstance;

class EffectAbility extends TogglableAbility
{
    private EffectInstance $effect;

    public function __construct(EffectInstance $effectInstance)
    {
        $this->effect = $effectInstance;
    }

    public function on(Player $player): void
    {
        $player->getEffects()->add($this->effect);
    }

    public function off(Player $player): void
    {
        $player->getEffects()->remove($this->effect->getType());
    }
}