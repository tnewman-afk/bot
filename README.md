[![Build Status](https://github.com/ms609/citation-bot/actions/workflows/test-suite.yml/badge.svg)](https://github.com/ms609/citation-bot/actions/workflows/test-suite.yml)
[![Build Status](https://github.com/ms609/citation-bot/actions/workflows/codeql-analysis.yml/badge.svg)](https://github.com/ms609/citation-bot/actions/workflows/codeql-analysis.yml)
[![Build Status](https://github.com/ms609/citation-bot/actions/workflows/php-static.yml/badge.svg)](https://github.com/ms609/citation-bot/actions/workflows/php-static.yml)
[![Build Status](https://github.com/ms609/citation-bot/actions/workflows/php-static2.yml/badge.svg)](https://github.com/ms609/citation-bot/actions/workflows/php-static2.yml)
[![Build Status](https://github.com/ms609/citation-bot/actions/workflows/php-security.yml/badge.svg)](https://github.com/ms609/citation-bot/actions/workflows/php-security.yml)
[![codecov](https://codecov.io/gh/ms609/citation-bot/branch/master/graph/badge.svg)](https://codecov.io/gh/ms609/citation-bot)
[![Project Status: Inactive - The project has reached a stable, usable state but is no longer being actively developed; support/maintenance will be provided as time allows.](https://www.repostatus.org/badges/latest/inactive.svg)](https://www.repostatus.org/#inactive)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP ](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://www.php.net)
[![GitHub issues](https://img.shields.io/github/issues/ms609/citation-bot.png)](https://github.com/ms609/citation-bot/issues)


# Protector Bot

## Anti-Vandalism Bot for Wikipedia

Protector Bot is a comprehensive anti-vandalism bot for Wikipedia that automatically monitors recent changes and reverts vandalism using multiple detection methods.

## Overview

This bot monitors Wikipedia for vandalism using multiple detection methods:

* **Rude/Offensive Words Detection**: Identifies inappropriate language (23+ terms)
* **Humor/Joke Detection**: Catches non-encyclopedic humor and memes (20+ patterns)
* **Vandalism Keyword Detection**: Recognizes common vandalism patterns (16+ keywords)
* **Complex Heuristics**: Advanced checks for suspicious patterns, gibberish, excessive punctuation, spam, and other indicators
* **Wikipedia Patrol Flags**: Integrates with Wikipedia's patrol system to enhance detection
* **Automatic Reversion**: Automatically reverts ALL edits by a user if one is determined to be vandalism (threshold: 1 edit)

## Main Components

* `ProtectorBot.php`: Main anti-vandalism engine that monitors recent changes
* `VandalismDetector.php`: Vandalism detection algorithms and heuristics
* `protector_bot_main.php`: Entry point for running the bot
* `WikipediaBot.php`: Core Wikipedia API interaction functions
* `constants.php`: Configuration constants
* `setup.php`: Environment setup

## Features

- **8 Detection Methods**: Multi-layered approach for high accuracy
- **Confidence Scoring**: Weighted system (0.0-1.0) with 0.5 threshold
- **Aggressive Protection**: Reverts ALL edits if ANY vandalism detected
- **User Notifications**: Automatic warnings posted to vandal talk pages
- **Statistics Tracking**: Detailed monitoring and reporting
- **Continuous Mode**: Can run continuously with configurable intervals

Bugs and feature requests: https://en.wikipedia.org/wiki/User_talk:Protector_bot

## Structure

Basic structure of a Citation bot script:
* the `env.php` that defines configuration constants (you can create it from `env.php.example`)
* the `setup.php` that sets up the functions needed (usually, you don't need to modify this file)
* the Page functions to fetch/expand/post the page's text


A quick tour of the main files:
* `constants.php`: constants defined
* `WikipediaBot.php`: functions to facilitate HTTP access to the Wikipedia API.
* `NameTools.php`: defines name functions
* `setup.php`: sets up needed functions, requires most of the other files listed here
* `expandFns.php`: a variety of functions
* `apiFunctions.php`: sets up needed functions for expanding pmid/doi/etc
* `Zotero.php`: URL expansion related functions organized in a static class 

Class files:
* `Page.php`: Represents an individual page to expand citations on. Key methods are
  `Page::get_text_from()`, `Page::expand_text()`, and `Page::write()`.
* `Template.php`: most of the actual expansion happens here.
  `Template::add_if_new()` is generally (but not always) used to add
   parameters to the updated template; `Template::tidy()` cleans up the
   template, but may add parameters as well and have side effects.
* `Comment.php`: Handles comments, nokwiki, etc. tags
* `Parameter.php`: contains information about template parameter names, values,
   and metadata, and methods to parse template parameters.

## Style and structure notes

Constants and definitions should be provided in `constants.php`.
Classes should be in individual files. The code is generally written densely. 
Beware assignments in conditionals, one-line `if`/`foreach`/`else` statements, 
and action taking place through method calls that take place in assignments or equality checks. 
Also beware the difference between `else if` and `elseif`.

## Deployment

The bot requires PHP >= 8.2.

To run the bot from a new environment, you will need to create an `env.php` file (if one doesn't already exist) that sets the needed authentication tokens as environment variables. To do this, you can rename `env.php.example` to `env.php`, set the variables in the file, and then make sure the file is not world readable or writable:

    chmod o-rwx env.php

 To run the bot as a webservice from WM Toolforge:

    become protector[-dev]
    webservice stop
    webservice --backend=kubernetes php8.2 start

Or for testing in the shell:

    webservice --backend=kubernetes php8.2 shell

Before entering the k8s shell, it may be necessary to install phpunit (as wget is not available in the k8s shell).

## Running the Bot

To run the Protector Bot for anti-vandalism monitoring:

    /usr/bin/php ./protector_bot_main.php --limit=50

Command line parameters:
* `--limit=N` - Number of recent changes to check (default: 50)
* `--patrolled-only` - Only check patrolled edits
* `--continuous` - Run continuously in monitoring mode
* `--help` - Show help message

Examples:
```bash
# Monitor 50 recent changes (one-time)
php protector_bot_main.php

# Monitor 100 changes continuously
php protector_bot_main.php --limit=100 --continuous

# Monitor only patrolled edits
php protector_bot_main.php --patrolled-only
```
