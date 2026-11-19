<?php
/*
 * Bumperchat (bumperchat.com -> https://www.3wordid.com/bumperchat/index.php)
 * ---------------------------------------------------------------------------
 * A second front door for 3WordID: licence plates as three words, and chat only.
 *   bumperchat/index.php          search box, onboarding steps, plate -> three words lookup
 *   bumperchat/claim.php          step 1: choose country, show that country's plate rules
 *   bumperchat/claim_plate.php    step 2: enter the plate, validate, check if the words are free
 *   bumperchat/claim_register.php step 3: register (email + optional mobile); skipped if logged in
 *   bumperchat/login.php          top-right email login (no login form inside the claim flow)
 *   bumperchat/chat.php           the chats of a Bumperchat user (no list / admin pages)
 *
 * No new database columns: a Bumperchat login is marked in the session only
 * ($_SESSION['bumperchat'] = ['words' => the three words they came for, 'at' => time]).
 * With that flag 3wid_list.php sends the user to bumperchat/chat.php. Every normal login
 * (establish_user_session) removes the flag again. See docs/BUMPERCHAT.md.
 */

/** The plate rules (bumperchat/plate_formats.json), cached per request. */
function bumperchat_formats(): array
{
    static $data = null;
    if ($data === null) {
        $raw = @file_get_contents(dirname(__DIR__) . '/bumperchat/plate_formats.json');
        $data = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
        if (!isset($data['countries']) || !is_array($data['countries'])) {
            $data['countries'] = [];
        }
    }
    return $data;
}

/** Text in the current language from a {"en": ..., "nl": ...} object (English fallback). */
function bumperchat_text($obj): string
{
    if (!is_array($obj)) {
        return (string) $obj;
    }
    $lang = function_exists('current_lang') ? current_lang() : 'en';
    return (string) ($obj[$lang] ?? $obj['en'] ?? '');
}

/** " AB 123  C " -> "ab 123 c". Only spaces separate the words; returns an empty string when it is not 3 words of letters/digits. */
function bumperchat_normalize_words(string $words): string
{
    $words = strtolower(trim(preg_replace('/\s+/u', ' ', substr($words, 0, 200))));
    if (strlen($words) > 80 || !preg_match('/^[a-z0-9]+ [a-z0-9]+ [a-z0-9]+$/', $words)) {
        return '';
    }
    return $words;
}

/** Mark this (just established) login as a Bumperchat login. Call AFTER establish_user_session(). */
function bumperchat_mark_session(string $words): void
{
    $_SESSION['bumperchat'] = ['words' => $words, 'at' => time()];
}

function bumperchat_session_words(): string
{
    return (string) ($_SESSION['bumperchat']['words'] ?? '');
}

/** Small car mark used in the Bumperchat header. */
function bumperchat_car_icon(int $size = 20): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . '<path d="M5 17H3v-5l2-5h14l2 5v5h-2"></path><path d="M3 12h18"></path><circle cx="7.5" cy="17" r="2"></circle><circle cx="16.5" cy="17" r="2"></circle><path d="M9.5 17h5"></path></svg>';
}

/** Header of every Bumperchat page. $user: logged-in user or null. */
function bumperchat_header($user): string
{
    $out = '<header class="site-header bc-header">'
        . '<a class="brand bc-brand" href="index.php"><span class="brand-mark bc-mark">' . bumperchat_car_icon(18) . '</span>'
        . '<span>Bumperchat<span class="bc-by">' . t('bc.by_3wordid') . '</span></span></a>'
        . '<div class="header-actions">' . lang_switcher();
    if ($user) {
        $out .= '<a class="btn btn-ghost bc-hide-sm" href="chat.php">' . t('bc.my_chats') . '</a>'
            . '<a class="btn btn-primary" href="logout.php">' . t('bc.logout') . '</a>';
    } else {
        $next = bumperchat_current_return();
        $href = 'login.php';
        if ($next !== '' && $next !== 'login.php') {
            $href .= '?next=' . rawurlencode($next);
        }
        $out .= '<a class="btn btn-primary" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . t('nav.login') . '</a>';
    }
    return $out . '</div></header>';
}

/** Optional mobile number + SMS consent fields for the claim form. */
function bumperchat_notify_fields(string $phone_value = '', bool $consent = false): string
{
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $checked = $consent ? ' checked' : '';
    return '<label class="bc-label" for="notify_phone">' . t('bc.phone_label') . '</label>'
        . '<input id="notify_phone" name="notify_phone" class="bc-input" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="' . $e(t('bc.phone_ph')) . '" value="' . $e($phone_value) . '">'
        . '<p class="bc-meta">' . t('bc.phone_hint') . '</p>'
        . '<label class="bc-check-row">'
        . '<input type="checkbox" name="notify_sms_consent" value="1"' . $checked . '>'
        . '<span>' . t_raw('bc.sms_consent') . '</span></label>';
}

/** Upper-case letter/digit groups of a typed plate (spaces, dashes and dots separate). */
function bumperchat_plate_groups(string $plate): array
{
    $parts = preg_split('/[^A-Z0-9]+/', strtoupper($plate), -1, PREG_SPLIT_NO_EMPTY);
    return is_array($parts) ? $parts : [];
}

/** Split a compact plate where letters and digits change: "AB123C" -> ["AB","123","C"]. */
function bumperchat_plate_runs(string $s): array
{
    preg_match_all('/[A-Z]+|[0-9]+/', $s, $m);
    return $m[0] ?? [];
}

function bumperchat_fixed_split(string $compact, array $lengths): ?array
{
    $total = 0;
    foreach ($lengths as $n) {
        $total += (int) $n;
    }
    if (strlen($compact) !== $total) {
        return null;
    }
    $out = [];
    $at = 0;
    foreach ($lengths as $n) {
        $n = (int) $n;
        $out[] = substr($compact, $at, $n);
        $at += $n;
    }
    return $out;
}

/**
 * Same rules as js/bumperchat.js plateToWords(): one country entry from
 * plate_formats.json, the typed plate, and (for US/CA) the region code.
 * Returns the three words (lower case, spaces) or '' when the plate does not fit.
 */
function bumperchat_plate_to_words(array $entry, string $plate, string $region = ''): string
{
    $g = bumperchat_plate_groups($plate);
    $compact = implode('', $g);
    $strip = strtolower((string) ($entry['strip'] ?? ''));
    $out = null;
    if ($compact === '' || strlen($compact) > 20) {
        return '';
    }

    $mode = (string) ($entry['mode'] ?? '');
    if ($mode === 'three') {
        if (count($g) === 3) {
            $out = $g;
        } elseif (!empty($entry['fixed']) && is_array($entry['fixed'])) {
            $out = bumperchat_fixed_split($compact, $entry['fixed']);
        }
        if (!$out) {
            $r = bumperchat_plate_runs($compact);
            if (count($r) === 3) {
                $out = $r;
            }
        }
    } elseif ($mode === 'mixed') {
        if (count($g) === 3) {
            $out = $g;
        } else {
            $r2 = bumperchat_plate_runs($compact);
            if (count($r2) === 3) {
                $out = $r2;
            } elseif (count($r2) === 2) {
                $out = array_merge([$strip], $r2);
            } elseif (count($g) === 2) {
                $out = array_merge([$strip], $g);
            }
        }
    } elseif ($mode === 'prefix') {
        $r3 = bumperchat_plate_runs($compact);
        if (count($g) === 3 && !empty($entry['three_ok'])) {
            $out = $g;
        } elseif (count($r3) === 2) {
            $out = array_merge([$strip], $r3);
        } elseif (count($g) === 2) {
            $out = array_merge([$strip], $g);
        } elseif (count($g) === 1 && strlen($compact) >= 3 && preg_match('/^[0-9]+$/', $compact)) {
            $out = [$strip, substr($compact, 0, 2), substr($compact, 2)];
        }
    } elseif ($mode === 'region') {
        $reg = strtoupper($region);
        if (empty($entry['regions']) || !is_array($entry['regions']) || !isset($entry['regions'][$reg])) {
            return '';
        }
        if (count($g) === 2) {
            $out = array_merge([$reg], $g);
        } elseif (count($g) === 1) {
            $out = [$strip, $reg, $g[0]];
        }
    }

    if (!$out || count($out) !== 3) {
        return '';
    }
    return strtolower(implode(' ', $out));
}

/** One country from plate_formats.json, or null. */
function bumperchat_country(string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }
    foreach (bumperchat_formats()['countries'] as $c) {
        if (isset($c['code']) && strtoupper((string) $c['code']) === $code) {
            return $c;
        }
    }
    return null;
}

function bumperchat_csrf_ok(): bool
{
    $a = (string) ($_SESSION['csrf_token'] ?? '');
    $b = (string) ($_POST['csrf_token'] ?? '');
    return $a !== '' && hash_equals($a, $b);
}

function bumperchat_csrf_field(): string
{
    $t = htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

/** Claim-flow state kept in the session (country, region, plate, words). */
function bumperchat_claim_state(): array
{
    $s = $_SESSION['bc_claim'] ?? [];
    return is_array($s) ? $s : [];
}

function bumperchat_claim_state_set(array $patch): array
{
    $s = array_merge(bumperchat_claim_state(), $patch);
    $_SESSION['bc_claim'] = $s;
    return $s;
}

function bumperchat_claim_state_clear(): void
{
    unset($_SESSION['bc_claim']);
}

/**
 * Safe relative Bumperchat path for the login ?next= parameter.
 * Rejects absolute URLs and paths outside this folder (open-redirect).
 */
function bumperchat_safe_next(?string $next, string $fallback = 'chat.php'): string
{
    $next = (string) $next;
    if ($next === '') {
        return $fallback;
    }
    if (!preg_match('/^(claim|claim_plate|claim_register|chat|pay|pay_return|index|login)\.php(?:\?[A-Za-z0-9_\-=.&%]*)?$/', $next)) {
        return $fallback;
    }
    return $next;
}

/** Current Bumperchat script name, for the top-right login return URL. */
function bumperchat_current_return(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return bumperchat_safe_next($script, 'index.php');
}

/**
 * After a Bumperchat login: if the claim flow already has validated words,
 * claim the plate (logged-in users skip registration) and go to chat or pay.
 * Otherwise go to $next (a safe relative path).
 */
function bumperchat_after_login_redirect(array $user, string $next = 'chat.php'): void
{
    $state = bumperchat_claim_state();
    $words = bumperchat_normalize_words((string) ($state['words'] ?? ''));
    bumperchat_mark_session($words);
    if ($words !== '') {
        bumperchat_try_claim_and_redirect($user, $words, $state['notify_post'] ?? []);
    }
    header('Location: ' . bumperchat_safe_next($next, 'chat.php'));
    exit;
}

/**
 * After a successful register or an explicit claim, create the plate when it is free.
 * Redirects to chat (trial/paid/unbilled) or the pay page (already used the free month).
 * $post is the claim form POST (phone + SMS consent); empty means no number.
 */
function bumperchat_try_claim_and_redirect(array $user, string $words, array $post = []): void
{
    if ($words === '' || (function_exists('db_3wordid_exists') && db_3wordid_exists($words))) {
        header('Location: chat.php');
        exit;
    }
    $notify = ['ok' => true, 'phone' => null, 'consent' => false, 'error' => ''];
    if (function_exists('notify_read_claim_post') && $post) {
        $notify = notify_read_claim_post($post);
    }
    if (!$notify['ok']) {
        $GLOBALS['error'] = $notify['error'];
        $GLOBALS['user'] = $user;
        return;
    }
    if (!function_exists('bumperchat_claim_plate')) {
        $GLOBALS['error'] = 'bc.err_claim_failed';
        $GLOBALS['user'] = $user;
        return;
    }
    $result = bumperchat_claim_plate($user, $words, $notify);
    if (!$result['ok']) {
        $GLOBALS['error'] = $result['error'];
        $GLOBALS['user'] = $user;
        return;
    }
    bumperchat_mark_session($words);
    bumperchat_claim_state_clear();
    if (($result['state'] ?? '') === 'expired') {
        header('Location: pay.php?words=' . rawurlencode($words));
        exit;
    }
    header('Location: chat.php');
    exit;
}

/** Visual 1–2–3 progress for the claim flow. $current is 1, 2 or 3. */
function bumperchat_flow_steps(int $current): string
{
    $items = [
        1 => ['claim.php', 'bc.flow_1'],
        2 => ['claim_plate.php', 'bc.flow_2'],
        3 => ['claim_register.php', 'bc.flow_3'],
    ];
    $out = '<ol class="bc-flow" aria-label="' . htmlspecialchars(t('bc.flow_label'), ENT_QUOTES, 'UTF-8') . '">';
    foreach ($items as $n => [$href, $key]) {
        $class = 'bc-flow-step';
        if ($n === $current) {
            $class .= ' is-current';
        } elseif ($n < $current) {
            $class .= ' is-done';
        }
        $label = $n . '. ' . t($key);
        if ($n < $current) {
            $out .= '<li class="' . $class . '"><a href="' . $href . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
        } else {
            $out .= '<li class="' . $class . '"><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></li>';
        }
    }
    return $out . '</ol>';
}

/**
 * HTML for one country's formatting rule, examples and optional note.
 * Used on the country step (all countries, one revealed) and as a recap later.
 */
function bumperchat_country_rules_html(array $c): string
{
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $name = bumperchat_text($c['name'] ?? '');
    $html = '<div class="bc-country-head">'
        . '<span class="bc-strip">' . $e($c['strip'] ?? '') . '</span>'
        . '<strong>' . $e($name) . '</strong>'
        . '<code class="bc-format">' . $e($c['format'] ?? '') . '</code>';
    if (!empty($c['uncertain'])) {
        $html .= '<span class="bc-pill" title="' . $e(t('bc.uncertain_title')) . '">' . $e(t('bc.uncertain')) . '</span>';
    }
    $html .= '</div>';
    $html .= '<p class="bc-format-rule">' . $e(bumperchat_text($c['rule'] ?? '')) . '</p>';
    $html .= '<p class="bc-format-ex">';
    foreach ($c['examples'] ?? [] as $ex) {
        $parts = explode('|', (string) ($ex['plate'] ?? ''), 2);
        $shown = count($parts) === 2 ? $parts[0] . ' · ' . $parts[1] : ($ex['plate'] ?? '');
        $html .= '<span class="bc-ex"><span class="bc-ex-plate">' . $e($shown) . '</span> → <code>' . $e($ex['words'] ?? '') . '</code></span>';
    }
    $html .= '</p>';
    if (!empty($c['note'])) {
        $html .= '<p class="bc-format-note">' . $e(bumperchat_text($c['note'])) . '</p>';
    }
    return $html;
}
