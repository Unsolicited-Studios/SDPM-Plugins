<?php

/**
 * Lots of parts have been taken from VirionsTools.
 * https://github.com/ifera-mc/VirionTools
 */

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

class VirionBuilder
{
    public static array $virionDirectories = [];

    public static function buildVirions(): void
    {
        self::$virionDirectories = [
            getcwd() . '/libraries/developed/' => getcwd() . '/built_libraries/',
            getcwd() . '/libraries/managed/' => getcwd() . '/built_libraries/',
            getcwd() . '/libraries/updated/' => getcwd() . '/built_libraries/'
        ];

        echo "🔨 Building Virions. Current working directory: " . getcwd() . PHP_EOL;
        foreach (self::$virionDirectories as $virionsDir => $targetDir) {
            echo "🔨 Building virions from this directory: $virionsDir\n";

            foreach (new \DirectoryIterator($virionsDir) as $folder) {
                if (!$folder->isDir() || $folder->isDot()) {
                    continue;
                }

                $folderName = $folder->getBasename();
                $fileName = "SDPM_" . $folderName . ".phar";
                $folderDir = $folder->getRealPath();
                if (!is_dir($folderDir)) {
                    continue;
                }
                echo "🔨 Starting build for $folderName\n";

                @mkdir($targetDir);

                $metaData = self::generateVirionMetadataFromYml($folderDir . "/" . "virion.yml");

                echo "🗜️ Compressing $folderName Virion...\n";
                self::buildVirion($targetDir . $fileName, $folderDir, [], $metaData, '<?php __HALT_COMPILER();');

                echo "🌟 Virion $folderName v" . $metaData['version'] . " has been created at $targetDir $fileName\n";
            }
        }
        VirionInjector::injectVirions();
    }

    public static function buildVirion(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = Phar::SHA1, ?int $compression = null): void
    {
        if (file_exists($pharPath)) {
            echo "⚠️ Phar file already exists, overwriting...\n";
            Phar::unlinkArchive($pharPath);
        }
        echo "➡️ Adding files...\n";

        $start = microtime(true);
        $phar = new Phar($pharPath);
        $phar->setMetadata($metadata);
        $phar->setStub($stub);
        $phar->setSignatureAlgorithm($signatureAlgo);
        $phar->startBuffering();

        $excludedSubstrings = self::pregQuoteArray([realpath($pharPath)], "/");
        $folderPatterns = self::pregQuoteArray([
            DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR . "."
        ], "/");
        $basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), "/");
        foreach ($folderPatterns as $p) {
            $excludedSubstrings[] = $basePattern . ".*" . $p;
        }

        $regex = sprintf(
            "/^(?!.*(%s))^%s(%s).*/i",
            implode("|", $excludedSubstrings),
            preg_quote($basePath, "/"),
            implode("|", self::pregQuoteArray($includedPaths, "/"))
        );

        $directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_PATHNAME);
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

    public static function generateVirionMetadataFromYml(string $virusVirionYmlPath): ?array
    {
        if (!file_exists($virusVirionYmlPath)) {
            throw new \RuntimeException("virion.yml not found. Aborting...");
        }

        $virusVirionYml = yaml_parse_file($virusVirionYmlPath);

        return [
            "compiler"     => "VirionTools",
            "name"         => $virusVirionYml["name"],
            "version"      => $virusVirionYml["version"],
            "antigen"      => $virusVirionYml["antigen"],
            "api"          => $virusVirionYml["api"] ?? "",
            "php"          => $virusVirionYml["php"] ?? [],
            "description"  => $virusVirionYml["description"] ?? "",
            "authors"      => $virusVirionYml["authors"] ?? [],
            "creationDate" => time()
        ];
    }

    public static function pregQuoteArray(array $strings, string $delim = null): array
    {
        return array_map(function (string $str) use ($delim): string {
            return preg_quote($str, $delim);
        }, $strings);
    }
}

class VirionInjector
{
    public const VIRION_INFECTION_MODE_SYNTAX = 0;
    public const VIRION_INFECTION_MODE_SINGLE = 1;
    public const VIRION_INFECTION_MODE_DOUBLE = 2;

    public static function injectVirions(): void
    {
        echo "🔨 Injecting Virions. Current working directory: " . getcwd() . PHP_EOL;
        foreach (VirionBuilder::$virionDirectories as $virionsDir => $targetDir) {
            echo "🔨 Injecting virions to other virions in this directory: $virionsDir\n";

            foreach (new \DirectoryIterator($virionsDir) as $folder) {
                if (!$folder->isDir() || $folder->isDot()) {
                    continue;
                }

                $folderName = $folder->getBasename();
                $folderDir = $folder->getRealPath();
                echo "🛫 Checking Virions For $folderName\n";
                if (!is_dir($folderDir) || !is_file($folderDir . "/virion.yml")) {
                    echo "❌ File virion.yml Does Not Exist For $folderName\n";
                    continue;
                }

                $hostVirionYaml = yaml_parse(file_get_contents($folderDir . "/virion.yml"));
                if (isset($hostVirionYaml["virions"]) && is_array($hostVirionYaml["virions"])) {
                    echo "➡️ Detected Virions Required For $folderName\n";

                    @mkdir($targetDir);
                    foreach ($hostVirionYaml["virions"] as $virionRequired) {
                        $virionDir = $targetDir . "SDPM_" . $virionRequired . ".phar";
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
        $hostVirionYml = yaml_parse(file_get_contents($hostPharPath . "virion.yml"));
        $virusVirionYml = yaml_parse(file_get_contents($virusPath . "virion.yml"));
        if (!is_array($virusVirionYml)) {
            throw new RuntimeException("Corrupted virion.yml, could not activate virion", 2);
        }

        $infectionLog = isset($host["virus-infections.json"]) ? json_decode(file_get_contents($hostPharPath . "virus-infections.json"), true) : [];

        $genus = $virusVirionYml["name"];
        $antigen = $virusVirionYml["antigen"];
        foreach ($infectionLog as $old) {
            if ($old["antigen"] === $antigen) {
                echo "❌ Target already infected by this virion, aborting\n";
                return 3;
            }
        }

        $antibody = $hostVirionYml["antigen"] . "\\libs\\" . $antigen;
        $infectionLog[$antibody] = $virusVirionYml;

        echo "🌟 Using antibody $antibody for virion $genus ({$antigen})\n";

        $hostPharPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath());
        $hostChanges = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($hostPharPath)) as $name => $chromosome) {
            if ($chromosome->isDir()) continue;
            if ($chromosome->getExtension() !== "php") continue;

            $rel = self::cutPrefix($name, $hostPharPath);
            $data = self::changeDna(file_get_contents($name), $antigen, $antibody, $mode, $hostChanges);
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
                    echo "⚠️ File $rel in virion is not under the antigen $antigen ($restriction)\n";
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

    public static function cutPrefix(string $string, string $prefix): string
    {
        if (strpos($string, $prefix) !== 0) throw new AssertionError("\$string does not start with \$prefix:\n$string\n$prefix");
        return substr($string, strlen($prefix));
    }
}

VirionBuilder::buildVirions();