# Changelog-Helper

Changelog-Helper is a set of commandline utilities for adding and releasing new CHANGELOG.md entries as defined by [keepachangelog.com](https://keepachangelog.com) definition.

It helps with the creation of correct entries, version numbers, tags and the table of contents.

<p align="center">
  <a href="https://github.com/mogic-le/changelog-helper/actions/workflows/static.yml"><img src="https://github.com/mogic-le/changelog-helper/actions/workflows/static.yml/badge.svg" alt="Static Analysis" /></a>
  <a href="https://github.com/mogic-le/changelog-helper/actions/workflows/pest-tests.yml"><img src="https://github.com/mogic-le/changelog-helper/actions/workflows/pest-tests.yml/badge.svg" alt="Tests" /></a>
  <a href="https://packagist.org/packages/mogic/changelog-helper"><img src="https://img.shields.io/packagist/dt/mogic/changelog-helper.svg" alt="Total Downloads" /></a>
  <a href="https://packagist.org/packages/mogic/changelog-helper"><img src="https://img.shields.io/packagist/v/mogic/changelog-helper.svg?label=stable" alt="Latest Stable Version" /></a>
</p>

------

## Documentation

The helper is built on top of the [Laravel Zero](https://laravel-zero.com) components and uses git as an internal command.

### Install

Via Composer

    composer global require mogic/changelog-helper

To get a link index in your CHANGELOG, you have to set a env variable. Add it in your .env or set it global:

    CHANGELOG_UNRELEASED_LINK=https://github.com/mogic-le/changelog-helper/compare/develop...main

### Usage of commands

#### Add entry

You can run a one-liner using the `add` command:

    changelog-helper add [added,changed,deprecated,fixed,...] This is a new entry line

Or you can use the same command in a interactive mode:

    changelog-helper add

#### Add entry based on commits

You can use the same command in a interactive mode:

    changelog-helper add-commit

#### Add new release

The release command creates a new release, based on your optional unreleased changes.

Optional: it commits the CHANGELOG.md changes and creates a tag on top of the last commit.

You can use run a one-liner:

    changelog-helper release [major|minor|patch] 1|0

Or you can use the same command in a interactive mode:

    changelog-helper release

### Environmet variables

* CHANGELOG_RELEASE_MESSAGE

## Build & release new version

To build a new release version, we have to create the build with the release tag to create after.

    ./changelog-helper app:build changelog-helper --build-version=1.x.x
    git add ./builds/changelog-helper
    ./changelog-helper add added Added new release build
    git add ./builds/changelog-helper
    git add ./CHANGELOG.md
    ./changelog-helper release minor 1.x.x
    git push && git git push --tags

## Author

Changelog-Helper was created by [Stefan Berger](https://github.com/bergo) from [MOGIC](https://www.mogic.com).

## License

Changelog-Helper is an open-source software licensed under the MIT license.

## Tests

run `./vendor/bin/pest`
