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
    public $signature = 'changelog:release {level : The release level} {tag : Try to create a git tag}';

    public $description = 'Create a new release of unreleased changes';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'level' => fn () => select(
                label: 'Select a changelog type:',
                options: ChangelogReleaseLevel::toArray(),
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

        $level = $this->argument('level');
        $tag = $this->argument('tag');

        $this->info('Latest version: '.$latestVersion);

        $version = semver($latestVersion);
        switch ($level) {
            case ChangelogReleaseLevel::MAJOR->value:
                $version->incrementMajor();
                break;
            case ChangelogReleaseLevel::MINOR->value:
                $version->incrementMinor();
                break;
            case ChangelogReleaseLevel::PATCH->value:
                $version->incrementPatch();
                break;
        }
        $this->info('New version: '.$version);

        ChangeLogHelper::release($version->major, $version->minor, $version->patch);

        $this->comment('New version released successfully');

        if ($tag) {
            exec('git add CHANGELOG.md && git commit -m "' . str_replace('{version}', config('changelog.version_prefix').$version, config('changelog.release_message')).'"');
            exec('git tag '.config('changelog.version_prefix').$version);
            $this->comment('Tag created successfully');
        }

        return self::SUCCESS;
    }
}
