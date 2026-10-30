<?php
/**
 * Car chat and open / closed chats.
 *
 * Car chat: a "Link to chat" 3WordID whose three words are a Dutch licence plate
 * ("1 kbb 00" -> 1KBB00). It can only be turned on when the plate is in the RDW open data
 * register of Dutch vehicles AND the person turning it on is on a Dutch IP address
 * (php/geoip.php). The make and colour from RDW are stored at that moment and shown on the
 * 3WordID's chat page, so people can check they are writing to the right car. RDW is not
 * asked again on every view.
 *
 * Open / closed (column chat_open, for "Link to chat" and "Car chat"):
 *   open   (1)  any 3WordID (and any visitor) can message it, and it can message any 3WordID
 *   closed (0)  both ways only 3WordIDs of the same account: outsiders and visitors can't
 *               message in, and it can't message out to other accounts
 * Defaults: Car chat open, Link to chat closed (column default 0).
 *
 * Settings in login/config.php (all optional, see docs/CAR_CHAT.md):
 *   GEOIP_COUNTRY_DB         path of the country .mmdb file (required for car chat)
 *   GEOIP_TRUSTED_PROXIES    / GEOIP_CLIENT_IP_HEADER  only behind a proxy or Cloudflare
 *   RDW_APP_TOKEN            free RDW (Socrata) app token for higher request limits
 *   RDW_API_URL              default https://opendata.rdw.nl/resource/m9d7-ebf2.json
 *   RDW_TIMEOUT              seconds, default 6
 *
 * Until the SQL (sql/2026-10-30_car_chat.sql) has run, nothing changes: no car chat option,
 * and every chat stays open as before.
 */

require_once __DIR__ . '/geoip.php';

if (!defined('CAR_CACHE_FOUND_TTL')) { define('CAR_CACHE_FOUND_TTL', 900); }      // 15 minutes
if (!defined('CAR_CACHE_NOT_FOUND_TTL')) { define('CAR_CACHE_NOT_FOUND_TTL', 600); } // 10 minutes

/** True when the car chat columns exist on 3wordid. Cached. */
function car_chat_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = false;
    $conn = get_db_conn();
    if (!$conn || $conn->connect_error) {
        return $ready;
    }
    try {
        $r = $conn->query('SELECT car_chat, car_plate, car_make, car_colour, car_checked_at, chat_open FROM 3wordid LIMIT 1');
        $ready = ($r !== false);
    } catch (Throwable $e) {
        $ready = false;
    }
    $conn->close();
    return $ready;
}

/** "1 kbb 00" / "AB-123-C" -> "1KBB00" / "AB123C"; '' when it can't be a Dutch plate. */
function car_plate_from_words(string $words): string
{
    $plate = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $words));
    // Dutch plates have 6 characters, always letters and digits mixed
    if (!preg_match('/^[A-Z0-9]{6}$/', $plate) || !preg_match('/[A-Z]/', $plate) || !preg_match('/[0-9]/', $plate)) {
        return '';
    }
    return $plate;
}

/** Make text from an RDW row: "KIA PICANTO" (brand not repeated when the model name has it). */
function car_make_from_rdw(array $r): string
{
    $merk = trim((string) ($r['merk'] ?? ''));
    $model = trim((string) ($r['handelsbenaming'] ?? ''));
    if ($model !== '' && $merk !== '' && stripos($model, $merk) === 0) {
        return $model;
    }
    return trim($merk . ' ' . $model);
}

function car_cache_file(string $plate): string
{
    $dir = rtrim(sys_get_temp_dir(), '/') . '/3wordid_rdw_cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir . '/' . hash('sha256', 'rdw:' . $plate) . '.json';
}

/**
 * Look a plate up in RDW open data (dataset m9d7-ebf2, "Gekentekende voertuigen").
 * Returns ['status' => 'found'|'not_found'|'error', 'make' => '', 'colour' => ''].
 * Found / not found are cached for a short while; errors (RDW down, timeout) are not.
 */
function car_rdw_lookup(string $plate): array
{
    $none = ['status' => 'error', 'make' => '', 'colour' => ''];
    if ($plate === '') {
        return ['status' => 'not_found', 'make' => '', 'colour' => ''];
    }
    $cache = car_cache_file($plate);
    if (is_file($cache)) {
        $c = json_decode((string) @file_get_contents($cache), true);
        $ttl = (($c['status'] ?? '') === 'found') ? CAR_CACHE_FOUND_TTL : CAR_CACHE_NOT_FOUND_TTL;
        if (is_array($c) && isset($c['status'], $c['t']) && (time() - (int) $c['t']) < $ttl) {
            return ['status' => (string) $c['status'], 'make' => (string) ($c['make'] ?? ''), 'colour' => (string) ($c['colour'] ?? '')];
        }
    }

    $base = defined('RDW_API_URL') ? (string) RDW_API_URL : 'https://opendata.rdw.nl/resource/m9d7-ebf2.json';
    $url = $base . (strpos($base, '?') === false ? '?' : '&') . http_build_query(['kenteken' => $plate]);
    $timeout = defined('RDW_TIMEOUT') ? max(1, (int) RDW_TIMEOUT) : 6;
    $headers = ['Accept: application/json'];
    if (defined('RDW_APP_TOKEN') && (string) RDW_APP_TOKEN !== '') {
        $headers[] = 'X-App-Token: ' . RDW_APP_TOKEN;
    }

    $body = false;
    $code = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => '3WordID car chat',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => implode("\r\n", $headers), 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
    }
    if ($body === false || $code !== 200) {
        error_log('car chat: RDW lookup failed (HTTP ' . $code . ')');
        return $none;
    }
    $rows = json_decode((string) $body, true);
    if (!is_array($rows)) {
        error_log('car chat: RDW answer is not JSON');
        return $none;
    }
    $result = ['status' => 'not_found', 'make' => '', 'colour' => ''];
    foreach ($rows as $r) {
        if (is_array($r) && strtoupper((string) ($r['kenteken'] ?? '')) === $plate) {
            $result = ['status' => 'found', 'make' => mb_substr(car_make_from_rdw($r), 0, 100), 'colour' => mb_substr(trim((string) ($r['eerste_kleur'] ?? '')), 0, 50)];
            break;
        }
    }
    @file_put_contents($cache, json_encode($result + ['t' => time()]), LOCK_EX);
    return $result;
}

/**
 * Can car chat be turned on for these three words, from this request?
 * Returns ['error' => lang key] or ['error' => '', 'plate', 'make', 'colour'].
 * Fails closed: no country database configured = refused.
 */
function car_chat_enable_check(string $words): array
{
    $plate = car_plate_from_words($words);
    if ($plate === '') {
        return ['error' => 'car.err_plate'];
    }
    if (!geoip_ready()) {
        error_log('car chat: GEOIP_COUNTRY_DB is not set up, car chat refused');
        return ['error' => 'car.err_geo_setup'];
    }
    if (geoip_country(geoip_client_ip()) !== 'NL') {
        return ['error' => 'car.err_not_nl'];
    }
    $rdw = car_rdw_lookup($plate);
    if ($rdw['status'] === 'error') {
        return ['error' => 'car.err_rdw_down'];
    }
    if ($rdw['status'] !== 'found') {
        return ['error' => 'car.err_plate'];
    }
    return ['error' => '', 'plate' => $plate, 'make' => $rdw['make'], 'colour' => $rdw['colour']];
}

/** Save car chat on/off with the RDW details (cleared when off). Call only after the owner check. */
function db_car_chat_save(int $wid_id, bool $on, string $plate = '', string $make = '', string $colour = ''): bool
{
    if ($wid_id < 1 || !car_chat_ready()) {
        return false;
    }
    $conn = get_db_conn();
    if (!$conn || $conn->connect_error) {
        return false;
    }
    if ($on) {
        $stmt = $conn->prepare('UPDATE 3wordid SET car_chat = 1, car_plate = ?, car_make = ?, car_colour = ?, car_checked_at = NOW() WHERE id = ?');
        $stmt->bind_param('sssi', $plate, $make, $colour, $wid_id);
    } else {
        $stmt = $conn->prepare('UPDATE 3wordid SET car_chat = 0, car_plate = NULL, car_make = NULL, car_colour = NULL, car_checked_at = NULL WHERE id = ?');
        $stmt->bind_param('i', $wid_id);
    }
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return (bool) $ok;
}

/** Save open (true) or closed (false). Call only after the owner check. */
function db_chat_open_set(int $wid_id, bool $open): bool
{
    if ($wid_id < 1 || !car_chat_ready()) {
        return false;
    }
    $conn = get_db_conn();
    if (!$conn || $conn->connect_error) {
        return false;
    }
    $v = $open ? 1 : 0;
    $stmt = $conn->prepare('UPDATE 3wordid SET chat_open = ? WHERE id = ?');
    $stmt->bind_param('ii', $v, $wid_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return (bool) $ok;
}

/** True for a car chat 3WordID row (from db_3wordid_get / db_3wordid_get_threeword). */
function car_chat_on($row): bool
{
    return is_array($row) && car_chat_ready() && (int) ($row['car_chat'] ?? 0) === 1 && (int) ($row['emailform'] ?? 0) === 1;
}

/** True when this 3WordID is a chat ("Link to chat" or "Car chat") that is closed. */
function chat_is_closed($row): bool
{
    return is_array($row) && car_chat_ready() && (int) ($row['emailform'] ?? 0) === 1
        && array_key_exists('chat_open', $row) && (int) $row['chat_open'] !== 1;
}

/**
 * Open / closed rule for one message. $from = sender 3WordID row, or null for an anonymous
 * visitor; $to = recipient 3WordID row. '' = allowed, else the lang key why not.
 * Closed is closed both ways: only 3WordIDs of the same account.
 */
function chat_access_error($from, $to): string
{
    if (!is_array($to)) {
        return '';
    }
    $same = is_array($from) && (int) ($from['user_id'] ?? 0) > 0 && (int) $from['user_id'] === (int) ($to['user_id'] ?? -1);
    if (chat_is_closed($to) && !$same) {
        return 'chat.err_closed_in';
    }
    if (is_array($from) && chat_is_closed($from) && !$same) {
        return 'chat.err_closed_out';
    }
    return '';
}

/** RDW colour (Dutch, e.g. "WIT") in the current language; unknown values as RDW wrote them. */
function car_colour_label(string $colour): string
{
    $k = 'car.colour_' . strtolower((string) preg_replace('/[^A-Za-z]/', '', $colour));
    $txt = t_plain($k);
    return ($txt === $k || $txt === '') ? $colour : $txt;
}

/** "Kia Picanto, white" style line for the chat page (plain text). */
function car_summary($row): string
{
    $make = trim((string) ($row['car_make'] ?? ''));
    $colour = trim((string) ($row['car_colour'] ?? ''));
    $parts = [];
    if ($make !== '') { $parts[] = $make; }
    if ($colour !== '') { $parts[] = car_colour_label($colour); }
    return implode(' · ', $parts);
}

/** Small car icon (inline SVG). */
function car_icon(int $size = 18): string
{
    return '<svg class="car-ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="M5 17H3v-5l2-5h14l2 5v5h-2"/><path d="M3 12h18"/><circle cx="7.5" cy="17" r="2"/><circle cx="16.5" cy="17" r="2"/><path d="M9.5 17h5"/></svg>';
}
