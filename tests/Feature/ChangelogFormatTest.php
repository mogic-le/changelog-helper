<?php

use App\Lib\ChangelogHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    File::ensureDirectoryExists(base_path('storage/app/test', 0755, true));
});

describe('changelog formatting', function () {
    $filename = Str::random(20).'.md';

    beforeEach(function () use ($filename) {
        config()->set('changelog.filename', 'storage/app/test/'.$filename);
        ChangelogHelper::prepare();
    });

    it('prevents duplicate entries when adding the same line multiple times', function () {
        // Add the same entry multiple times
        ChangelogHelper::addLine('added', 'Test feature');
        ChangelogHelper::addLine('added', 'Test feature');
        ChangelogHelper::addLine('added', 'Test feature');

        $content = ChangelogHelper::parse();

        // Check that there's only one instance of the entry
        $this->assertCount(1, $content[ChangelogHelper::$identifierUnreleasedHeading]['Added']);
        $this->assertContains('Test feature', $content[ChangelogHelper::$identifierUnreleasedHeading]['Added']);
    });

    it('maintains proper section formatting', function () {
        // Add entries to different sections
        ChangelogHelper::addLine('added', 'New feature');
        ChangelogHelper::addLine('fixed', 'Bug fix');

        // Get the raw content to check formatting
        $path = ChangelogHelper::path();
        $content = File::get($path);

        // Check for proper section headers and content
        $this->assertStringContainsString('### Added', $content);
        $this->assertStringContainsString('- New feature', $content);
        $this->assertStringContainsString('### Fixed', $content);
        $this->assertStringContainsString('- Bug fix', $content);

        // Make sure sections are properly separated
        $this->assertFalse(strpos($content, '- New feature### Fixed') !== false);
    });

    it('sorts sections alphabetically', function () {
        // Add entries in non-alphabetical order
        ChangelogHelper::addLine('fixed', 'Bug fix');
        ChangelogHelper::addLine('added', 'New feature');
        ChangelogHelper::addLine('changed', 'Updated component');

        $content = ChangelogHelper::parse();

        // Get the keys in the order they appear
        $keys = array_keys($content[ChangelogHelper::$identifierUnreleasedHeading]);

        // Check that they're in alphabetical order
        $this->assertEquals(['Added', 'Changed', 'Fixed'], $keys);
    });

    it('handles empty lines properly', function () {
        // Try to add an empty line (should be ignored)
        ChangelogHelper::addLine('added', '');
        ChangelogHelper::addLine('added', 'Valid entry');

        $content = ChangelogHelper::parse();

        // Check that empty entry was not added
        $this->assertArrayHasKey('Added', $content[ChangelogHelper::$identifierUnreleasedHeading]);
        $entries = $content[ChangelogHelper::$identifierUnreleasedHeading]['Added'];
        $this->assertContains('Valid entry', $entries);
        $this->assertNotContains('', $entries);
    });

    it('preserves existing content when adding new entries', function () {
        // Add initial entries
        ChangelogHelper::addLine('added', 'First feature');
        ChangelogHelper::addLine('fixed', 'First bug fix');

        // Add more entries
        ChangelogHelper::addLine('added', 'Second feature');
        ChangelogHelper::addLine('fixed', 'Second bug fix');

        $content = ChangelogHelper::parse();

        // Check that all entries are preserved
        $this->assertContains('First feature', $content[ChangelogHelper::$identifierUnreleasedHeading]['Added']);
        $this->assertContains('Second feature', $content[ChangelogHelper::$identifierUnreleasedHeading]['Added']);
        $this->assertContains('First bug fix', $content[ChangelogHelper::$identifierUnreleasedHeading]['Fixed']);
        $this->assertContains('Second bug fix', $content[ChangelogHelper::$identifierUnreleasedHeading]['Fixed']);
    });
});

afterAll(function () {
    File::deleteDirectory(base_path('storage/app/test'));
});
