<?php

/**
 * Lots of parts have been taken from VirionsTools.
 * https://github.com/ifera-mc/VirionTools
 */

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

class PluginBuilder
{
    public static array $pluginDirectories = [];

    public static function buildPlugins(): void
    {
        self::$pluginDirectories = [
            getcwd() . '/plugins/developed/' => getcwd() . '/built_plugins/',
            getcwd() . '/plugins/managed/' => getcwd() . '/built_plugins/',
            getcwd() . '/plugins/revived/' => getcwd() . '/built_plugins/',
            getcwd() . '/plugins/updated/' => getcwd() . '/built_plugins/'
        ];
        
        echo "➡️ Moving RELEASE_VERSION to SDAutoUpdater";
        
        @copy(getcwd() . '/extras/tools/RELEASE_VERSION', getcwd() . '/plugins/developed/SDAutoUpdater/RELEASE_VERSION');
        
        echo "🔨 Building plugins. Current working directory: " . getcwd() . PHP_EOL;
        foreach (self::$pluginDirectories as $pluginsDir => $targetDir) {
            @mkdir($targetDir);
        
            foreach (new \DirectoryIterator($pluginsDir) as $folder) {
                if (!$folder->isDir() || $folder->isDot()) {
                    continue;
                }
        
                $folderName = $folder->getBasename();
                $fileName = 'SDPM_' . $folderName . '.phar';
                $pharPath = $targetDir . $fileName;
        
                $folderDir = $folder->getRealPath();
                if (!is_dir($folderDir)) {
                    continue;
                }
        
                $metadata = self::generatePluginMetadataFromYml($folderDir . '/plugin.yml');
                assert($metadata !== null);
        
                $stubMetadata = [];
                foreach ($metadata as $key => $value) {
                    $stubMetadata[] = addslashes(ucfirst($key) . ": " . (is_array($value) ? implode(", ", $value) : $value));
                }
        
                self::buildPhar($pharPath, $folderDir . '/', [], $metadata, '<?php __HALT_COMPILER();', \Phar::SHA1);
        
                echo "🌟 Plugin $folderName v" . $metadata['version'] . " has been created at $pharPath\n";
                continue;
            }
        }
        @unlink(getcwd() . '/plugins/developed/SDAutoUpdater/RELEASE_VERSION');
        PluginInjector::injectVirions();
    }
    
    public static function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1, ?int $compression = null): void
    {
        $basePath = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $includedPaths = array_map(function ($path): string {
            $path = rtrim(str_replace("/", DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
            return is_dir($path) ? $path . DIRECTORY_SEPARATOR : $path;
        }, $includedPaths);
        if (file_exists($pharPath)) {
            echo "⚠️ Phar file already exists, overwriting...\n";
            try {
                \Phar::unlinkArchive($pharPath);
            } catch (\PharException $e) {
                //unlinkArchive() doesn't like dodgy phars
                unlink($pharPath);
            }
        }
    
        echo "➡️ Adding files...\n";
    
        $start = microtime(true);
        $phar = new \Phar($pharPath);
        $phar->setMetadata($metadata);
        $phar->setStub($stub);
        $phar->setSignatureAlgorithm($signatureAlgo);
        $phar->startBuffering();
    
        //If paths contain any of these, they will be excluded
        $excludedSubstrings = self::preg_quote_array([
            realpath($pharPath), //don't add the phar to itself
        ], '/');
    
        $folderPatterns = self::preg_quote_array([
            DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR . '.' //"Hidden" files, git dirs etc
        ], '/');
    
        //Only exclude these within the basedir, otherwise the project won't get built if it itself is in a directory that matches these patterns
        $basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), '/');
        foreach ($folderPatterns as $p) {
            $excludedSubstrings[] = $basePattern . '.*' . $p;
        }
    
        $regex = sprintf(
            '/^(?!.*(%s))^%s(%s).*/i',
            implode('|', $excludedSubstrings), //String may not contain any of these substrings
            preg_quote($basePath, '/'), //String must start with this path...
            implode('|', self::preg_quote_array($includedPaths, '/')) //... and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
        );
    
        $directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_PATHNAME); //can't use fileinfo because of symlinks
        $iterator = new \RecursiveIteratorIterator($directory);
        $regexIterator = new \RegexIterator($iterator, $regex);
    
        $count = count($phar->buildFromIterator($regexIterator, $basePath));
        echo "➡️ Added $count files\n";
    
        if ($compression !== null) {
            echo "➡️ Checking for compressible files...\n";
            foreach ($phar as $file => $finfo) {
                /** @var \PharFileInfo $finfo */
                if ($finfo->getSize() > (1024 * 512)) {
                    echo "🗜️ Compressing " . $finfo->getFilename() . "\n";
                    $finfo->compress($compression);
                }
            }
        }
        $phar->stopBuffering();
    
        echo "🌟 Done in " . round(microtime(true) - $start, 3) . "s\n";
    }
    
    public static function generatePluginMetadataFromYml(string $pluginYmlPath): ?array
    {
        if (!file_exists($pluginYmlPath)) {
            return null;
        }
    
        $pluginYml = yaml_parse_file($pluginYmlPath);
        return [
            "name" => $pluginYml["name"],
            "version" => $pluginYml["version"],
            "main" => $pluginYml["main"],
            "api" => $pluginYml["api"],
            "depend" => $pluginYml["depend"] ?? "",
            "description" => $pluginYml["description"] ?? "",
            "authors" => $pluginYml["authors"] ?? "",
            "website" => $pluginYml["website"] ?? "",
            "creationDate" => time()
        ];
    }
    
    public static function preg_quote_array(array $strings, string $delim = null): array
    {
        return array_map(function (string $str) use ($delim): string {
            return preg_quote($str, $delim);
        }, $strings);
    }
}

class PluginInjector
{
    public const VIRION_INFECTION_MODE_SYNTAX = 0;
    public const VIRION_INFECTION_MODE_SINGLE = 1;
    public const VIRION_INFECTION_MODE_DOUBLE = 2;

    public static function injectVirions(): void
    {
        echo "🔨 Injecting Virions. Current working directory: " . getcwd() . "\n";
        foreach (PluginBuilder::$pluginDirectories as $pluginsDir => $targetDir) {
            echo "🔨 Injecting virions to plugins in this directory: $targetDir\n";

            foreach (new \DirectoryIterator($pluginsDir) as $folder) {
                if (!$folder->isDir() || $folder->isDot()) {
                    continue;
                }

                $folderName = $folder->getBasename();
                $folderDir = $folder->getRealPath();
                echo "🛫 Checking Virions For $folderName\n";
                if (!is_dir($folderDir) || !is_file($folderDir . "/plugin.yml")) {
                    echo "❌ File plugin.yml Does Not Exist For $folderName\n";
                    continue;
                }

                $pluginYaml = yaml_parse(file_get_contents($folderDir . "/plugin.yml"));
                if (isset($pluginYaml["virions"]) && is_array($pluginYaml["virions"])) {
                    echo "➡️ Detected Virions Required For $folderName\n";

                    @mkdir($targetDir);
                    foreach ($pluginYaml["virions"] as $virionRequired) {
                        $virionDir = getcwd() . '/built_libraries/' . "SDPM_" . $virionRequired . ".phar";
                        $hostDir = $targetDir . "SDPM_" . $folderName . ".phar";

                        echo "➡️ $folderName Requested For $virionRequired\n";
                        if (is_file($virionDir)) {
                            $virion = new Phar($virionDir);
                            $host = new Phar($hostDir);

                            self::virionInfect($virion, $host);
                        }
                    }
                }
            }
        }
    }

    public static function virionInfect(Phar $virus, Phar $host, int $mode = self::VIRION_INFECTION_MODE_SYNTAX, int &$hostChanges = 0, int &$viralChanges = 0): int
    {
        if (!isset($virus["virion.yml"])) {
            throw new RuntimeException("virion.yml not found, could not activate virion", 2);
        }

        $hostPharPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath()) . "/";
        $virusPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $virus->getPath()) . "/";
        $pluginYml = yaml_parse(file_get_contents($hostPharPath . "plugin.yml"));
        $virionYml = yaml_parse(file_get_contents($virusPath . "virion.yml"));
        if (!is_array($virionYml)) {
            throw new RuntimeException("Corrupted virion.yml, could not activate virion", 2);
        }

        $infectionLog = isset($host["virus-infections.json"]) ? json_decode(file_get_contents($hostPharPath . "virus-infections.json"), true) : [];

        $genus = $virionYml["name"];
        $antigen = $virionYml["antigen"];
        foreach ($infectionLog as $old) {
            if ($old["antigen"] === $antigen) {
                echo "❌ Target already infected by this virion, aborting\n";
                return 3;
            }
        }

        $antibody = self::getPrefix($pluginYml) . $antigen;
        $infectionLog[$antibody] = $virionYml;

        echo "🌟 Using antibody $antibody for virion $genus ({$antigen})\n";

        $hostPharPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath());
        $hostChanges = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($hostPharPath)) as $name => $chromosome) {
            if ($chromosome->isDir()) continue;
            if ($chromosome->getExtension() !== "php") continue;

            $rel = self::cutPrefix($name, $hostPharPath);
            $data = self::changeDna($original = file_get_contents($name), $antigen, $antibody, $mode, $hostChanges);
            if ($data !== "") $host[$rel] = $data;
        }

        $restriction = "src/" . str_replace("\\", "/", $antigen) . "/"; // restriction enzyme ^_^
        $ligase = "src/" . str_replace("\\", "/", $antibody) . "/";

        $viralChanges = 0;
        foreach (new RecursiveIteratorIterator($virus) as $name => $genome) {
            if ($genome->isDir()) continue;

            $rel = self::cutPrefix($name, "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $virus->getPath()) . "/");

            if (strpos($rel, "resources/") === 0) {
                /** @phpstan-ignore-next-line */
                $host[$rel] = file_get_contents($name);
            } elseif (strpos($rel, "src/") === 0) {
                if (strpos($rel, $restriction) !== 0) {
                    echo "⚠️ file $rel in virion is not under the antigen $antigen ($restriction)\n";
                    $newRel = $rel;
                } else {
                    $newRel = $ligase . self::cutPrefix($rel, $restriction);
                }
                $data = self::changeDna(file_get_contents($name), $antigen, $antibody, $mode, $viralChanges); // it"s actually RNA
                $host[$newRel] = $data;
            }
        }
        /** @phpstan-ignore-next-line */
        $host["virus-infections.json"] = json_encode($infectionLog);
        return 0;
    }

    public static function changeDna(string $chromosome, string $antigen, string $antibody, int $mode, int &$count = 0): string
    {
        switch ($mode) {
            case self::VIRION_INFECTION_MODE_SYNTAX:
                $tokens = token_get_all($chromosome);
                $tokens[] = ""; // should not be valid though
                foreach ($tokens as $offset => $token) {
                    if (!is_array($token) or $token[0] !== T_WHITESPACE) {
                        /** @noinspection IssetArgumentExistenceInspection */
                        list($id, $str, $line) = is_array($token) ? $token : [-1, $token, $line ?? 1];
                        //namespace test; is a T_STRING whereas namespace test\test; is not.
                        if (isset($init, $prefixToken) and $id === T_STRING) {
                            if ($str === $antigen) { // case-sensitive!
                                $tokens[$offset][1] = $antibody . substr($str, strlen($antigen));
                                ++$count;
                            } elseif (stripos($str, $antigen) === 0) {
                                echo "\x1b[38;5;227m\n[WARNING] Not replacing FQN $str case-insensitively.\n\x1b[m";
                            }
                            unset($init, $prefixToken);
                        } else {
                            if ($id === T_NAMESPACE) {
                                $init = $offset;
                                $prefixToken = $id;
                            } elseif ($id === T_NAME_QUALIFIED) {
                                if (($str[strlen($antigen)] ?? "\\") === "\\") {
                                    if (strpos($str, $antigen) === 0) { // case-sensitive!
                                        $tokens[$offset][1] = $antibody . substr($str, strlen($antigen));
                                        ++$count;
                                    } elseif (stripos($str, $antigen) === 0) {
                                        echo "\x1b[38;5;227m\n[WARNING] Not replacing FQN $str case-insensitively.\n\x1b[m";
                                    }
                                }
                                unset($init, $prefixToken);
                            } elseif ($id === T_NAME_FULLY_QUALIFIED) {
                                if (strpos($str, "\\" . $antigen . "\\") === 0) { // case-sensitive!
                                    $tokens[$offset][1] = "\\" . $antibody . substr($str, strlen($antigen) + 1);
                                    ++$count;
                                } elseif (stripos($str, "\\" . $antigen . "\\") === 0) {
                                    echo "\x1b[38;5;227m\n[WARNING] Not replacing FQN $str case-insensitively.\n\x1b[m";
                                }
                                unset($init, $prefixToken);
                            }
                        }
                    }
                }
                $ret = "";
                foreach ($tokens as $token) {
                    $ret .= is_array($token) ? $token[1] : $token;
                }
                break;
            case self::VIRION_INFECTION_MODE_SINGLE:
                $ret = str_replace($antigen, $antibody, $chromosome, $subCount);
                $count += $subCount;
                break;
            case self::VIRION_INFECTION_MODE_DOUBLE:
                $ret = str_replace(
                    [$antigen, str_replace("\\", "\\\\", $antigen)],
                    [$antibody, str_replace("\\", "\\\\", $antibody)],
                    $chromosome,
                    $subCount
                );
                $count += $subCount;
                break;
            default:
                throw new InvalidArgumentException("Unknown mode: $mode");
        }
        return $ret;
    }

    public static function getPrefix(array $pluginYml): string
    {
        $main = $pluginYml["main"];
        $mainArray = explode("\\", $main);

        array_pop($mainArray);

        $path = implode("\\", $mainArray);
        $prefix = $path . "\\libs\\";
        return $prefix;
    }

    public static function cutPrefix(string $string, string $prefix): string
    {
        if (strpos($string, $prefix) !== 0) throw new AssertionError("\$string does not start with \$prefix:\n$string\n$prefix");
        return substr($string, strlen($prefix));
    }
}

PluginBuilder::buildPlugins();
