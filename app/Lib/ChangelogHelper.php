<?php

namespace App\Lib;

use Carbon\Carbon;
use File;
use Str;

class ChangelogHelper
{
    public static $identifierUnreleased = 'unreleased';

    public static $identifierUnreleasedHeading = '## [unreleased]';

    public static function path(): string
    {
        $path = implode('/', [
            config('changelog.path', base_path()),
            config('changelog.filename'),
        ]);

        return $path;
    }

    /**
     * Get the template from the template
     */
    public static function template(): string
    {
        return File::get(base_path(config('changelog.template')));
    }

    /**
     * Try to find changelog file or create from template
     */
    public static function prepare(): bool
    {
        $path = self::path();

        if (! File::exists($path) || File::get($path) === '') {

            $content = str_replace('{{package_name}}', config('changelog.package_name'), self::template());

            File::put($path, $content);
        }

        return File::exists($path);
    }

    public static function getLatestVersion(): ?string
    {
        $path = self::path();
        $fileExists = File::exists($path);

        if ($fileExists === false) {
            $fileExists = self::prepare();
        }

        if ($fileExists === false) {
            return false;
        }

        $content = self::parse();

        $releases = [];
        foreach ($content as $releaseLong => $area) {
            $release = self::getRelease($releaseLong);
            if ($release == self::$identifierUnreleased) {
                continue;
            }
            $releases[] = $release;
        }

        return collect($releases)->sortDesc(SORT_NATURAL)->first();
    }

    /**
     * Add a new line to the CHANGELOG file
     *
     * @param  mixed  $type
     * @param  mixed  $text
     */
    public static function addLine(string $type, string $text): bool
    {
        if (empty($text)) {
            return true; // Skip empty lines
        }

        $success = false;
        $path = self::path();

        $fileExists = File::exists($path);

        if ($fileExists === false) {
            $fileExists = self::prepare();
        }

        if ($fileExists === false) {
            return $success;
        }

        $content = self::parse();

        if (empty($content)) {
            $content[self::$identifierUnreleasedHeading] = [];
        }

        if (isset($content[self::$identifierUnreleasedHeading][ucfirst($type)]) === false) {
            $content[self::$identifierUnreleasedHeading][ucfirst($type)] = [];
        }

        // Only add if not already present
        if (! in_array($text, $content[self::$identifierUnreleasedHeading][ucfirst($type)])) {
            $content[self::$identifierUnreleasedHeading][ucfirst($type)][] = $text;
        }

        ksort($content[self::$identifierUnreleasedHeading]);

        // Save the changes
        $success = self::toMarkdown($content);

        return $success;
    }

    private static function getRelease(string $release): ?string
    {
        $pattern = '/\[(.*?)\]/';
        if (preg_match($pattern, $release, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }

    public static function generateLinks(array $links): array
    {
        $linkMeta = config('changelog.links');
        if (empty($linkMeta['unreleased_link'])) {
            return [];
        }
        $versionPrefix = config('changelog.version_prefix');

        $finalLinks = [];
        $i = 0;
        $max = count($links);
        foreach ($links as $release) {
            $i++;
            $link = $linkMeta['unreleased_link'];
            if ($release != self::$identifierUnreleased) {
                $link = str_replace($linkMeta['main_branch'], $versionPrefix.$release, $link);
                if ($max > $i) {
                    $link = str_replace($linkMeta['develop_branch'], $versionPrefix.$links[$i], $link);
                } else {
                    $link = str_replace($linkMeta['develop_branch'], $versionPrefix.$links[$i - 1], $link);
                }
            }

            $finalLinks[] = "[{$release}]: {$link}";
        }

        return $finalLinks;
    }

    public static function sortContent(array $content): array
    {
        return collect($content)->sortKeysUsing('strnatcasecmp')->reverse()->toArray();
    }

    /**
     * Save the field to markdown format
     *
     * @param  mixed  $content
     */
    public static function toMarkdown(array $content = []): bool
    {
        $content = self::sortContent($content);

        $lines = [];
        $links = [];

        foreach ($content as $release => $area) {
            $lines[] = "{$release}\n\n";
            if (($rel = self::getRelease($release)) != null) {
                $links[] = $rel;
            }

            foreach ($area as $type => $areaLines) {
                $lines[] = "### {$type}\n\n";

                // Filter out empty lines and ensure uniqueness
                $uniqueLines = array_filter(array_unique($areaLines), function ($line) {
                    return ! empty(trim($line));
                });

                foreach ($uniqueLines as $line) {
                    $lines[] = "- {$line}\n";
                }

                $lines[] = "\n";
            }
        }

        $content = implode('', $lines);

        if (count($links)) {
            $content .= implode("\n", self::generateLinks($links));
        }

        $path = self::path();
        $contentFile = File::exists($path) ? File::get($path) : '';
        $prefixContent = substr($contentFile, 0, strpos($contentFile, self::$identifierUnreleasedHeading));

        $finalContent = $prefixContent.$content;

        // Save the markdown file
        File::put($path, $finalContent);

        return File::exists($path);
    }

    /**
     * Convert a CHANGELOG file into a field
     */
    public static function parse(): array
    {
        $path = self::path();

        $content = [
            self::$identifierUnreleasedHeading => [],
        ];
        $contentFile = File::exists($path) ? File::get($path) : '';

        preg_match_all("/##\s{0,}\[unreleased\]|\#\#\s{0,}\[\d{1,}\.\d{1,}\.\d{1,}\]\s\-\s\d{4}\-\d{2}-\d{2}/", $contentFile, $result);

        $releases = $result[0];

        if (empty($releases) === true) {
            return [
                self::$identifierUnreleasedHeading => [],
            ];
        }

        $reduced = substr($contentFile, strpos($contentFile, self::$identifierUnreleasedHeading));
        $parts = [];

        if (strripos($reduced, '['.self::$identifierUnreleased.']:') != false) {
            $reduced = substr($reduced, 0, strripos($reduced, '['.self::$identifierUnreleased.']:'));
        }

        foreach ($releases as $index => $release) {
            $content[$release] = [];

            $term = isset($releases[$index + 1]) ? $releases[$index + 1] : "\n";
            $pos = strpos($reduced, $term);

            if ($pos === false) {
                continue;
            }

            $part = substr($reduced, strpos($reduced, $release) + strlen($release), strripos($reduced, $term) - strpos($reduced, $release) - strlen($release));
            $part = trim(preg_replace("/\n{2,}/", "\n", $part));

            preg_match_all("/###\s[a-zA-Z]{1,}/", $part, $types);

            $typesList = [];

            // Extract from the file
            if (empty($types[0])) {
                continue;
            }

            collect($types[0])->each(function ($type, $typeIndex) use ($release, $types, $part, &$content, &$typesList) {

                $start = strpos($part, $type);
                $stop = isset($types[0][$typeIndex + 1]) ? strripos($part, $types[0][$typeIndex + 1]) : strlen($part);
                $typeSimple = substr($type, 4);

                $partType = trim(str_replace("{$type}", '', substr($part, $start, $stop - $start)));

                $typeEntries = implode('', array_map(function ($item) {
                    if (Str::startsWith($item, '-') === true) {
                        $item = '[~/-/~]'.substr($item, 1);
                    }

                    return $item;
                }, preg_split("/\n/", $partType)));

                if (isset($typesList[$type]) == false) {
                    $typesList[$type] = [];
                }

                $typesOfList = collect($typeEntries)->filter(function ($item) {
                    if ($item != '' && $item != "\n") {
                        return $item;
                    }
                })->map(function ($item) {
                    return trim($item);
                });

                if (isset($content[$release][$typeSimple]) === false) {
                    $content[$release][$typeSimple] = [];
                }

                $typeEntriesSplitted = preg_split("/\[~\/-\/~\]/", $typesOfList->first());

                $entries = collect($typeEntriesSplitted)->filter(function ($item) {
                    if ($item != '' && $item != "\n") {
                        return $item;
                    }
                })->map(function ($item) {
                    return trim($item);
                })->toArray();

                $content[$release][$typeSimple] = $entries;

            });
        }

        return $content;
    }

    /**
     * Release the unreleased part of a CHANGELOG file
     *
     * @param  mixed  $major
     * @param  mixed  $minor
     * @param  mixed  $patch
     */
    public static function release(int $major = 0, int $minor = 0, int $patch = 0): bool
    {
        $content = self::parse();
        $date = Carbon::now()->format('Y-m-d');

        if (empty($content["## [{$major}.{$minor}.{$patch}] - {$date}"])) {
            $content["## [{$major}.{$minor}.{$patch}] - {$date}"] = $content[self::$identifierUnreleasedHeading];
            $content[self::$identifierUnreleasedHeading] = [];
        } else {
            $content["## [{$major}.{$minor}.{$patch}] - {$date}"] = array_merge($content["## [{$major}.{$minor}.{$patch}] - {$date}"], $content[self::$identifierUnreleasedHeading]);
            $content[self::$identifierUnreleasedHeading] = [];
        }

        return self::toMarkdown($content);
    }
}
