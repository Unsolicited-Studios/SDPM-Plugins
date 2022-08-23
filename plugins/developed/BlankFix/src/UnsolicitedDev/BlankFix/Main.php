<?php

/**                                                                        **\
 *                        █▀ █▀█ █░░ █ █▀▀ █ ▀█▀                            *
 *                        ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░                            *
 *                                                                          *
 *            █▀▄ █▀▀ █░█ █▀▀ █░░ █▀█ █▀█ █▀▄▀█ █▀▀ █▄░█ ▀█▀                *
 *            █▄▀ ██▄ ▀▄▀ ██▄ █▄▄ █▄█ █▀▀ █░▀░█ ██▄ █░▀█ ░█░                *
 *                https://github.com/Unsolicited-Studios                    *
 *                                                                          *
 *                  Copyright 2022 Unsolicited-Studios                      *
 *    Licensed under the Apache License, Version 2.0 (the 'License');       *
 *   you may not use this file except in compliance with the License.       *
 *                                                                          *
 *                You may obtain a copy of the License at                   *
 *              http://www.apache.org/licenses/LICENSE-2.0                  *
 *                                                                          *
 *  Unless required by applicable law or agreed to in writing, software     *
 *   distributed under the License is distributed on an 'AS IS' BASIS,      *
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. *
 *  See the License for the specific language governing permissions and     *
 *                    limitations under the License.                        *
 *                                                                          *
 */                                                                        //

declare(strict_types=1);

namespace UnsolicitedDev\BlankFix;

use pocketmine\event\Listener;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;

class Main extends PluginBase implements Listener
{
    private array $toAlert = [];

    public function onEnable(): void
    {
        $this->saveResource('config.yml');
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $rotation = $player->saveNBT()->getListTag('Rotation');
        if (!$rotation instanceof ListTag) {
            return;
        }

        $tags = $rotation->getValue();

        $yaw = $tags[0]->getValue();
        $pitch = $tags[1]->getValue();

        // NOTE: is_nan() will not work here
        if ($yaw == 'NAN' || $pitch == 'NAN') {
            $this->toAlert[$player->getUniqueId()->toString()] = [$yaw, $pitch];
            $player->setRotation(0.0, 0.0);
            $player->save();
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        if (isset($this->toAlert[$player->getUniqueId()->toString()])) {
            $player->sendMessage(str_replace('{player}', $player->getName(), $this->getConfig()->get('notify-message')));
            unset($this->toAlert[$player->getUniqueId()->toString()]);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        // if for whatever reason the player is still cached
        $player = $event->getPlayer();
        if (isset($this->toAlert[$player->getUniqueId()->toString()])) {
            unset($this->toAlert[$player->getUniqueId()->toString()]);
        }
    }
}
