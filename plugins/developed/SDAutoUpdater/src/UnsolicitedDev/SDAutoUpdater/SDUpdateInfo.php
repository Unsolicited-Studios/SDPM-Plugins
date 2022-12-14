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

namespace UnsolicitedDev\SDAutoUpdater;

class SDUpdateInfo
{
    public static array $latestRelease;
    public static int $currentVersion;

    public static function getPluginNames(string $folder): array
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
}
