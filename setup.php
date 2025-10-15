<?php

declare(strict_types=1);

error_reporting(E_ALL);

date_default_timezone_set('UTC');
ob_implicit_flush(true);
flush();

/*
 * setup.php sets up the environment for Protector Bot
 */

function bot_debug_log(string $log_this): void {
    if (function_exists('echoable')) {
        if (defined('WIKI_BASE')) {
            $base = WIKI_BASE;
        } else {
            $base = "  ";
        }
        @clearstatcache();
        file_put_contents('ProtectorBot.log', date('Y-m-d H:i:s') . ' :: ' . $base . ' :: ' . $log_this . "\n", FILE_APPEND);
    }
}

// Set up Wikipedia API endpoints
if (isset($_REQUEST["wiki_base"])){
    $wiki_base = trim((string) $_REQUEST["wiki_base"]);
    if (!in_array($wiki_base, ['en', 'simple'], true)) {
        echo '<!DOCTYPE html><html lang="en" dir="ltr"><head><title>Protector Bot: error</title></head><body><h1>Unsupported wiki requested - aborting</h1></body></html>';
        exit;
    }
} else {
    $wiki_base = 'en';
}

define('WIKI_ROOT', 'https://'. $wiki_base . '.wikipedia.org/w/index.php');
define('API_ROOT', 'https://'. $wiki_base . '.wikipedia.org/w/api.php');
define('WIKI_BASE', $wiki_base);
unset($wiki_base);

require_once 'constants.php';

ini_set("user_agent", BOT_USER_AGENT);
include_once './vendor/autoload.php';

define("TRAVIS", (bool) getenv('CI') || defined('__PHPUNIT_PHAR__') || defined('PHPUNIT_COMPOSER_INSTALL') || (strpos((string) @$_SERVER['argv'][0], 'phpunit') !== false));

if (TRAVIS || isset($argv)) {
    define("HTML_OUTPUT", false);
} else {
    define("HTML_OUTPUT", true);
}

define("FLUSHING_OKAY", true);

if (file_exists('env.php')) {
    // Set the environment variables with putenv(). Remember to set permissions (not readable!)
    ob_start();
    /** @psalm-suppress MissingFile */
    include_once 'env.php';
    $env_output = trim(ob_get_contents());
    if ($env_output) {
        bot_debug_log("got this:\n" . $env_output);
    }
    unset($env_output);
    ob_end_clean();
}

if (!mb_internal_encoding('UTF-8') || !mb_regex_encoding('UTF-8')) { /** @phpstan-ignore-line */
    echo 'Unable to set encoding';
    exit;
}

ini_set("memory_limit", "512M");
ini_set("pcre.backtrack_limit", "1425000000");
ini_set("pcre.recursion_limit", "425000000");

function check_blocked(): void {
    if (!WikipediaBot::is_valid_user('Protector_bot')) {
        echo '</pre><div style="text-align:center"><h1>The Protector Bot is currently blocked because of disagreement over its usage.</h1><br/><h1>Or, the bot is not enabled on this wiki fully.</h1><h2><a href="https://en.wikipedia.org/wiki/User_talk:Protector_bot" title="Join the discussion" target="_blank"  aria-label="Join the discussion (opens a new window)">Please join in the discussion</a></h2></div><footer><a href="./" title="Use Protector Bot again">Another&nbsp;page</a>?</footer></body></html>';
        exit;
    }
}

require_once 'user_messages.php';
require_once 'WikipediaBot.php';

if (!TRAVIS) {
    WikipediaBot::make_ch();
}
