<?php

namespace Heisenburger69\BurgerCustomArmor\Abilities\Reactive\Offensive;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DamageAmplificationAbility extends OffensiveAbility
{
    public function __construct(
        private float $negation
    ) {
    }

    public function canActivate(Player $damager): bool
    {
        return true;
    }

    public function activate(EntityDamageByEntityEvent $event): void
    {
        $baseDmg = $event->getBaseDamage() + ($event->getBaseDamage() * $this->negation);
        if ($baseDmg < 0) $baseDmg = 0;
        $event->setBaseDamage($baseDmg);
    }
}
