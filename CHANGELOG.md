# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Add SQLite support - 2024-10-05

### Added

- SQLite support

## 0.5.0 - 2024-03-14

### Added

- Laravel 11 support by @mtvenD in #30

## 0.4.1 - 2023-12-19

### Fixed

- `getPathSet` method by @illambo in #27

## Add MySQL support - 2023-05-19

### Added

- MySQL support

### Changed

- Rename model method "isMoving" to "isParentChanging"
- Rename model method "wasMoved" to "isParentChanged"
- Refactoring

## 0.3.2 - 2023-02-19

### Changed

- Assigning `path` no longer updates timestamps and dispatches events.
- Small refactoring

## 0.3.1 - 2023-02-18

### Added

- Support for Laravel 10

## 0.3.0 - 2023-01-15

### Added

- Added support for PHP 8.2

### Changed

- Renamed `whereAncestor` to `whereSelfOrAncestor`
- Renamed `whereDescendant` to `whereSelfOrDescendant`
- Bump minimal Laravel version to 8.79
- Small refactoring

## 0.2.1 - 2023-01-10

### Added

- Added possibility to use a custom operator with `whereDepth` method

## 0.2.0 - 2023-01-09

#### Added

- Added possibility to use custom column as path source

#### Changed

- Now migrations can be published

## 0.1.1 - 2023-01-09

### Added

- Added `isAncestorOf` method
- Added `isDescendantOf` method

### Changed

- Refactored relations

## 0.1.0 - 2023-01-08

### Added

- Initial release
