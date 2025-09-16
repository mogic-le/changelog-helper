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

    it('handles changelog with missing subheaders and assigns unassigned bullet points to Changed', function () {
        // Create a changelog with bullet points but no subheaders
        $path = ChangelogHelper::path();
        $malformedChangelog = "# Changelog

All notable changes to Changelog-Helper will be documented in this file.

## [unreleased]

- First unassigned bullet point
- Second unassigned bullet point

## [1.0.0] - 2024-01-01

- Another unassigned bullet point
- Yet another unassigned bullet point

### Added

- Properly categorized addition

";
        File::put($path, $malformedChangelog);

        $content = ChangelogHelper::parse();

        // Check that unassigned bullet points in unreleased are assigned to 'Changed'
        $this->assertArrayHasKey('Changed', $content[ChangelogHelper::$identifierUnreleasedHeading]);
        $this->assertContains('First unassigned bullet point', $content[ChangelogHelper::$identifierUnreleasedHeading]['Changed']);
        $this->assertContains('Second unassigned bullet point', $content[ChangelogHelper::$identifierUnreleasedHeading]['Changed']);

        // Check that the released version also has unassigned items in 'Changed'
        $releaseKey = '## [1.0.0] - 2024-01-01';
        $this->assertArrayHasKey($releaseKey, $content);
        $this->assertArrayHasKey('Changed', $content[$releaseKey]);
        $this->assertContains('Another unassigned bullet point', $content[$releaseKey]['Changed']);
        $this->assertContains('Yet another unassigned bullet point', $content[$releaseKey]['Changed']);

        // Check that properly categorized items are still in their correct category
        $this->assertArrayHasKey('Added', $content[$releaseKey]);
        $this->assertContains('Properly categorized addition', $content[$releaseKey]['Added']);
    });

    it('handles mixed content with empty lines and whitespace', function () {
        // Create a changelog with various edge cases
        $path = ChangelogHelper::path();
        $edgeCaseChangelog = "# Changelog

All notable changes to Changelog-Helper will be documented in this file.

## [unreleased]

- First bullet point

- Second bullet point with extra spacing

### Added

- Properly categorized item

## [2.0.0] - 2024-02-01

   - Bullet with leading spaces
- Normal bullet point

### Changed

- Mixed with proper category

";
        File::put($path, $edgeCaseChangelog);

        $content = ChangelogHelper::parse();

        // Check unreleased section
        $this->assertArrayHasKey('Changed', $content[ChangelogHelper::$identifierUnreleasedHeading]);
        $this->assertContains('First bullet point', $content[ChangelogHelper::$identifierUnreleasedHeading]['Changed']);
        $this->assertContains('Second bullet point with extra spacing', $content[ChangelogHelper::$identifierUnreleasedHeading]['Changed']);

        $this->assertArrayHasKey('Added', $content[ChangelogHelper::$identifierUnreleasedHeading]);
        $this->assertContains('Properly categorized item', $content[ChangelogHelper::$identifierUnreleasedHeading]['Added']);

        // Check release section
        $releaseKey = '## [2.0.0] - 2024-02-01';
        $this->assertArrayHasKey($releaseKey, $content);
        $this->assertArrayHasKey('Changed', $content[$releaseKey]);
        $this->assertContains('Bullet with leading spaces', $content[$releaseKey]['Changed']);
        $this->assertContains('Normal bullet point', $content[$releaseKey]['Changed']);
        $this->assertContains('Mixed with proper category', $content[$releaseKey]['Changed']);
    });

    it('handles versions without dates', function () {
        // Create a changelog with versions that have no dates
        $path = ChangelogHelper::path();
        $changelogWithoutDates = "# Changelog

All notable changes to test-project will be documented in this file.

## [unreleased]

- Unreleased feature

## [2.0.0]

### Added

- New feature without date

## [1.5.0] - 2024-01-15

### Fixed

- Bug fix with date

## [1.0.0]

- Another version without date

";
        File::put($path, $changelogWithoutDates);

        $content = ChangelogHelper::parse();

        // Check that versions without dates are still parsed
        $this->assertArrayHasKey('## [2.0.0]', $content);
        $this->assertArrayHasKey('Added', $content['## [2.0.0]']);
        $this->assertContains('New feature without date', $content['## [2.0.0]']['Added']);

        $this->assertArrayHasKey('## [1.0.0]', $content);
        $this->assertArrayHasKey('Changed', $content['## [1.0.0]']);
        $this->assertContains('Another version without date', $content['## [1.0.0]']['Changed']);

        // Check that versions with dates still work
        $this->assertArrayHasKey('## [1.5.0] - 2024-01-15', $content);
        $this->assertArrayHasKey('Fixed', $content['## [1.5.0] - 2024-01-15']);
        $this->assertContains('Bug fix with date', $content['## [1.5.0] - 2024-01-15']['Fixed']);
    });

    it('getLatestVersion works with versions without dates', function () {
        // Create a changelog with mixed version formats
        $path = ChangelogHelper::path();
        $mixedVersions = "# Changelog

All notable changes to test-project will be documented in this file.

## [unreleased]

- Unreleased feature

## [3.0.0]

### Added

- Latest version without date

## [2.5.0] - 2024-02-01

### Fixed

- Version with date

## [2.0.0]

### Changed

- Older version without date

";
        File::put($path, $mixedVersions);

        $latestVersion = ChangelogHelper::getLatestVersion();

        // Should return the highest version number regardless of date format
        $this->assertEquals('3.0.0', $latestVersion);
    });

    it('finds changelog in parent directories up to git root', function () {
        // Create a temporary directory structure with a git repo
        $tempDir = sys_get_temp_dir() . '/changelog_test_' . uniqid();
        $subDir = $tempDir . '/subdir/nested';

        // Create directory structure
        mkdir($subDir, 0755, true);

        // Create a .git directory in the root
        mkdir($tempDir . '/.git', 0755, true);

        // Create a CHANGELOG.md in the root
        $rootChangelog = $tempDir . '/CHANGELOG.md';
        file_put_contents($rootChangelog, "# Changelog\n\n## [unreleased]\n\n- Root changelog\n");

        // Change to subdirectory and temporarily override config
        $originalCwd = getcwd();
        $originalFilename = config('changelog.filename');

        chdir($subDir);
        config()->set('changelog.filename', 'CHANGELOG.md');

        try {
            // The path method should find the root changelog, not create a new one in subdir
            $foundPath = ChangelogHelper::findChangelogPath();

            $this->assertEquals(realpath($rootChangelog), realpath($foundPath));
            $this->assertTrue(file_exists($rootChangelog));
            $this->assertFalse(file_exists($subDir . '/CHANGELOG.md'));

        } finally {
            // Restore original directory and config
            chdir($originalCwd);
            config()->set('changelog.filename', $originalFilename);

            // Clean up
            deleteDirectory($tempDir);
        }
    });

    it('falls back to config path when no git root found', function () {
        // Create a temporary directory structure WITHOUT a git repo
        $tempDir = sys_get_temp_dir() . '/changelog_test_no_git_' . uniqid();
        $subDir = $tempDir . '/subdir/nested';

        // Create directory structure (no .git directory)
        mkdir($subDir, 0755, true);

        // Change to subdirectory and temporarily override config
        $originalCwd = getcwd();
        $originalFilename = config('changelog.filename');
        $originalPath = config('changelog.path');

        chdir($subDir);
        config()->set('changelog.filename', 'CHANGELOG.md');
        config()->set('changelog.path', $tempDir);

        try {
            // Should fall back to config path since no git root found
            $foundPath = ChangelogHelper::findChangelogPath();
            $expectedPath = $tempDir . '/CHANGELOG.md';

            $this->assertEquals($expectedPath, $foundPath);

        } finally {
            // Restore original directory and config
            chdir($originalCwd);
            config()->set('changelog.filename', $originalFilename);
            config()->set('changelog.path', $originalPath);

            // Clean up
            deleteDirectory($tempDir);
        }
    });
});

// Helper function to recursively delete directories
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

afterAll(function () {
    File::deleteDirectory(base_path('storage/app/test'));
});
