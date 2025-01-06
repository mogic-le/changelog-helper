<?php

namespace App\Commands;

use App\Enums\ChangelogType;
use App\Lib\ChangelogHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;

class ChangelogAddCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'add';

    public $description = 'Add a new changelog entry to the changelog file';

    public function handle(): int
    {
        $type = $this->choice('Select a changelog type:', ChangelogType::toArray());

        $description = $this->ask('Please enter a description:', '');

        if (! in_array($type, ChangelogType::toArray())) {
            $this->error('Invalid type. Options are: '.implode(', ', ChangelogType::toArray()));

            return self::FAILURE;
        }
        if (empty(Str::replace(' ', '', $description))) {
            $this->error('Description is required');

            return self::FAILURE;
        }

        ChangelogHelper::addLine($type, $description);

        $this->info('Changelog entry added successfully');

        return self::SUCCESS;
    }
}
