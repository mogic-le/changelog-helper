<?php

namespace App\Commands;

use App\Enums\ChangelogType;
use App\Lib\ChangelogHelper;
use App\Lib\GitHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\textarea;

class ChangelogAddCommitCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'add-commit';

    public $description = 'Add a new changelog entry based on last commit';

    public function handle(): int
    {
        $type = $this->choice('Select a changelog type:', ChangelogType::toArray());

        $commits = (new GitHelper)->getCommits();

        $selectedCommits = $this->choice('Please select the commits:', $commits, 0, 1, true);

        $description = textarea(
            label: 'Please enter a description:',
            default: implode("\n", $selectedCommits),
        );

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
