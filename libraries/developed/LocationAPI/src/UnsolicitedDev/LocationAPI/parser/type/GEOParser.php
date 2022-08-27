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

namespace UnsolicitedDev\LocationAPI\parser\type;

use UnsolicitedDev\LocationAPI\parser\ParserBase;

class GEOParser extends ParserBase
{
    public static function parseResult(mixed $result, string $ip): mixed
    {
        return self::parseError($result) ?? $result;
    }

    public static function parseError(mixed $result): ?string
    {
        return match (true) {
            isset($result['error']['message']) => $result['error']['message'], // 1
            isset($result['message']) => $result['message'], // 2
            default => null // assuming that result is invalid
        };
    }

    public static function parseMapping(string $ip, array $configs): array
    {
        return [
            'api1' => [
                'url' => 'https://ipinfo.io/' . $ip . '/json?token=' . $configs['api1.key']
            ],
            'api2' => [
                'url' => 'http://ip-api.com/json/' . $ip
            ]
        ];
    }
}
