<?php

use App\Commands\ChangelogAddCommand;
use App\Enums\ChangelogType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

describe('add to changelog', function () {

    $filename = Str::random(20).'.md';

    it('checks if a line is added', function () use ($filename) {

        config()->set('changelog.filename', 'storage/app/test/'.$filename);

        $this->artisan(ChangelogAddCommand::class)
            ->expectsChoice('Select a changelog type:', ChangelogType::ADDED->value, ChangelogType::toArray())
            ->expectsQuestion('Please enter a description:', 'Test')
            ->expectsOutput('Changelog entry added successfully')
            ->assertExitCode(0);

        $template = File::get(config('changelog.path').'/Templates/Stubs/TestAddCommand.md');
        $testfile = File::get(base_path(config('changelog.filename')));

        $this->assertSame($template, $testfile);

    });

    it('checks if another line is added', function () use ($filename) {

        config()->set('changelog.filename', 'storage/app/test/'.$filename);

        $this->artisan(ChangelogAddCommand::class)
            ->expectsQuestion('Select a changelog type:', 'Added')
            ->expectsQuestion('Please enter a description:', 'Test2')
            ->expectsOutput('Changelog entry added successfully')
            ->assertExitCode(0);

        $template = File::get(config('changelog.path').'/Templates/Stubs/TestAddCommand2.md');
        $testfile = File::get(base_path(config('changelog.filename')));

        $this->assertSame($template, $testfile);
    });

    it('does not add a line without description', function () use ($filename) {

        config()->set('changelog.filename', 'storage/app/test/'.$filename);

        $this->artisan(ChangelogAddCommand::class)
            ->expectsQuestion('Select a changelog type:', 'Added')
            ->expectsQuestion('Please enter a description:', '')
            ->assertExitCode(1);

        $template = File::get(config('changelog.path').'/Templates/Stubs/TestAddCommand2.md');
        $testfile = File::get(base_path(config('changelog.filename')));

        $this->assertSame($template, $testfile);
    });

    it('does not add a line with wrong type', function () use ($filename) {

        config()->set('changelog.filename', 'storage/app/test/'.$filename);

        $this->artisan(ChangelogAddCommand::class)
            ->expectsQuestion('Select a changelog type:', 'Foo')
            ->expectsQuestion('Please enter a description:', 'Test')
            ->assertExitCode(1);

        $template = File::get(config('changelog.path').'/Templates/Stubs/TestAddCommand2.md');
        $testfile = File::get(base_path(config('changelog.filename')));

        $this->assertSame($template, $testfile);

        $files = File::allFiles('storage/app/test/');

        Storage::delete($files);
    });
});
