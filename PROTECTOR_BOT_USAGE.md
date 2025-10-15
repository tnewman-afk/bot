# Protector Bot Usage Guide

## Overview

Protector Bot is an anti-vandalism bot for Wikipedia that automatically monitors recent changes and reverts vandalism. It uses multiple detection methods including:

- Rude/offensive language detection
- Humor and joke detection
- Common vandalism keywords
- Suspicious patterns (all caps, character repetition, gibberish)
- Complex heuristics (excessive punctuation, URL spam, email addresses)
- Wikipedia patrol flag integration
- Size-based change analysis

## Quick Start

### Basic Usage

Monitor the last 50 recent changes:
```bash
php protector_bot_main.php
```

### Monitor more changes

Check the last 100 recent changes:
```bash
php protector_bot_main.php --limit=100
```

### Continuous Monitoring

Run the bot continuously with 60-second intervals:
```bash
php protector_bot_main.php --continuous
```

### Patrolled Edits Only

Only check edits that have been marked as patrolled:
```bash
php protector_bot_main.php --patrolled-only
```

### Combined Options

Monitor 200 recent changes continuously:
```bash
php protector_bot_main.php --limit=200 --continuous
```

## How It Works

### Detection Process

1. **Monitor Recent Changes**: The bot queries Wikipedia's recent changes API
2. **Analyze Each Edit**: Each edit is checked against multiple vandalism indicators
3. **Calculate Confidence**: A confidence score (0.0-1.0) is calculated based on detected issues
4. **Flag Vandalism**: Edits with confidence ≥ 0.5 are flagged as vandalism
5. **Auto-Revert**: If ANY edit by a user is vandalism, ALL their edits are reverted
6. **Notify User**: A warning message is posted to the user's talk page

### Vandalism Detection Examples

**Rude Words**: 
- "This article is fucking stupid" → FLAGGED

**Humor/Jokes**:
- "lol this is hilarious haha" → FLAGGED

**All Caps**:
- "THIS IS ALL CAPS SCREAMING!!!" → FLAGGED

**Vandalism Keywords**:
- "This person sucks and is stupid" → FLAGGED

**Multiple Indicators**:
- "lol this fucking article sucks!!!" → HIGH CONFIDENCE

**Legitimate Text**:
- "This is a legitimate encyclopedic article about history." → NOT FLAGGED

### Confidence Scoring

The detection system uses a weighted scoring approach:

- **Rude words**: +0.4 per word (max 3 words counted)
- **Humor indicators**: +0.3 per indicator (max 2 counted)
- **Vandalism keywords**: +0.3 per keyword (max 2 counted)
- **Suspicious patterns**: +0.4 per pattern
- **Additional heuristics**: +0.15 to +0.3 each
- **Patrol flag bonus**: +0.3 if already flagged by Wikipedia

Vandalism threshold: **0.5** (50% confidence)

## Configuration

### Environment Variables

Set up your Wikipedia OAuth credentials in `env.php`:
```php
putenv('PHP_OAUTH_CONSUMER_TOKEN=your_token');
putenv('PHP_OAUTH_CONSUMER_SECRET=your_secret');
putenv('PHP_OAUTH_ACCESS_TOKEN=your_access_token');
putenv('PHP_OAUTH_ACCESS_SECRET=your_access_secret');
```

### Customizing Detection

To modify detection sensitivity, edit `VandalismDetector.php`:

- **Add more words**: Update `RUDE_WORDS`, `HUMOR_INDICATORS`, or `VANDALISM_KEYWORDS` arrays
- **Adjust scoring**: Modify score values in the `detectVandalism()` method
- **Change threshold**: Modify the `0.5` threshold in line 94
- **Add patterns**: Add new regex patterns to `SUSPICIOUS_PATTERNS`

### Customizing Revert Behavior

To modify revert behavior, edit `ProtectorBot.php`:

- **Change threshold**: Modify `MAX_VANDALISM_THRESHOLD` (default: 1)
- **Modify warning message**: Edit the `reportToUserTalk()` method
- **Change monitoring scope**: Adjust API parameters in `monitorRecentChanges()`

## Testing

Run the test suite:
```bash
phpunit tests/phpunit/VandalismDetectorTest.php
```

Test specific detection:
```php
<?php
require_once 'VandalismDetector.php';

$result = VandalismDetector::detectVandalism("test text here");
var_dump($result);
// Output: ['is_vandalism' => bool, 'reasons' => array, 'confidence' => float]
```

## Output Example

```
Protector Bot starting...
Monitor limit: 50
Patrolled only: No
Continuous mode: No

Starting to monitor recent changes (limit: 50)...
Detected vandalism (confidence: 85%): Contains rude/offensive words: fuck; Contains humor/joke indicators: lol
Vandalism detected by user 'Vandal123' on page 'Example Article' (revid: 123456)
Reverting ALL edits by user 'Vandal123' due to vandalism threshold reached
Reverting edit 123456 on page 'Example Article'
Reverted 1 edits by user 'Vandal123'
Posting warning to User talk:Vandal123

=== Monitoring Results ===
Checked: 50 edits
Vandalism detected: 1 edits
Users flagged: 1 users

=== Detailed Statistics ===
Total users with vandalism: 1
Total vandalism instances: 1
  User 'Vandal123': 1 vandalism / 1 total edits

Protector Bot finished.
```

## Limitations & Notes

1. **False Positives**: The bot may occasionally flag legitimate edits. Review warnings regularly.
2. **API Rate Limits**: Respect Wikipedia's API rate limits. Use `--continuous` mode carefully.
3. **Patrol Integration**: Works best with Wikipedia's patrol system enabled.
4. **Main Namespace Only**: Currently only monitors main (article) namespace edits.
5. **Bot Account Required**: Requires a Wikipedia bot account with appropriate permissions.

## Troubleshooting

**"User Not Set" error**: Ensure OAuth credentials are configured in `env.php`

**"Class not found" error**: Run `composer install` to install dependencies

**API errors**: Check your internet connection and Wikipedia API status

**No vandalism detected**: The bot may be working correctly - no vandalism in monitored period

## Legacy Citation Bot Features

The bot still supports legacy citation expansion functionality:
```bash
php process_page.php "Page_Name" --slow --savetofiles
```

See README.md for more details on legacy features.

## Support

For bugs, feature requests, or questions:
- GitHub Issues: https://github.com/tnewman-afk/bot/issues
- Wikipedia Talk Page: https://en.wikipedia.org/wiki/User_talk:Protector_bot

## License

GPL-3.0-or-later (same as original Citation Bot)
