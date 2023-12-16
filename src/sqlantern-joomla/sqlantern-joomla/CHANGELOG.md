# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [1.9.0 beta] - 2023-12-16

### Fixed
- Initial Table data panels' width has been calculated improperly (JavaScript).

### Changed
- The version - 0.9.42 beta becomes the initial public release: 1.9.0 beta

## [0.9.42 beta] - 2023-12-15

### Fixed
- The "Switch screen" pop-up panel reordering bug was not fully eliminated the last time. It is now (hopefully).
- If the only thing in the tab was an error, it looked buggy (it wasn't, but it looked like that), not anymore.
- Import progress style (it looked broken).
- mysqli: A workaround for strange situation when some servers return "SELECT table_rows" as "table_rows", but others return mix of "TABLE_ROWS" and "table_rows" (different in different rows, I swear). And I was trying to read "table_rows", which failed with "TABLE_ROWS". Strange.
- PHP: A lot of place that could throw a Notice or Warning (like "Undefined array key") are rewritten. Hopefully, all of them, but I don't know yet.
- pgsql: Password containing `'` and `\` can now be used (they were a problem before). Hopefully, there are no more characters needing special care.
- pgsql: Cleaned the code a bit (removed OpenCart code, links to Joomla; moved it to GitHub).

### Changed
- `SQL_SET_CHARSET` was removed, replaced by per-driver values: `SQL_MYSQLI_CHARSET` and `SQL_POSTGRES_CHARSET`.
- Non-critical CSS changes.

## [0.9.41 beta] - 2023-12-13

### Fixed
- Fix for Firefox still running program code after visibilityChange to "hidden" when refreshing page. Turned out, it was basically impossible to refresh the page in Firefox, we're such great testers!
- There was a bug in "Switch screen" pop-up when reordering panels (rogue "click" happening where we didn't expect it to).
- Small less important JavaSript fixes.

### Changed
- `SQL_POSTGRE_CONNECTION_DATABASE` constant renamed to `SQL_POSTGRES_CONNECTION_DATABASE` (POSTGRE changed to POSTGRES, IDK why I had used "Postgre").

## [0.9.40 beta] - 2023-12-09

### Fixed
- pgsql: Refactoring of `sqlRunQuery` - COUNT is improved to display an error without the COUNT and subquery (like in mysqli now), TRUNCATE did not show anything at all previously (it's "state: executed" now, similar to mysqli), BLOB/BINARY detection is now written in the same way as mysqli (but not tested, because we have no such data at hand).

### Changed
- A tiny translation change.
- Smal CSS improvements.

## [0.9.39 beta] - 2023-12-08

### Fixed
- Keep-alive had a debug timeout of 3 seconds instead of 30 seconds, depr.
- Small JavaScript refactoring and non-critical bug fixes.
- The temprorary fake favicon in HTML head is replaced with the correct one.

### Added
- Hints for the new "previous screen" and "next screen" arrows.

### Changed
- mysqli: `SELECT`s are now read row by row, to run out of RAM in PHP instead of effecting the whole server, if the response is too big to handle. It also happens way faster this way, in a matter of seconds instead of minutes.
- mysqli: `mb_detect_encoding` no longer use to detect BLOB/BINARY, it's way too slow on long data (which binary often is). I just try to run `json_encode` now, and it is four times faster than `mb_detect_encoding`. A proper type check should be introduces one day.
