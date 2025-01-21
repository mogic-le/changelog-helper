<?php

use App\Commands\ChangelogAddCommitCommand;
use App\Enums\ChangelogType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    File::ensureDirectoryExists(base_path('storage/app/test', 0755, true));
});

describe('add to changelog', function () {

    $filename = Str::random(20).'.md';

    it('checks if a commit is added', function () use ($filename) {

        config()->set('changelog.filename', 'storage/app/test/'.$filename);

        $gitHelper = Mockery::mock('overload:App\Lib\GitHelper');
        $gitHelper->shouldReceive('getCommits')
            ->andReturn([
                'commit1',
                'commit2',
                'commit3',
            ]);

        $this->artisan(ChangelogAddCommitCommand::class)
            ->expectsChoice('Select a changelog type:', ChangelogType::CHANGED->value, ChangelogType::toArray())
            ->expectsChoice('Please select the commits:', ['commit2'], ['commit1', 'commit2', 'commit3'])
            ->expectsQuestion('Please enter a description:', 'commit2'.PHP_EOL.'Testbeschreibung')
            ->expectsOutput('Changelog entry added successfully')
            ->assertExitCode(0);

        $template = File::get(config('changelog.path').'/Templates/Stubs/TestAddCommitCommand.md');
        $testfile = File::get(base_path(config('changelog.filename')));

        $this->assertSame($template, $testfile);

        $files = File::allFiles('storage/app/test/');

        Storage::delete($files);
    });
});

afterAll(function () {
    File::deleteDirectory(base_path('storage/app/test'));
});
