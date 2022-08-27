<?php

/**                                                                        **\
 *               █░█ █▄░█ █▀ █▀█ █░░ █ █▀▀ █ ▀█▀ █▀▀ █▀▄                    *
 *               █▄█ █░▀█ ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░ ██▄ █▄▀                    *
 *                                                                          *
 *                       █▀ ▀█▀ █░█ █▀▄ █ █▀█ █▀                            *
 *                       ▄█ ░█░ █▄█ █▄▀ █ █▄█ ▄█                            *
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

namespace UnsolicitedDev\NoPlayerNotify;

use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\command\defaults\SayCommand;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;

class NoPlayerNotify extends PluginBase implements Listener
{
    use SingletonTrait;

    public array $playerChatCount = [];
    public int $lastServerSayTime = 0;

    public function onEnable(): void
    {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->unregister($commandMap->getCommand('say'));
        $commandMap->register('pocketmine', new SayCommand('say'));
    }

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();

        $i = 0;
        foreach ($event->getRecipients() as $recipient) {
            if ($recipient instanceof Player) {
                $i++;
            }
        }

        if (
            $this->lastServerSayTime + 1200 < time() &&
            $this->playerChatCount[$player->getName()] !== -1 && 
            $i === 0
        ) {
            $this->playerChatCount[$player->getName()] = ($this->playerChatCount[$player->getName()] ?? 0) + 1;
            if ($this->playerChatCount[$player->getName()] >= $this->getConfig()->get('chat-threshold', 3)) {
                $event->getPlayer()->sendMessage($this->getConfig()->get('no-player-message', 'Hmmm...\n§cIt seems like there are no other players or console viewers in the server receiving your chat message. Are you chatting to yourself?'));
                $this->playerChatCount[$player->getName()] = -1;
            }
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        unset($this->playerChatCount[$event->getPlayer()->getName()]);
    }
}
