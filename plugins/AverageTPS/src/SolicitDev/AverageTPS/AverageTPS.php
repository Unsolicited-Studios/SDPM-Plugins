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

namespace SolicitDev\AverageTPS;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use SolicitDev\AverageTPS\task\TPSTask;
use SolicitDev\AverageTPS\command\TPSCommand;

class AverageTPS extends PluginBase
{
    public static array $types = [];

    public static array $averageTPS = [];
    public static array $lastTPS = [];

    public function onEnable(): void
    {
        $this->saveResource('config.yml');
        self::$types = $this->getConfig()->get('tps-check-timings', [
            'full', '5s', '15s', '30s', '60s', '10m', '30m', '1h', '3h', '6h', '12h'
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new TPSTask(), 20);
        $this->getServer()->getCommandMap()->register('tps', new TPSCommand($this, 'tps', 'Check the server\'s average TPS.'));
    }

    public static function isTPSAccurate(string $type): bool
    {
        if ((Server::getInstance()->getTick() / 20) >= self::convertToSeconds($type)) {
            return true;
        }
        return false;
    }

    public static function convertToSeconds(string $type): int
    {
        $unit = self::getTypeUnit($type);
        $time = self::removeTypeUnit($type);
        return match ($unit) {
            's' => $time,
            'm' => $time * 60,
            'h' => $time * 3600,
            default => 0
        };
    }

    public static function getTypeUnit(string $type): string
    {
        return preg_replace('/[0-9]+/', '', $type) ?? '';
    }

    public static function removeTypeUnit(string $type): int
    {
        return (int) str_replace(['s', 'm', 'h'], '', $type);
    }

    public static function addValueToAverage(float $oldValue, float $toAdd, int $newSize): float
    {
        return $oldValue + ($toAdd - $oldValue) / $newSize;
    }
}
