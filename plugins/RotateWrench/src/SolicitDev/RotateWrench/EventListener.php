<?php

/**
 *          █▀ █▀█ █░░ █ █▀▀ █ ▀█▀
 *          ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░
 * 
 * █▀▄ █▀▀ █░█ █▀▀ █░░ █▀█ █▀█ █▀▄▀█ █▀▀ █▄░█ ▀█▀
 * █▄▀ ██▄ ▀▄▀ ██▄ █▄▄ █▄█ █▀▀ █░▀░█ ██▄ █░▀█ ░█░
 *    https://github.com/Solicit-Development
 * 
 *    Copyright 2022 Solicit-Development
 *    Licensed under the Apache License, Version 2.0 (the 'License');
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 * 
 *        http://www.apache.org/licenses/LICENSE-2.0
 * 
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an 'AS IS' BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 * 
 */

declare(strict_types=1);

namespace SolicitDev\RotateWrench;

use pocketmine\math\Facing;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

class EventListener implements Listener
{
    /**
     * @priority MONITOR
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$event->isCancelled() && $event->getItem()->getNamedTag()->getString('RotateWrench', '') === 'Wrench') {
            RotateWrench::rotateBlock($player, $event->getBlock(), Facing::opposite($player->getHorizontalFacing()));
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        if ($event->getItem()->getNamedTag()->getString('RotateWrench', '') === 'Wrench') {
            $event->cancel();
        }
    }
}
