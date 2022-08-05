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

namespace SolicitDev\libVPN\util;

use SolicitDev\libVPN\API;

class Algorithm
{
    public static function getIncludes(int $minimumChecks = 2): array
    {
        $timings = API::$timingCache;
        asort($timings, SORT_NUMERIC);
        $detection = API::$detectionCount;
        arsort($detection, SORT_NUMERIC);

        $responseTop = array_slice($timings, 0, $minimumChecks + 2, true);
        $detectionTop = array_slice($detection, 0, $minimumChecks + 2, true);

        $intersect = array_intersect_key($responseTop, $detectionTop);
        if (count($intersect) < $minimumChecks) {
            // TODO: Better logic for this
            $intersect = $responseTop;
        }
        return $intersect;
    }
}
