<?php
/*
 * Bumperchat (bumperchat.com -> https://www.3wordid.com/bumperchat/index.php)
 * ---------------------------------------------------------------------------
 * A second front door for 3WordID: licence plates as three words, and chat only.
 *   bumperchat/index.php  search box, onboarding steps, plate -> three words lookup
 *   bumperchat/claim.php  is the 3WordID free? create an email + password account or log in
 *   bumperchat/chat.php   the chats of a Bumperchat user (no list / admin pages)
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
        $out .= '<a class="btn btn-primary" href="claim.php#login">' . t('nav.login') . '</a>';
    }
    return $out . '</div></header>';
}
