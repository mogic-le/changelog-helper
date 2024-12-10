# Changelog-Helper

<p align="center">
  <a href="https://github.com/mogic/changelog-helper/actions"><img src="https://github.com/mogic/changelog-helper/actions/workflows/tests.yml/badge.svg" alt="Build Status" /></a>
  <a href="https://packagist.org/packages/mogic/changelog-helper"><img src="https://img.shields.io/packagist/dt/mogic/changelog-helper.svg" alt="Total Downloads" /></a>
  <a href="https://packagist.org/packages/mogic/changelog-helper"><img src="https://img.shields.io/packagist/v/mogic/changelog-helper.svg?label=stable" alt="Latest Stable Version" /></a>
</p>

Changelog-Helper was created by [Stefan Berger](https://github.com/mogic-le) from [MOGIC](https://www.mogic.com), and is a set of commandline commands to add new CHANGELOG entries and create new releases.

- Built on top of the [Laravel Zero](https://laravel-zero.com) components.

------

## Documentation

### Install

Via Composer

  composer global require mogic/changelog-helper

### Usage of commands

#### Add entry

You can run a one-liner using the `changelog:add` command:

  changelog-helper changelog:add [added,changed,deprecated,fixed,...] This is a new entry line

You can use the same command in a interactive mode:

  changelog-helper changelog:add

#### Add entry based on commits

You can use the same command in a interactive mode:

  changelog-helper changelog:add-commit

#### Add new release

You can use the same command in a interactive mode:

  changelog-helper changelog:release


## License

Changelog-Helper is an open-source software licensed under the MIT license.
