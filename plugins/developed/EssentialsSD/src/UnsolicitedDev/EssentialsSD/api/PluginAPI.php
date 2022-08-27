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

namespace UnsolicitedDev\EssentialsSD\api;

use pocketmine\Server;

class PluginAPI
{
    public static function getAllPluginNames(string $folder): array
    {
        $pluginNames = [];
        foreach (new \DirectoryIterator($folder) as $file) {
            if (
                $file->isDot() || !$file->isFile() || $file->getExtension() !== 'phar' ||
                !file_exists($pluginYaml = 'phar://' . $file->getPathname() . '/plugin.yml') ||
                !($yamlContents = file_get_contents($pluginYaml)) ||
                !is_array($data = yaml_parse($yamlContents)) ||
                !isset($data['name'])
            ) {
                continue;
            }

            $pluginNames[$data['name']] = $file->getPathname();
        }
        return $pluginNames;
    }

    public static function getLatestCrashCauser(bool $modifyChangeTime = false): ?string
    {
        $fileTiming = [];
        foreach (glob(Server::getInstance()->getDataPath() . 'crashdumps/*.log') as $filePath) {
            $fileTiming[$filePath] = !filectime($filePath) ? 0 : filectime($filePath);
        }

        $pluginName = null;
        $latestFile = array_search(max($fileTiming), $fileTiming);
        foreach (file($latestFile) as $line) {
            $words = explode(' ', $line);
            if (count($words) === 2 && $words[0] === 'BAD PLUGIN:') {
                $pluginName = $words[1];
                break;
            }
        }
        !$modifyChangeTime ?: touch($latestFile);

        return $pluginName;
    }

    public static function getAllCrashCausers(): array
    {
        $pluginNames = [];
        foreach (glob(Server::getInstance()->getDataPath() . 'crashdumps/*.log') as $filePath) {
            foreach (file($filePath) as $line) {
                $words = explode(' ', $line);
                if (count($words) === 2 && $words[0] === 'BAD PLUGIN:') {
                    $pluginNames[] = $words[1];
                    continue;
                }
            }
        }
        return $pluginNames;
    }
}
