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

use pocketmine\entity\Skin;
use pocketmine\player\Player;

class SkinAPI
{
    public static function setSkin(Player $player, ?string $skinId = null, ?string $skinData = null, ?string $capeData = null, ?string $geometryName = null, ?string $geometryData = null): void
    {
        $oldSkin = $player->getSkin();
        $skin = new Skin(
            $skinId ?? $oldSkin->getSkinId(),
            $skinData ?? $oldSkin->getSkinData(),
            $capeData ?? $oldSkin->getCapeData(),
            $geometryName ?? $oldSkin->getGeometryName(),
            $geometryData ?? $oldSkin->getGeometryData()
        );
        
        $player->setSkin($skin);
        $player->sendSkin();
    }

    public static function createCapeData(string $filePath): string
    {
        $bytes = '';
        $img = imagecreatefrompng($filePath);
        $lc = getimagesize($filePath);
        $l = (int)$lc[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $a = ((~($rgba >> 24)) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($img);
        return $bytes;
    }
}
