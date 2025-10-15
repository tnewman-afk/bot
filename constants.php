<?php

declare(strict_types=1);

/**
 * Constants for Protector Bot - Anti-Vandalism System
 */

const COMMONUSERNAME = 'protector@tools.wmflabs.org';

const BOT_USER_AGENT = "Mozilla/5.0 (compatible; Protector_bot; mailto:".COMMONUSERNAME."; +https://en.wikipedia.org/wiki/User:Protector_bot)";

const BOT_HTTP_TIMEOUT = 20;
const BOT_CONNECTION_TIMEOUT = 10;

function curl_limit_page_size(CurlHandle $_ch, int $_DE = 0, int $down = 0, int $_UE = 0, int $_Up = 0): int {
    // Limit downloads to reasonable sizes
    if ($down > 134217728) { // 128MB
         bot_debug_log("Absurdly large curl download");
         return 1;
    }
    return 0;
}

/** @param array<int, int|string|bool|array<int, string>> $ops */
function bot_curl_init(float $time, array $ops): CurlHandle {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_BUFFERSIZE => 524288, // 512kB chunks
        CURLOPT_MAXREDIRS => 20,
        CURLOPT_USERAGENT => BOT_USER_AGENT,
        CURLOPT_AUTOREFERER => "1",
        CURLOPT_REFERER => "https://en.wikipedia.org",
        CURLOPT_COOKIESESSION => "1",
        CURLOPT_RETURNTRANSFER => "1",
        CURLOPT_HEADEROPT => CURLHEADER_UNIFIED,
        CURLOPT_PROGRESSFUNCTION => 'curl_limit_page_size',
        CURLOPT_NOPROGRESS => "0",
        CURLOPT_TIMEOUT => BOT_HTTP_TIMEOUT * $time,
        CURLOPT_CONNECTTIMEOUT => BOT_CONNECTION_TIMEOUT * $time,
    ]);
    curl_setopt_array($ch, $ops);
    return $ch;
}
