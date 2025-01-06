<?php

return [
    'package_name' => env('APP_NAME', 'Changelog-Helper'),
    'path' => getcwd(),
    'filename' => 'CHANGELOG.md',
    'template' => 'Templates/CHANGELOG.md',
    'version_prefix' => env('CHANGELOG_VERSION_PREFIX', 'v'),
    'links' => [
        'unreleased_link' => env('CHANGELOG_UNRELEASED_LINK', ''),
        'main_branch' => env('CHANGELOG_LINK_MAIN_BRANCH', 'main'),
        'develop_branch' => env('CHANGELOG_LINK_DEVELOP_BRANCH', 'develop'),
    ],
    'release_message' => env('CHANGELOG_RELEASE_MESSAGE', 'Release version {version}'),
];
