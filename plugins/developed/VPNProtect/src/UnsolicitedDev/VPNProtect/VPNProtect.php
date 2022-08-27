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

namespace UnsolicitedDev\VPNProtect;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use UnsolicitedDev\VPNProtect\EventListener;

class VPNProtect extends PluginBase
{
    use SingletonTrait;

    public function onEnable(): void
    {
        self::setInstance($this);

        $this->saveDefaultConfig();
        if (!$this->runChecks()) {
            return;
        }

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function getEnabledAPIs(): array
    {
        $enabled = [];
        foreach ($this->getConfig()->get('checks', []) as $api => $data) {
            if ($data['enabled']) {
                $enabled[] = $api;
            }
        }
        return $enabled;
    }

    private function runChecks(): bool
    {
        $minimumAPIs = $this->getConfig()->get('minimum-checks', 2) + 2;
        if (count($this->getEnabledAPIs()) <= $minimumAPIs) {
            $this->getLogger()->warning('Not enough APIs enabled to run checks! Please enable more than ' . $minimumAPIs . ' APIs.');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return false;
        }
        return true;
    }
}
