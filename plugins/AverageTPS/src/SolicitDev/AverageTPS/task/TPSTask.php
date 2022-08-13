<?php

/**                                                                        **\
 *                        █▀ █▀█ █░░ █ █▀▀ █ ▀█▀                            *
 *                        ▄█ █▄█ █▄▄ █ █▄▄ █ ░█░                            *
 *                                                                          *
 *            █▀▄ █▀▀ █░█ █▀▀ █░░ █▀█ █▀█ █▀▄▀█ █▀▀ █▄░█ ▀█▀                *
 *            █▄▀ ██▄ ▀▄▀ ██▄ █▄▄ █▄█ █▀▀ █░▀░█ ██▄ █░▀█ ░█░                *
 *                https://github.com/Solicit-Development                    *
 *                                                                          *
 *                  Copyright 2022 Solicit-Development                      *
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

namespace SolicitDev\AverageTPS\task;

use pocketmine\Server;
use pocketmine\scheduler\Task;
use SolicitDev\AverageTPS\AverageTPS;

class TPSTask extends Task
{
    public function onRun(): void
    {
        foreach (AverageTPS::TYPES as $type) {
            AverageTPS::$averageTPS[$type] = [
                'count' => $count = (AverageTPS::$averageTPS[$type]['count'] ?? 0) + 1,
                'value' => AverageTPS::addValueToAverage(AverageTPS::$averageTPS[$type]['value'] ?? 20, Server::getInstance()->getTicksPerSecond(), $count)
            ];

            if (
                $type === 'full' ||
                (Server::getInstance()->getTick() / 20) % AverageTPS::convertToSeconds($type) === 1
            ) {
                $this->updateLastTPS($type);
            }
        }
    }

    private function updateLastTPS(string $type): void
    {
        AverageTPS::$lastTPS[$type] = AverageTPS::$averageTPS[$type]['value'];
        unset(AverageTPS::$averageTPS[$type]);
    }
}
