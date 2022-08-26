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

namespace UnsolicitedDev\LocationAPI;

use UnsolicitedDev\LocationAPI\util\Util;
use UnsolicitedDev\LocationAPI\util\Cache;
use pocketmine\utils\InternetRequestResult;
use UnsolicitedDev\LocationAPI\util\Algorithm;
use UnsolicitedDev\LocationAPI\parser\type\VPNParser;

class VPNAPI
{
    public static Cache $tempCache;
    public static Cache $timingCache;
    public static Cache $detectionCount;

    private static bool $isInit = false;

    public static function init(): bool
    {
        if (!self::$isInit) {
            self::$tempCache = new Cache();
            self::$timingCache = new Cache();
            self::$detectionCount = new Cache();

            self::$isInit = true;
            return true;
        }
        return false;
    }

    /**
     * You are recommended to run this on a separate thread! 
     */
    public static function getNormalResults(string $ip, array $options = []): array
    {
        $results = [];
        foreach (VPNParser::parseMapping($ip, $options['config'] ?? self::getDefaults()) as $key => $data) {
            if (!Util::isIncluded($key, $options)) {
                continue;
            }

            $internetResult = Util::processRequest($data, function (\CurlHandle $ch) use ($ip, $key) {
                self::$tempCache->set($ip, curl_getinfo($ch, CURLINFO_TOTAL_TIME_T));
                self::$timingCache->set($key, curl_getinfo($ch, CURLINFO_TOTAL_TIME_T));
            });

            if (!$internetResult instanceof InternetRequestResult) {
                $results[$key] = ['Request error', 0];
                continue;
            }

            $parsedResult = VPNParser::parseResult(json_decode($internetResult->getBody(), true) ?? $internetResult->getBody(), $ip);
            // NOTE: do not remove this strict check
            if ($parsedResult === true) {
                self::$detectionCount->set($key, (self::$detectionCount->get($key) ?? 0) + 1);
            }

            $results[$key] = [
                $parsedResult,
                self::$tempCache->get($ip) ?? 0
            ];
        }
        self::$tempCache->remove($ip);
        return $results;
    }

    /**
     * You are recommended to run this on a separate thread! 
     * This will also not return all results!
     */
    public static function getSmartResults(string $ip, array $options = []): array
    {
        if (!empty(self::$timingCache->getAll())) {
            return self::getNormalResults($ip, array_merge($options, [
                'include' => Algorithm::getIncludes($options['minimum-checks'] ?? 2)
            ]));
        }
        return self::getNormalResults($ip, $options);
    }

    public static function getDefaults(): array
    {
        return [
            'api2.key' => '',
            'api4.key' => '',
            'api5.key' => '',
            'api7.key' => '',
            'api7.mobile' => true,
            'api7.fast' => false,
            'api7.strictness' => 0,
            'api7.lighter_penalties' => true,
            'api8.key' => '',
            'api9.key' => '',
            'api10.key' => '',
            'api11.key' => ''
        ];
    }
}
