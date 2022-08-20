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

use Mcbeany\libAsync\libAsync;
use pocketmine\utils\Internet;
use RecursiveIteratorIterator;
use SOFe\AwaitGenerator\Await;
use RecursiveDirectoryIterator;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\InternetRequestResult;

class SDAutoUpdater extends PluginBase
{
    public const RELEASE_API = 'https://api.github.com/repos/Unsolicited-Studios/SDPM-Plugins/releases/latest';

    private static SDAutoUpdater $instance;

    public static function getInstance(): SDAutoUpdater
    {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::$instance = $this;

        $this->initRelease();
    }

    public function initRelease(): void
    {
        $logger = $this->getLogger();
        $releaseFile = $this->getFile() . 'RELEASE_VERSION';

        if (!is_file($releaseFile)) {
            $logger->error('The RELEASE_VERSION file does not exist. You must have directly downloaded the plugin from the repository. Please do not do that - check the releases instead.');
            return;
        }
        $contents = file_get_contents($releaseFile);

        SDUpdateInfo::$currentVersion = (int) $contents;

        Await::f2c(function () use ($logger) {
            $result = yield from libAsync::doAsync(fn () => Internet::getURL(self::RELEASE_API));
            if (!$result instanceof InternetRequestResult) {
                $logger->error('GitHub API is down! Please try again later.');
                return;
            }

            SDUpdateInfo::$latestRelease = json_decode($result->getBody(), true);
        }, function () {
            $this->doUpdate();
        });
    }

    public function doUpdate(): void
    {
        if (!isset(SDUpdateInfo::$currentVersion) || !isset(SDUpdateInfo::$latestRelease['tag_name'])) {
            $this->getLogger()->error('Failed to initialize release information. Plugin will shut down.');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $latestVersion = (int) str_replace('r', '', SDUpdateInfo::$latestRelease['tag_name'] ?? '');
        if (SDUpdateInfo::$currentVersion < $latestVersion) {
            $this->getLogger()->info('New version detected. Running updates...');

            $download = SDUpdateInfo::$latestRelease['assets'][0]['browser_download_url'] ?? '';
            if (!str_starts_with($download, 'https://github.com/Unsolicited-Studios/SDPM-Plugins')) {
                $this->getLogger()->error('Download URL for the latest release is incorrect. Is GitHub API not working as intended?');
                return;
            }

            $this->getLogger()->debug('Downloading update from ' . $download);

            $logger = $this->getLogger();
            $dataFolder = $this->getDataFolder();
            $pluginsFolder = $this->getServer()->getDataPath() . 'plugins';
            $releaseFile = $this->getFile() . 'RELEASE_VERSION';
            Await::f2c(function () use ($latestVersion, $download, $logger, $dataFolder, $pluginsFolder, $releaseFile) {
                $result = yield from libAsync::doAsync(fn () => Internet::getURL($download));
                if (!$result instanceof InternetRequestResult) {
                    $logger->error('Failed to download update.');
                    return;
                }

                $logger->debug('Downloaded update. Extracting...');

                $extractFolder = $dataFolder . 'update';

                file_put_contents($dataFolder . 'update.zip', $result->getBody());

                $zip = new \ZipArchive();
                $zip->open($dataFolder . 'update.zip');
                $zip->extractTo($extractFolder);
                $zip->close();

                unlink($dataFolder . 'update.zip');

                $logger->debug('Extracted update. Updating only existing plugins.');

                $serverPlugins = SDUpdateInfo::getPluginNames($pluginsFolder);
                $updatePlugins = SDUpdateInfo::getPluginNames($extractFolder);
                foreach ($serverPlugins as $pluginName => $serverFile) {
                    if (!isset($updatePlugins[$pluginName])) {
                        continue;
                    }
                    $updateFile = $updatePlugins[$pluginName];

                    $logger->debug('Updating ' . $pluginName . ' from ' . $updateFile . ' to ' . $serverFile);

                    rename($updateFile, $serverFile);
                }

                $logger->debug('Updated existing plugins. Removing extracted files.');

                foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($extractFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                ) as $item) {
                    if ($item->isDir()) {
                        rmdir($item->getRealPath());
                        return;
                    }
                    unlink($item->getRealPath());
                }
                rmdir($extractFolder);

                $logger->debug('Removed extracted files. Updating RELEASE_VERSION file.');

                file_put_contents($releaseFile, $latestVersion);

                $logger->info('Update complete. New version installed: ' . SDUpdateInfo::$latestRelease['name']);
            });
        }
    }

    public function getFile(): string
    {
        return parent::getFile();
    }
}
