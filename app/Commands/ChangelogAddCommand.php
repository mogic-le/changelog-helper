<?php

namespace App\Commands;

use App\Enums\ChangelogType;
use App\Lib\ChangelogHelper;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\select;

class ChangelogAddCommand extends Command implements PromptsForMissingInput
{
    public $signature = 'changelog:add {type : The log type} {description* : The description of the changes}';

    public $description = 'Add a new changelog entry to the changelog file';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'type' => fn () => select(
                label: 'Select a changelog type:',
                options: ChangelogType::toArray(),
                default: ChangelogType::ADDED->value,
            ),
            'description' => ['Please enter a description:', ''],
        ];
    }

    public function handle(): int
    {
        $type = ucfirst($this->argument('type'));
        $description = implode(' ', $this->argument('description', []));
        if (! in_array($type, ChangelogType::toArray())) {
            $this->error('Invalid type. Options are: '.implode(', ', ChangelogType::toArray()));

            return self::FAILURE;
        }
        if (empty($description)) {
            $this->error('Description is required');

            return self::FAILURE;
        }

        ChangelogHelper::addLine($type, $description);

        $this->info('Changelog entry added successfully');

        return self::SUCCESS;
    }
}
