<?php
/*
 * Bumperchat (bumperchat.com -> https://www.3wordid.com/bumperchat/index.php)
 * ---------------------------------------------------------------------------
 * A second front door for 3WordID: licence plates as three words, and chat only.
 *   bumperchat/index.php          search box, onboarding steps, plate -> three words lookup
 *   bumperchat/claim.php          country + plate on one page (format, validate, then Next)
 *   bumperchat/claim_plate.php    redirect to claim.php (old links)
 *   bumperchat/claim_register.php register (email + optional mobile); skipped if logged in
 *   bumperchat/login.php          top-right email login (no login form inside the claim flow)
 *   bumperchat/dispute.php        ownership-dispute info (email only; no upload or payment yet)
 *   bumperchat/chat.php           the chats of a Bumperchat user (no list / admin pages)
 *
 * No new database columns: a Bumperchat login is marked in the session only
 * ($_SESSION['bumperchat'] = ['words' => the three words they came for, 'at' => time]).
 * With that flag 3wid_list.php sends the user to bumperchat/chat.php. Every normal login
 * (establish_user_session) removes the flag again. See docs/BUMPERCHAT.md.
 */

/**
 * Public contact address used in terms (terms.s14_p2) and the Bumperchat
 * dispute mailto. Same address the site already publishes; not a new inbox.
 * A later config.php may define this first.
 */
if (!defined('SITE_CONTACT_EMAIL')) {
    define('SITE_CONTACT_EMAIL', 'frits@rincker.nl');
}

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
 * Country or region word shown as a fixed chip (never typed). Lower case,
 * matching the first word of plateToWords. Empty for three-mode countries (NL, …)
 * and for mixed-mode plates that already have three groups.
 */
function bumperchat_plate_prefix(array $entry, string $plate = '', string $region = ''): string
{
    $mode = (string) ($entry['mode'] ?? '');
    $strip = strtolower((string) ($entry['strip'] ?? ''));
    $reg = strtoupper(trim($region));
    $plate = bumperchat_strip_duplicate_prefix($entry, $plate, $region);
    $g = bumperchat_plate_groups($plate);

    if ($mode === 'prefix') {
        if (!empty($entry['three_ok']) && count($g) === 3) {
            return '';
        }
        return $strip;
    }
    if ($mode === 'mixed') {
        if (count($g) === 3) {
            return '';
        }
        $r = bumperchat_plate_runs(strtoupper(implode('', $g)));
        if (count($r) === 3) {
            return '';
        }
        if (count($g) === 2 || count($r) === 2) {
            return $strip;
        }
        return '';
    }
    if ($mode === 'region') {
        if ($reg === '' || empty($entry['regions']) || !isset($entry['regions'][$reg])) {
            return '';
        }
        if (count($g) === 1) {
            return $strip;
        }
        return strtolower($reg);
    }
    return '';
}

/**
 * Visible prefix in the plate field: the word in uppercase plus the
 * country's separator (dash or space). US/CA always use a dash, as in TX-ABC-1234.
 */
function bumperchat_plate_prefix_label(array $entry, string $plate = '', string $region = ''): string
{
    $prefix = bumperchat_plate_prefix($entry, $plate, $region);
    if ($prefix === '') {
        return '';
    }
    $sep = (($entry['mode'] ?? '') === 'region') ? '-' : bumperchat_plate_separator($entry);
    return strtoupper($prefix) . $sep;
}

/** Strip and region tokens a user might type in front of the real plate. */
function bumperchat_prefix_tokens(array $entry, string $region = ''): array
{
    $tokens = [];
    $mode = (string) ($entry['mode'] ?? '');
    if ($mode === 'three' || $mode === '') {
        return $tokens;
    }
    $strip = strtolower((string) ($entry['strip'] ?? ''));
    if ($strip !== '') {
        $tokens[] = $strip;
    }
    if ($mode === 'region') {
        $reg = strtolower(trim($region));
        if ($reg !== '' && $reg !== $strip) {
            $tokens[] = $reg;
        }
    }
    return $tokens;
}

/** Whether $plate is a complete plate for this country, without peeling a prefix. */
function bumperchat_remainder_is_complete(array $entry, string $plate, string $region = ''): bool
{
    if (bumperchat_plate_to_words_raw($entry, $plate, $region) === '') {
        return false;
    }
    $mode = (string) ($entry['mode'] ?? '');
    $g = bumperchat_plate_groups($plate);
    $r = bumperchat_plate_runs(strtoupper(implode('', $g)));
    if ($mode === 'prefix' || $mode === 'mixed') {
        if (count($g) === 2 || count($r) === 2) {
            return true;
        }
        if (( $mode === 'mixed' || !empty($entry['three_ok']) ) && (count($g) === 3 || count($r) === 3)) {
            return true;
        }
        return false;
    }
    if ($mode === 'region') {
        return count($g) === 1 || count($g) === 2;
    }
    return false;
}

/**
 * If the user typed the country/region prefix in front of the plate
 * (FIN ABC-123, ON ABCD 123), drop that leading duplicate. Does not peel
 * a leading group that is itself the plate (FIN-123 stays FIN-123).
 */
function bumperchat_strip_duplicate_prefix(array $entry, string $raw, string $region = ''): string
{
    $tokens = bumperchat_prefix_tokens($entry, $region);
    if (!$tokens || trim($raw) === '') {
        return $raw;
    }

    $groups = bumperchat_plate_groups($raw);
    $i = 0;
    while ($i < count($groups) && in_array(strtolower($groups[$i]), $tokens, true)) {
        $i++;
    }
    if ($i > 0 && $i < count($groups)) {
        $rest = implode(' ', array_slice($groups, $i));
        if (bumperchat_remainder_is_complete($entry, $rest, $region)) {
            return $rest;
        }
    }

    // One blob, no separators: peel a 2+ letter country strip (FINABC123 → ABC123).
    // Not used for 1-letter strips (S, E, M, …) or region codes, which can be
    // the start of a real letter group.
    if (count($groups) === 1) {
        $compact = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));
        $strip = strtoupper((string) ($entry['strip'] ?? ''));
        $mode = (string) ($entry['mode'] ?? '');
        if ($strip !== '' && strlen($strip) >= 2 && ($mode === 'prefix' || $mode === 'mixed')
            && strncmp($compact, $strip, strlen($strip)) === 0 && strlen($compact) > strlen($strip)) {
            $rest = substr($compact, strlen($strip));
            if (bumperchat_remainder_is_complete($entry, $rest, $region)) {
                return $rest;
            }
        }
    }

    return $raw;
}

/**
 * Same rules as js/bumperchat.js plateToWords(): one country entry from
 * plate_formats.json, the typed plate, and (for US/CA) the region code.
 * Returns the three words (lower case, spaces) or '' when the plate does not fit.
 */
function bumperchat_plate_to_words(array $entry, string $plate, string $region = ''): string
{
    $plate = bumperchat_strip_duplicate_prefix($entry, $plate, $region);
    return bumperchat_plate_to_words_raw($entry, $plate, $region);
}

/** plateToWords without peeling a typed prefix; used by the peeler itself. */
function bumperchat_plate_to_words_raw(array $entry, string $plate, string $region = ''): string
{
    $g = bumperchat_plate_groups($plate);
    $compact = implode('', $g);
    $strip = strtolower((string) ($entry['strip'] ?? ''));
    $out = null;
    if ($compact === '' || strlen($compact) > 20) {
        return '';
    }
    $official = bumperchat_unique_fit_any_pattern($entry, $compact, false);
    $mixed_official = bumperchat_patterns_have_mixed(is_array($entry['group_patterns'] ?? null) ? $entry['group_patterns'] : []);

    $mode = (string) ($entry['mode'] ?? '');
    if ($mode === 'three') {
        if (count($g) === 3) {
            $out = $g;
        } elseif ($official && count($official) === 3) {
            $out = $official;
        } elseif (!empty($entry['fixed']) && is_array($entry['fixed'])) {
            $out = bumperchat_fixed_split($compact, $entry['fixed']);
        }
        if (!$out && !$mixed_official) {
            $r = bumperchat_plate_runs($compact);
            if (count($r) === 3) {
                $out = $r;
            }
        }
    } elseif ($mode === 'mixed') {
        if (count($g) === 3) {
            $out = $g;
        } elseif ($official && count($official) === 3) {
            $out = $official;
        } elseif ($official && count($official) === 2) {
            $out = array_merge([$strip], $official);
        } elseif (!$mixed_official) {
            $r2 = bumperchat_plate_runs($compact);
            if (count($r2) === 3) {
                $out = $r2;
            } elseif (count($r2) === 2) {
                $out = array_merge([$strip], $r2);
            } elseif (count($g) === 2) {
                $out = array_merge([$strip], $g);
            }
        } elseif (count($g) === 2 && (($official && count($official) === 2) || bumperchat_groups_match_official($entry, $g))) {
            $out = array_merge([$strip], $g);
        }
    } elseif ($mode === 'prefix') {
        $has_patterns = !empty($entry['group_patterns']) && is_array($entry['group_patterns']);
        $r3 = $has_patterns ? [] : bumperchat_plate_runs($compact);
        if (count($g) === 3 && !empty($entry['three_ok'])) {
            $out = $g;
        } elseif ($official && count($official) === 2) {
            $out = array_merge([$strip], $official);
        } elseif ($official && count($official) === 3 && !empty($entry['three_ok'])) {
            $out = $official;
        } elseif (count($g) === 2 && bumperchat_groups_match_official($entry, $g)) {
            $out = array_merge([$strip], $g);
        } elseif (count($r3) === 2) {
            $out = array_merge([$strip], $r3);
        } elseif (!$has_patterns && count($g) === 2) {
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

/** Subtle "Dispute ownership" link to dispute.php?words=… */
function bumperchat_dispute_link(string $words): string
{
    $words = bumperchat_normalize_words($words);
    if ($words === '') {
        return '';
    }
    return '<p class="bc-dispute"><a href="dispute.php?words=' . rawurlencode($words) . '">'
        . t('bc.dispute_link') . '</a></p>';
}

/**
 * Next steps when a plate is already claimed.
 * Send-message goes to the existing 3wid_forward.php route used by the
 * Bumperchat homepage and chat page for a claimed plate.
 * $opts: logged_in (bool), retry (bool, default true).
 */
function bumperchat_taken_actions(string $words, array $opts = []): string
{
    $words = bumperchat_normalize_words($words);
    if ($words === '') {
        return '';
    }
    $logged_in = !empty($opts['logged_in']);
    $retry = array_key_exists('retry', $opts) ? !empty($opts['retry']) : true;
    $out = '<div class="bc-taken-actions">';
    $out .= '<a class="btn btn-primary" href="../3wid_forward.php?threewords=' . rawurlencode($words) . '">'
        . t('bc.send_to_plate') . '</a>';
    if (!$logged_in) {
        $next = 'claim.php?words=' . rawurlencode($words);
        $out .= '<a class="btn btn-ghost" href="login.php?next=' . rawurlencode($next) . '">'
            . t('bc.taken_login') . '</a>';
    }
    if ($retry) {
        $out .= '<a class="btn btn-ghost" href="claim.php?new=1">' . t('bc.try_another') . '</a>';
    }
    $out .= '</div>' . bumperchat_dispute_link($words);
    return $out;
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
    if (!preg_match('/^(claim|claim_plate|claim_register|chat|pay|pay_return|index|login|pending_send|dispute)\.php(?:\?[A-Za-z0-9_\-=.+&%]*)?$/', $next)) {
        return $fallback;
    }
    return $next;
}

/** Current Bumperchat script name, for the top-right login return URL. */
function bumperchat_current_return(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($script, ['pending_send.php', 'claim.php', 'dispute.php'], true)) {
        $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
        if ($qs !== '') {
            $script .= '?' . $qs;
        }
    }
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
        $own = function_exists('db_3wordid_get_threeword') ? db_3wordid_get_threeword($words) : null;
        if (is_array($own) && (int) ($own['user_id'] ?? 0) === (int) ($user['id'] ?? 0)) {
            if (function_exists('plate_billing_requires_payment') && plate_billing_requires_payment($own)) {
                header('Location: pay.php?words=' . rawurlencode($words));
                exit;
            }
            bumperchat_claim_state_clear();
            header('Location: chat.php');
            exit;
        }
        $taken = is_array($own) || (function_exists('db_3wordid_exists') && db_3wordid_exists($words));
        if (!$taken) {
            bumperchat_try_claim_and_redirect($user, $words, $state['notify_post'] ?? []);
        }
        // Already claimed by someone else: fall through to $next (the claim page).
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

/** Primary separator in a country's format string (dash or space). */
function bumperchat_plate_separator(array $entry): string
{
    $format = preg_replace('/\[[^\]]*\]/', '', (string) ($entry['format'] ?? ''));
    return (is_string($format) && strpos($format, '-') !== false) ? '-' : ' ';
}

/**
 * Official group patterns from plate_formats.json. Same helpers as
 * js/bumperchat.js. Used only by the Bumperchat chain.
 * @return list<array{cls:?string,lit:?string,min:int,max:int}>|null
 */
function bumperchat_compile_group_pattern(string $src): ?array
{
    $slots = [];
    $i = 0;
    $len = strlen($src);
    while ($i < $len) {
        $cls = null;
        $lit = null;
        if ($src[$i] === '[') {
            $close = strpos($src, ']', $i);
            if ($close === false) {
                return null;
            }
            $body = substr($src, $i + 1, $close - $i - 1);
            if ($body === 'A-Z') {
                $cls = 'A-Z';
            } elseif ($body === '0-9') {
                $cls = '0-9';
            } elseif ($body === 'A-Z0-9' || $body === '0-9A-Z') {
                $cls = 'A-Z0-9';
            } elseif ($body === 'EH' || $body === 'HE') {
                $cls = 'EH';
            } else {
                return null;
            }
            $i = $close + 1;
        } elseif (preg_match('/[A-Z0-9]/', $src[$i])) {
            $lit = $src[$i];
            $i++;
        } else {
            return null;
        }
        $min = 1;
        $max = 1;
        if ($i < $len && $src[$i] === '{') {
            $end = strpos($src, '}', $i);
            if ($end === false) {
                return null;
            }
            $spec = explode(',', substr($src, $i + 1, $end - $i - 1));
            $min = (int) $spec[0];
            $max = isset($spec[1]) ? (int) $spec[1] : $min;
            if ($min < 1 || $max < $min) {
                return null;
            }
            $i = $end + 1;
        } elseif ($i < $len && $src[$i] === '?') {
            $min = 0;
            $max = 1;
            $i++;
        }
        $slots[] = ['cls' => $cls, 'lit' => $lit, 'min' => $min, 'max' => $max];
    }
    return $slots ?: null;
}

function bumperchat_slot_char_ok(array $slot, string $ch): bool
{
    if ($slot['lit'] !== null) {
        return $ch === $slot['lit'];
    }
    $cls = (string) ($slot['cls'] ?? '');
    if ($cls === 'A-Z') {
        return (bool) preg_match('/[A-Z]/', $ch);
    }
    if ($cls === '0-9') {
        return (bool) preg_match('/[0-9]/', $ch);
    }
    if ($cls === 'A-Z0-9') {
        return (bool) preg_match('/[A-Z0-9]/', $ch);
    }
    if ($cls === 'EH') {
        return (bool) preg_match('/[EH]/', $ch);
    }
    return false;
}

function bumperchat_slots_minmax(array $slots): array
{
    $min = 0;
    $max = 0;
    foreach ($slots as $slot) {
        $min += (int) $slot['min'];
        $max += (int) $slot['max'];
    }
    return ['min' => $min, 'max' => $max];
}

function bumperchat_slots_consume(array $slots, string $text, int $si, int $ti, bool $allow_partial): bool
{
    $nslots = count($slots);
    $len = strlen($text);
    if ($si === $nslots) {
        return $ti === $len || ($allow_partial && $ti <= $len);
    }
    if ($ti > $len) {
        return false;
    }
    $slot = $slots[$si];
    $max_take = min((int) $slot['max'], $len - $ti);
    for ($n = $max_take; $n >= (int) $slot['min']; $n--) {
        $ok = true;
        for ($k = 0; $k < $n; $k++) {
            if (!bumperchat_slot_char_ok($slot, $text[$ti + $k])) {
                $ok = false;
                break;
            }
        }
        if ($ok && bumperchat_slots_consume($slots, $text, $si + 1, $ti + $n, $allow_partial)) {
            return true;
        }
    }
    if ($allow_partial && $ti === $len && (int) $slot['min'] === 0) {
        return bumperchat_slots_consume($slots, $text, $si + 1, $ti, $allow_partial);
    }
    if ($allow_partial && $si === $nslots - 1 && $ti < $len) {
        $rest = $len - $ti;
        if ($rest < (int) $slot['min'] && $rest <= (int) $slot['max']) {
            $ok = true;
            for ($k = 0; $k < $rest; $k++) {
                if (!bumperchat_slot_char_ok($slot, $text[$ti + $k])) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }
    }
    if ($allow_partial && $si < $nslots && $ti === $len) {
        return true;
    }
    return false;
}

function bumperchat_group_full_match(string $pattern, string $text): bool
{
    $slots = bumperchat_compile_group_pattern($pattern);
    return $slots && $text !== '' && bumperchat_slots_consume($slots, $text, 0, 0, false);
}

function bumperchat_group_prefix_match(string $pattern, string $text): bool
{
    $slots = bumperchat_compile_group_pattern($pattern);
    if (!$slots) {
        return false;
    }
    if ($text === '') {
        return true;
    }
    return bumperchat_slots_consume($slots, $text, 0, 0, true);
}

/** True when typed groups are one complete official pattern (not a prefix). */
function bumperchat_groups_match_official(array $entry, array $groups): bool
{
    $list = $entry['group_patterns'] ?? [];
    if (!$groups || !is_array($list)) {
        return false;
    }
    foreach ($list as $pats) {
        if (!is_array($pats) || count($pats) !== count($groups)) {
            continue;
        }
        $ok = true;
        foreach ($pats as $i => $pat) {
            if (!bumperchat_group_full_match((string) $pat, (string) $groups[$i])) {
                $ok = false;
                break;
            }
        }
        if ($ok) {
            return true;
        }
    }
    return false;
}

function bumperchat_group_is_mixed(string $pattern): bool
{
    $has_digit = (bool) preg_match('/\[0-9\]/', $pattern);
    $has_letter = (bool) preg_match('/\[A-Z\]/', $pattern);
    $has_alnum = (bool) preg_match('/\[0-9A-Z\]|\[A-Z0-9\]/', $pattern);
    $has_eh = (bool) preg_match('/\[EH\]|\[HE\]/', $pattern);
    return $has_alnum || ($has_digit && $has_letter) || ($has_digit && $has_eh);
}

function bumperchat_patterns_have_mixed(array $patterns): bool
{
    foreach ($patterns as $groups) {
        if (!is_array($groups)) {
            continue;
        }
        foreach ($groups as $g) {
            if (bumperchat_group_is_mixed((string) $g)) {
                return true;
            }
        }
    }
    return false;
}

/** @param list<string> $group_patterns @return list<list<string>> */
function bumperchat_collect_pattern_fits(array $group_patterns, string $text, bool $allow_partial): array
{
    $fits = [];
    if ($text === '') {
        return $allow_partial ? [[]] : [];
    }
    $walker = function (int $gi, int $pos, array $acc) use (&$walker, $group_patterns, $text, $allow_partial, &$fits): void {
        if ($gi === count($group_patterns)) {
            if ($pos === strlen($text)) {
                $fits[] = $acc;
            }
            return;
        }
        if ($pos === strlen($text)) {
            if ($allow_partial) {
                $fits[] = $acc;
            }
            return;
        }
        $slots = bumperchat_compile_group_pattern((string) $group_patterns[$gi]);
        if (!$slots) {
            return;
        }
        $mm = bumperchat_slots_minmax($slots);
        $left = strlen($text) - $pos;
        $is_last = $gi === count($group_patterns) - 1;
        $max_len = min($mm['max'], $left);
        $min_len = $allow_partial ? 1 : $mm['min'];
        if ($min_len > $max_len && $allow_partial && $left > 0) {
            $min_len = 1;
        }
        for ($n = $max_len; $n >= $min_len; $n--) {
            $piece = substr($text, $pos, $n);
            $more = ($pos + $n) < strlen($text);
            $ok_full = bumperchat_slots_consume($slots, $piece, 0, 0, false);
            $ok_part = $allow_partial && !$more && bumperchat_slots_consume($slots, $piece, 0, 0, true);
            if ($more && !$ok_full) {
                continue;
            }
            if (!$more && !$ok_full && !$ok_part) {
                continue;
            }
            if ($more && $is_last) {
                continue;
            }
            $walker($gi + 1, $pos + $n, array_merge($acc, [$piece]));
        }
    };
    $walker(0, 0, []);
    return $fits;
}

/**
 * Official grouping when every matching pattern agrees on the same
 * group boundaries. Null when the plate is ambiguous or does not fit.
 * @return list<string>|null
 */
function bumperchat_unique_fit_any_pattern(array $entry, string $text, bool $allow_partial): ?array
{
    $list = $entry['group_patterns'] ?? [];
    if (!is_array($list)) {
        return null;
    }
    $all = [];
    foreach ($list as $groups) {
        if (!is_array($groups)) {
            continue;
        }
        foreach (bumperchat_collect_pattern_fits($groups, $text, $allow_partial) as $got) {
            $all[] = $got;
        }
    }
    if (!$all) {
        return null;
    }
    $key = implode("\0", $all[0]);
    foreach ($all as $got) {
        if (implode("\0", $got) !== $key) {
            return null;
        }
    }
    return $all[0];
}

function bumperchat_country_format_known(array $entry): bool
{
    return ($entry['format'] ?? '') !== ''
        || !empty($entry['fixed'])
        || (($entry['mode'] ?? '') === 'region')
        || !empty($entry['group_patterns']);
}

function bumperchat_join_formatted(array $parts, string $sep, bool $trailing): string
{
    $parts = array_values(array_filter($parts, static function ($g) {
        return $g !== '';
    }));
    $out = implode($sep, $parts);
    if ($trailing && $out !== '') {
        $out .= $sep;
    }
    return $out;
}

/**
 * Format a typed plate for the Bumperchat chain only (index, claim,
 * onboarding). Not used on the main 3WordID site or the create form.
 * When a country format is known, follow that format (including official
 * mixed letter/digit groups, also while typing). The generic letter/digit
 * split is only used when no country format is known, or when every official
 * group is a homogeneous run of letters or of digits. Same as formatPlate()
 * in js/bumperchat.js.
 */
function bumperchat_format_plate(array $entry, string $raw, string $region = ''): string
{
    $raw = bumperchat_strip_duplicate_prefix($entry, $raw, $region);
    $compact = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));
    if ($compact === '') {
        return '';
    }
    $sep = bumperchat_plate_separator($entry);
    $typed = strtoupper($raw);
    $trailing = (bool) preg_match('/[-.\\s]$/', $typed);
    $typed_groups = bumperchat_plate_groups($raw);
    $known = bumperchat_country_format_known($entry);
    $patterns = is_array($entry['group_patterns'] ?? null) ? $entry['group_patterns'] : [];
    $mode = (string) ($entry['mode'] ?? '');

    if ($mode === 'region') {
        return bumperchat_join_formatted($typed_groups, $sep, $trailing);
    }

    // If the user already typed exactly three groups, those are the three
    // words. Do not split mixed groups such as 2562AE 187 0 or DE 123E.
    if (count($typed_groups) === 3) {
        return bumperchat_join_formatted($typed_groups, $sep, $trailing);
    }

    if ($patterns) {
        if (count($typed_groups) <= 1) {
            $fitted = bumperchat_unique_fit_any_pattern($entry, $compact, true);
            if ($fitted) {
                return bumperchat_join_formatted($fitted, $sep, $trailing);
            }
        } else {
            $kept = [];
            foreach ($typed_groups as $g) {
                $keep_whole = false;
                foreach ($patterns as $groups) {
                    if (!is_array($groups)) {
                        continue;
                    }
                    foreach ($groups as $pat) {
                        $pat = (string) $pat;
                        if (bumperchat_group_full_match($pat, $g) || (bumperchat_group_is_mixed($pat) && bumperchat_group_prefix_match($pat, $g))) {
                            $keep_whole = true;
                            break 2;
                        }
                    }
                }
                if ($keep_whole) {
                    $kept[] = $g;
                } else {
                    $pieces = bumperchat_plate_runs($g);
                    $kept = array_merge($kept, $pieces ?: [$g]);
                }
            }
            if ($kept) {
                return bumperchat_join_formatted($kept, $sep, $trailing);
            }
        }
    }

    if (!empty($entry['fixed']) && is_array($entry['fixed']) && count($typed_groups) <= 1) {
        $parts = [];
        $at = 0;
        $len = strlen($compact);
        foreach ($entry['fixed'] as $n) {
            $n = (int) $n;
            if ($n < 1 || $at >= $len) {
                break;
            }
            $parts[] = substr($compact, $at, $n);
            $at += $n;
        }
        if ($at < $len) {
            $parts[] = substr($compact, $at);
        }
        return bumperchat_join_formatted($parts, $sep, $trailing);
    }

    if (!$known || !bumperchat_patterns_have_mixed($patterns)) {
        $parts = [];
        foreach ($typed_groups as $g) {
            foreach (bumperchat_plate_runs($g) as $r) {
                $parts[] = $r;
            }
        }
        return bumperchat_join_formatted($parts, $sep, $trailing);
    }
    return bumperchat_join_formatted($typed_groups, $sep, $trailing);
}

/** First example plate of a country, without a "STATE|" prefix. */
function bumperchat_plate_example(array $entry): string
{
    $ex = (string) ($entry['examples'][0]['plate'] ?? $entry['format'] ?? '');
    $parts = explode('|', $ex, 2);
    return count($parts) === 2 ? $parts[1] : $ex;
}

/** Visual style for the plate input: nl (yellow), na (US/Canada white), eu (white + blue band). */
function bumperchat_plate_style(string $code): string
{
    $code = strtoupper($code);
    if ($code === 'NL') {
        return 'nl';
    }
    if ($code === 'US' || $code === 'CA') {
        return 'na';
    }
    return 'eu';
}

/**
 * Country codes whose rules accept this plate (and region, for US/CA).
 * Used to auto-detect or suggest a country when the pattern is distinctive.
 */
function bumperchat_infer_countries(string $plate, string $region = ''): array
{
    $hits = [];
    if (trim($plate) === '') {
        return $hits;
    }
    foreach (bumperchat_formats()['countries'] as $c) {
        $code = (string) ($c['code'] ?? '');
        if ($code === '') {
            continue;
        }
        $reg = $region;
        if (($c['mode'] ?? '') === 'region' && $reg === '') {
            continue;
        }
        if (bumperchat_plate_to_words($c, $plate, $reg) !== '') {
            $hits[] = $code;
        }
    }
    return $hits;
}

/** The only matching country code, or '' when none or more than one match. */
function bumperchat_infer_country(string $plate, string $region = ''): string
{
    $hits = bumperchat_infer_countries($plate, $region);
    return count($hits) === 1 ? $hits[0] : '';
}

/**
 * From a 3WordID ("e 1234 bcd", "tx abc 1234", "ab 123 c"), recover a country,
 * optional US/CA region, and a formatted plate. When the first word is not a
 * known country or region prefix, country is left empty and the three words
 * are returned as the plate for the caller to format with a default country.
 *
 * @return array{country:string,region:string,plate:string,words:string}
 */

/** First words of non-region country examples, so "ab 123 c" is not treated as Alberta. */
function bumperchat_common_first_words(): array
{
    static $set = null;
    if ($set !== null) {
        return $set;
    }
    $set = [];
    foreach (bumperchat_formats()['countries'] as $c) {
        if (($c['mode'] ?? '') === 'region') {
            continue;
        }
        foreach ($c['examples'] ?? [] as $ex) {
            $w = bumperchat_normalize_words((string) ($ex['words'] ?? ''));
            if ($w === '') {
                continue;
            }
            $set[explode(' ', $w)[0]] = true;
        }
    }
    return $set;
}

function bumperchat_prefill_from_words(string $words): array
{
    $empty = ['country' => '', 'region' => '', 'plate' => '', 'words' => ''];
    $words = bumperchat_normalize_words($words);
    if ($words === '') {
        return $empty;
    }
    $parts = explode(' ', $words);
    $first = $parts[0];

    // US/CA region codes (TX, QC, ...) as the first word. Skip when that word
    // is also a common first group of an EU plate ("ab 123 c" is Dutch, not Alberta).
    $common_first = bumperchat_common_first_words();
    foreach (['US', 'CA'] as $code) {
        $c = bumperchat_country($code);
        if (!$c || empty($c['regions']) || !is_array($c['regions'])) {
            continue;
        }
        foreach ($c['regions'] as $reg => $_label) {
            if (strtoupper($first) !== strtoupper((string) $reg)) {
                continue;
            }
            if (isset($common_first[$first])) {
                break 2;
            }
            $rest = implode(' ', array_slice($parts, 1));
            $reg_up = strtoupper((string) $reg);
            return [
                'country' => $code,
                'region'  => $reg_up,
                'plate'   => bumperchat_format_plate($c, $rest, $reg_up),
                'words'   => $words,
            ];
        }
    }

    $matched = [];
    foreach (bumperchat_formats()['countries'] as $c) {
        $strip = strtolower((string) ($c['strip'] ?? ''));
        if ($strip !== '' && $strip === $first) {
            $matched[] = $c;
        }
    }
    if (count($matched) === 1) {
        $c = $matched[0];
        $mode = (string) ($c['mode'] ?? '');
        $rest = array_slice($parts, 1);
        $region = '';
        if ($mode === 'region' && $rest) {
            $maybe = strtoupper((string) $rest[0]);
            if (isset($c['regions'][$maybe])) {
                $region = $maybe;
                $rest = array_slice($rest, 1);
            }
        }
        return [
            'country' => (string) $c['code'],
            'region'  => $region,
            'plate'   => bumperchat_format_plate($c, implode(' ', $rest), $region),
            'words'   => $words,
        ];
    }

    return [
        'country' => '',
        'region'  => '',
        'plate'   => strtoupper(implode(' ', $parts)),
        'words'   => $words,
    ];
}

/**
 * Country code from server headers that already hold a lookup done at the
 * edge or in Apache (Cloudflare, mod_geoip, MaxMind). Never logs the value.
 */
function bumperchat_country_from_geo_headers(array $server): string
{
    $keys = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_GEOIP_COUNTRY_CODE',
        'GEOIP_COUNTRY_CODE',
        'HTTP_MM_COUNTRY_CODE',
        'MM_COUNTRY_CODE',
        'GEOIP2_COUNTRY_CODE',
        'HTTP_GEOIP2_COUNTRY_CODE',
    ];
    foreach ($keys as $key) {
        $raw = strtoupper(trim((string) ($server[$key] ?? '')));
        // Cloudflare: XX = unknown, T1 = Tor. Ignore placeholders.
        if ($raw === '' || $raw === 'XX' || $raw === 'T1' || $raw === 'A1' || $raw === 'A2') {
            continue;
        }
        if (preg_match('/^[A-Z]{2}$/', $raw)) {
            return $raw;
        }
    }
    return '';
}

/**
 * Country from Accept-Language: a region subtag (nl-NL, en-US) first, then a
 * language code that is also a country in the plate list (de → DE).
 */
function bumperchat_country_from_accept_language(?string $header): string
{
    if ($header === null || $header === '') {
        return '';
    }
    $ranked = [];
    foreach (explode(',', $header) as $part) {
        $bits = explode(';', trim($part));
        $tag = strtolower(trim((string) ($bits[0] ?? '')));
        if ($tag === '' || $tag === '*') {
            continue;
        }
        $q = 1.0;
        foreach (array_slice($bits, 1) as $param) {
            $param = trim((string) $param);
            if (stripos($param, 'q=') === 0) {
                $q = (float) substr($param, 2);
            }
        }
        $sub = preg_split('/[-_]/', $tag);
        $lang = strtoupper((string) ($sub[0] ?? ''));
        $region = '';
        foreach (array_slice($sub, 1) as $piece) {
            if (preg_match('/^[a-z]{2}$/', $piece)) {
                $region = strtoupper($piece);
            }
        }
        $ranked[] = ['region' => $region, 'lang' => $lang, 'q' => $q];
    }
    usort($ranked, function ($a, $b) { return $b['q'] <=> $a['q']; });
    foreach ($ranked as $row) {
        if ($row['region'] !== '' && bumperchat_country($row['region'])) {
            return $row['region'];
        }
    }
    foreach ($ranked as $row) {
        if ($row['lang'] !== '' && bumperchat_country($row['lang'])) {
            return $row['lang'];
        }
    }
    return '';
}

/**
 * Local IP-to-country lookup only: configured .mmdb (GEOIP_COUNTRY_DB) or the
 * legacy PHP geoip extension. The IP is not sent anywhere and is not logged.
 */
function bumperchat_country_from_ip_lookup(): string
{
    $ip = function_exists('geoip_client_ip')
        ? geoip_client_ip()
        : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return '';
    }
    if (function_exists('geoip_country')) {
        $code = geoip_country($ip);
        if (is_string($code) && $code !== '') {
            return $code;
        }
    }
    if (function_exists('geoip_country_code_by_name')) {
        $code = @geoip_country_code_by_name($ip);
        if (is_string($code) && preg_match('/^[A-Za-z]{2}$/', $code)) {
            return strtoupper($code);
        }
    }
    return '';
}

/**
 * Default country for the Bumperchat converter and claim form.
 * Privacy-first, server-side only: no browser geo API, no third-party call,
 * nothing stored or logged. Order:
 *   1. CF-IPCountry / GEOIP_COUNTRY_CODE / MM_COUNTRY_CODE (and aliases)
 *   2. Local MaxMind/DB-IP .mmdb or PHP geoip extension
 *   3. Accept-Language region (or language that matches a country)
 *   4. NL
 * The code is used only when it exists in plate_formats.json.
 *
 * $server / $accept_language / $lookup_ip are injectable for tests.
 */
function bumperchat_default_country(?array $server = null, $accept_language = false, bool $lookup_ip = true): string
{
    $server = $server ?? $_SERVER;
    $code = bumperchat_country_from_geo_headers($server);
    if ($code !== '' && bumperchat_country($code)) {
        return $code;
    }
    if ($lookup_ip) {
        $code = bumperchat_country_from_ip_lookup();
        if ($code !== '' && bumperchat_country($code)) {
            return $code;
        }
    }
    if ($accept_language === false) {
        $accept_language = (string) ($server['HTTP_ACCEPT_LANGUAGE'] ?? '');
    }
    $code = bumperchat_country_from_accept_language(is_string($accept_language) ? $accept_language : '');
    if ($code !== '' && bumperchat_country($code)) {
        return $code;
    }
    return bumperchat_country('NL') ? 'NL' : '';
}

/**
 * Small original SVG of 12 gold stars (not a copyrighted EU-flag image),
 * used in the blue band of the licence-plate input.
 */
function bumperchat_eu_stars_svg(): string
{
    $pts = '';
    for ($i = 0; $i < 12; $i++) {
        $a = deg2rad(-90 + $i * 30);
        $x = 10 + 6.2 * cos($a);
        $y = 9 + 6.2 * sin($a);
        $pts .= '<circle cx="' . round($x, 2) . '" cy="' . round($y, 2) . '" r="1.05" fill="#FFCC00"/>';
    }
    return '<svg class="bc-plate-stars" width="22" height="22" viewBox="0 0 20 20" aria-hidden="true" focusable="false">' . $pts . '</svg>';
}

/**
 * Licence-plate face: EU blue band + input (or a read-only value).
 * $opts: value, region, id, name, placeholder, readonly, example.
 */
function bumperchat_plate_face_html(array $country, array $opts = []): string
{
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $code = (string) ($country['code'] ?? '');
    $style = bumperchat_plate_style($code);
    $strip = (string) ($country['strip'] ?? $code);
    $region = (string) ($opts['region'] ?? '');
    $value = (string) ($opts['value'] ?? '');
    if ($value !== '') {
        $value = bumperchat_format_plate($country, $value, $region);
    }
    $prefix = bumperchat_plate_prefix($country, $value, $region);
    $label = bumperchat_plate_prefix_label($country, $value, $region);
    $id = (string) ($opts['id'] ?? 'bcPlate');
    $name = (string) ($opts['name'] ?? 'plate');
    $ph = (string) ($opts['placeholder'] ?? bumperchat_plate_example($country));
    $readonly = !empty($opts['readonly']);
    $cls = 'bc-plate-shell is-' . $style;
    if ($readonly) {
        $cls .= ' is-readonly';
    }
    if ($label !== '') {
        $cls .= ' is-prefixed';
    }
    $html = '<div class="' . $cls . '" id="bcPlateShell" data-style="' . $e($style) . '" data-code="' . $e($code) . '">';
    $html .= '<span class="bc-plate-band" id="bcPlateBand" aria-hidden="true">'
        . bumperchat_eu_stars_svg()
        . '<span class="bc-plate-cc" id="bcPlateCc">' . $e($strip) . '</span></span>';
    $html .= '<span class="bc-plate-legend" id="bcPlateLegend">' . $e($region) . '</span>';
    $html .= '<span class="bc-plate-type">';
    $html .= '<span class="bc-plate-prefix" id="bcPlatePrefix" aria-hidden="true"'
        . ($label === '' ? ' hidden' : '') . '>' . $e($label) . '</span>';
    $hint = $prefix !== '' ? t('bc.plate_prefix_hint', ['prefix' => $prefix]) : '';
    $html .= '<span class="bc-sr-only" id="bcPlatePrefixHint">' . $hint . '</span>';
    $described = $prefix !== '' ? ' aria-describedby="bcPlatePrefixHint"' : '';
    if ($readonly) {
        $html .= '<span class="bc-plate-value" id="' . $e($id) . '">' . $e($value) . '</span>';
    } else {
        $html .= '<input id="' . $e($id) . '" name="' . $e($name) . '" class="bc-plate-field" type="text" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" inputmode="text" aria-label="' . $e(t('bc.plate')) . '"' . $described . ' placeholder="' . $e($ph) . '" value="' . $e($value) . '">';
    }
    $html .= '</span></div>';
    return $html;
}
