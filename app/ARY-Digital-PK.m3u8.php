<?php
/**
 * Combined Live TV M3U8 Generator
 * Location: /channels/thetvapp/live.m3u8.php
 * Serves a complete M3U8 playlist with all working channels
 */

header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: inline; filename="live-tv.m3u8"');
header('Cache-Control: max-age=3600, public');
header('Access-Control-Allow-Origin: *');

// Start M3U8
echo "#EXTM3U\n";

// ============================================
// ARY Digital - Auto-extracting token
// ============================================
$ary_url = getAryDigitalUrl();
if ($ary_url) {
    echo '#EXTINF:-1 tvg-name="ARY Digital" tvg-id="ARYDigital.pk" tvg-language="Urdu" tvg-country="PK" tvg-logo="https://upload.wikimedia.org/wikipedia/en/4/4b/ARY_Digital.png" group-title="Entertainment",ARY Digital' . "\n";
    echo '#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36' . "\n";
    echo '#EXTVLCOPT:http-referrer=https://live.arydigital.tv/' . "\n";
    echo '#EXTVLCOPT:http-origin=https://live.arydigital.tv' . "\n";
    echo $ary_url . "\n\n";
}

// ============================================
// Add more channels here as you find them
// ============================================

/**
 * Get ARY Digital URL from cache or fresh extraction
 */
function getAryDigitalUrl() {
    $cache_file = __DIR__ . '/cookie/ary_stream_cache.json';
    
    // Try cache first (valid for 2 hours)
    if (file_exists($cache_file)) {
        $cache = json_decode(file_get_contents($cache_file), true);
        if ($cache && isset($cache['url']) && (time() - $cache['timestamp']) < 7200) {
            return $cache['url'];
        }
    }
    
    // Fetch fresh URL
    $url = extractAryStreamUrl();
    if ($url) {
        file_put_contents($cache_file, json_encode([
            'url' => $url,
            'timestamp' => time()
        ]));
        return $url;
    }
    
    // Fallback to old cache
    if (file_exists($cache_file)) {
        $cache = json_decode(file_get_contents($cache_file), true);
        if ($cache && isset($cache['url'])) {
            return $cache['url'];
        }
    }
    
    return false;
}

/**
 * Extract fresh ARY Digital stream URL
 */
function extractAryStreamUrl() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://live.arydigital.tv/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-US,en;q=0.9',
            'Referer: https://live.arydigital.tv/',
        ],
    ]);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code != 200) return false;
    
    // Extract URL without uuid parameter (cleaner)
    if (preg_match('/https:\/\/arydigital\.aryzap\.com\/[a-f0-9]+\/[a-f0-9]+\/v1\/[a-f0-9]+\/[a-f0-9]+\/main\.m3u8/', $html, $matches)) {
        return $matches[0];
    }
    
    return false;
}