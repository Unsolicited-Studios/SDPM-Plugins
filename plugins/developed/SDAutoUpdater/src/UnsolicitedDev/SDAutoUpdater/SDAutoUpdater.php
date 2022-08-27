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
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\InternetRequestResult;

class SDAutoUpdater extends PluginBase
{
    use SingletonTrait;

    public const RELEASE_API = 'https://api.github.com/repos/Unsolicited-Studios/SDPM-Plugins/releases/latest';

    public static array $latestRelease;
    public static int $currentVersion;
    
    public function onEnable(): void
    {
        self::setInstance($this);

        $this->initRelease(function () {
            $this->checkUpdate();
        });
    }

    public function onDisable(): void
    {
        $crashCausers = SDUpdateUtil::getCrashCausers();
        if (count($crashCausers) > 0) {
            // TODO: Log these errors somewhere for us to see
            $this->getLogger()->error('The following plugins caused a crash: ' . implode(', ', $crashCausers) . '. An update will be forced.');
            $this->doUpdate();
        }
    }

    public function initRelease(?callable $callable = null): void
    {
        $logger = $this->getLogger();
        $releaseFile = $this->getFile() . 'RELEASE_VERSION';

        if (!is_file($releaseFile)) {
            $logger->error('The RELEASE_VERSION file does not exist. You must have directly downloaded the plugin from the repository. Please do not do that - check the releases instead.');
            return;
        }
        $contents = file_get_contents($releaseFile);

        self::$currentVersion = (int) $contents;

        Await::f2c(function () use ($logger) {
            $result = yield from libAsync::doAsync(fn () => Internet::getURL(self::RELEASE_API));
            if (!$result instanceof InternetRequestResult) {
                $logger->error('GitHub API is down! Please try again later.');
                return;
            }

            self::$latestRelease = json_decode($result->getBody(), true);
        }, $callable);
    }

    public function checkUpdate(): void
    {
        if (!isset(self::$currentVersion) || !isset(self::$latestRelease['tag_name'])) {
            $this->getLogger()->error('Failed to initialize release information. Plugin will shut down.');
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $latestVersion = (int) str_replace('r', '', self::$latestRelease['tag_name'] ?? '');
        if (self::$currentVersion < $latestVersion) {
            $this->getLogger()->info('New version detected. Running updates...');
            
            $this->doUpdate(function() use ($latestVersion) {
                $this->getLogger()->debug('Updating RELEASE_VERSION file.');

                file_put_contents($this->getFile() . 'RELEASE_VERSION', $latestVersion);
            });
        }
    }

    public function doUpdate(?callable $callable = null): void
    {
        $download = self::$latestRelease['assets'][0]['browser_download_url'] ?? '';
        if (!str_starts_with($download, 'https://github.com/Unsolicited-Studios/SDPM-Plugins')) {
            $this->getLogger()->error('Download URL for the latest release is incorrect. Is GitHub API not working as intended?');
            return;
        }

        $this->getLogger()->debug('Downloading update from ' . $download);

        $logger = $this->getLogger();
        $dataFolder = $this->getDataFolder();
        $pluginsFolder = $this->getServer()->getDataPath() . 'plugins';
        Await::f2c(function () use ($download, $logger, $dataFolder, $pluginsFolder) {
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

            $serverPlugins = SDUpdateUtil::getPluginNames($pluginsFolder);
            $updatePlugins = SDUpdateUtil::getPluginNames($extractFolder);
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

            $logger->info('Update complete. New version installed: ' . self::$latestRelease['name']);
        }, $callable);
    }

    public function getFile(): string
    {
        return parent::getFile();
    }
}
