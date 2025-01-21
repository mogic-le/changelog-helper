<?php

namespace App\Commands;

use App\Enums\ChangelogType;
use App\Lib\ChangelogHelper;
use App\Lib\GitHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

class ChangelogAddCommitCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'add-commit {type : The log type}';

    public $description = 'Add a new changelog entry based on last commit';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'type' => fn () => select(
                label: 'Select a changelog type:',
                options: ChangelogType::toArray(),
                default: ChangelogType::ADDED->value,
            ),
        ];
    }

    public function handle(): int
    {
        $commits = (new GitHelper)->getCommits();

        $selectedCommits = multiselect(
            label: 'Please select the commits:',
            options: $commits,
            scroll: 8,
        );
        $description = textarea(
            label: 'Please enter a description:',
            default: implode("\n", $selectedCommits),
            rows: 5,
        );

        $type = $this->argument('type');

        if (! in_array($type, ChangelogType::toArray())) {
            $this->error('Invalid type. Options are: '.implode(', ', ChangelogType::toArray()));

            return self::FAILURE;
        }

        if (empty(trim($description))) {
            $this->error('Description is required');

            return self::FAILURE;
        }

        foreach (explode("\n", $description) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            ChangelogHelper::addLine($type, $line);
        }
        $this->info('Changelog entry added successfully');

        return self::SUCCESS;
    }
}
