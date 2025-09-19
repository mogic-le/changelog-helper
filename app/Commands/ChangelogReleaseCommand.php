<?php

namespace App\Commands;

use App\Enums\ChangelogReleaseLevel;
use App\Lib\ChangelogHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ChangelogReleaseCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'release {version : The version number (e.g., 3.2.1) or release level (major, minor, patch)} {tag : Try to create a git tag}';

    public $description = 'Create a new release of unreleased changes. Use a specific version (e.g., 3.2.1) or release level (major, minor, patch)';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'version' => fn () => select(
                label: 'Select a release level or enter a specific version:',
                options: array_merge(['custom'], ChangelogReleaseLevel::toArray()),
                default: ChangelogReleaseLevel::MINOR->value,
            ),
            'tag' => fn () => confirm(
                label: 'Commit and create a tag?',
                default: false
            ),
        ];
    }

    public function handle(): int
    {
        $latestVersion = ChangelogHelper::getLatestVersion();
        $latestVersion = $latestVersion ?? '0.0.0';

        $versionInput = $this->argument('version');
        $tag = $this->argument('tag');

        $this->info('Latest version: '.$latestVersion);

        // Check if the input is a specific version number or a release level
        if ($this->isVersionNumber($versionInput)) {
            // Use the specific version provided
            $version = semver($versionInput);
            $this->info('Using specified version: '.$version);
        } else {
            // Treat as release level and increment from latest version
            $version = semver($latestVersion);
            switch ($versionInput) {
                case ChangelogReleaseLevel::MAJOR->value:
                    $version->incrementMajor();
                    break;
                case ChangelogReleaseLevel::MINOR->value:
                    $version->incrementMinor();
                    break;
                case ChangelogReleaseLevel::PATCH->value:
                    $version->incrementPatch();
                    break;
                default:
                    $this->error("Invalid release level: {$versionInput}. Use 'major', 'minor', 'patch', or a specific version number (e.g., '3.2.1').");

                    return self::FAILURE;
            }
            $this->info('New version: '.$version);
        }

        $releaseResult = ChangeLogHelper::release($version->major, $version->minor, $version->patch);

        if ($releaseResult) {
            $this->comment('New version released successfully');
        } else {
            $this->error('Failed to release new version. Check if there are unreleased changes.');

            return self::FAILURE;
        }

        if ($tag) {
            exec('git add CHANGELOG.md && git commit -m "'.str_replace('{version}', config('changelog.version_prefix').$version, config('changelog.release_message')).'"');
            exec('git tag '.config('changelog.version_prefix').$version);
            $this->comment('Tag created successfully');
        }

        return self::SUCCESS;
    }

    /**
     * Check if the input is a version number (e.g., "3.2.1") rather than a release level
     */
    private function isVersionNumber(string $input): bool
    {
        // Check if the input matches semantic version pattern (X.Y.Z)
        return preg_match('/^\d+\.\d+\.\d+$/', $input) === 1;
    }
}
