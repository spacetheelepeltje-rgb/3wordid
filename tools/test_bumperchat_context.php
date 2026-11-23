<?php
/**
 * Bumperchat context checks that need no database:
 *  - plate account rules (chat-only routing),
 *  - the Bumperchat context flag (session / cookie) and country memory,
 *  - login return allow-list incl. help.php, Google start URL,
 *  - NO link or redirect from /bumperchat/ to the main 3wordID homepage / list / login,
 *  - Google return (login/welcome.php) stays in /bumperchat/,
 *  - the main homepage car link to bumperchat.com (the only link in that direction),
 *  - the help page and its texts, the chat-view plate form.
 * Run from the repo root:  php tools/test_bumperchat_context.php
 * Not needed on the web server. Exit code 1 when something differs.
 */

if (!function_exists('current_lang')) { function current_lang(): string { return 'en'; } }
if (!function_exists('t')) { function t(string $key, array $vars = []): string { return $key; } }
if (!function_exists('t_plain')) { function t_plain(string $key, array $vars = []): string { return $key; } }

if (!function_exists('lang_switcher')) { function lang_switcher() { return ''; } }

require_once __DIR__ . '/../php/bumperchat.php';

$root = realpath(__DIR__ . '/..');
$fail = 0;
function expect($label, $got, $want) {
    global $fail;
    if ($got !== $want) {
        echo "FAIL $label: got " . var_export($got, true) . ", expected " . var_export($want, true) . "\n";
        $fail++;
    }
}
function src($rel) { global $root; return (string) file_get_contents($root . '/' . $rel); }

// --- plate account (chat-only routing) ---
$plate_trial = ['id' => 1, 'threeword' => 'ab 123 c', 'claim_source' => 'bumperchat', 'billing_product' => 'bumperchat_plate'];
$plate_paying = ['id' => 2, 'threeword' => 'de 456 f', 'claim_source' => 'bumperchat', 'billing_product' => null];
$plate_product_only = ['id' => 3, 'threeword' => 'gh 789 i', 'billing_product' => 'bumperchat_plate'];
$normal = ['id' => 4, 'threeword' => 'red blue fox', 'claim_source' => null, 'billing_product' => null];
$normal_missing_cols = ['id' => 5, 'threeword' => 'red blue owl'];
expect('plate row (trial)', bumperchat_is_plate_row($plate_trial), true);
expect('plate row (paying account, no product)', bumperchat_is_plate_row($plate_paying), true);
expect('plate row (product only)', bumperchat_is_plate_row($plate_product_only), true);
expect('normal row', bumperchat_is_plate_row($normal), false);
expect('row without billing columns', bumperchat_is_plate_row($normal_missing_cols), false);
expect('plate-only account', bumperchat_plate_only_account([$plate_trial, $plate_paying]), true);
expect('mixed account is not plate-only', bumperchat_plate_only_account([$plate_trial, $normal]), false);
expect('normal account is not plate-only', bumperchat_plate_only_account([$normal]), false);
expect('empty account is not plate-only', bumperchat_plate_only_account([]), false);

// --- Bumperchat context ---
$_SESSION = []; $_COOKIE = [];
expect('not active by default', bumperchat_active(), false);
$_COOKIE['bc_ctx'] = '1';
expect('active by cookie (session expired)', bumperchat_active(), true);
bumperchat_ctx_forget();
expect('forget clears the cookie value', bumperchat_active(), false);
$_SESSION['bc_ctx'] = 1;
expect('active by session context', bumperchat_active(), true);
$_SESSION = [];
bumperchat_mark_session('ab 123 c');
expect('mark_session sets flag + context', [!empty($_SESSION['bumperchat']), !empty($_SESSION['bc_ctx'])], [true, true]);
expect('bumperchat_or in context', bumperchat_or('3wid_list.php', 'chat.php'), 'bumperchat/chat.php');
$_SESSION = [];
expect('bumperchat_or outside context', bumperchat_or('3wid_list.php', 'chat.php'), '3wid_list.php');

// --- remembered country ---
$_SESSION = [];
bumperchat_remember_country('fr');
expect('remember country (session) ', $_SESSION['bc_country'] ?? '', 'FR');
expect('remembered country is used', bumperchat_remembered_country(), 'FR');
bumperchat_remember_country('ZZ');
expect('unknown country not remembered', $_SESSION['bc_country'] ?? '', 'FR');
$_SESSION = [];
expect('no memory: detected default', bumperchat_remembered_country(), bumperchat_default_country());

// --- recipient typed in the chat view: plate -> three words (same formatter / validator) ---
$_SESSION = [];
expect('recipient: three words stay', bumperchat_plate_input_to_words('Ab  123 c'), 'ab 123 c');
expect('recipient: NL plate with dashes', bumperchat_plate_input_to_words('AB-123-C', 'NL'), 'ab 123 c');
expect('recipient: NL compact', bumperchat_plate_input_to_words('ab123c', 'NL'), 'ab 123 c');
expect('recipient: NL sidecode 11 with a US default', bumperchat_plate_input_to_words('36-JXN-1', 'US'), '36 jxn 1');
expect('recipient: not a plate', bumperchat_plate_input_to_words('hello', 'NL'), '');
expect('recipient: empty', bumperchat_plate_input_to_words('  ', 'NL'), '');
bumperchat_remember_country('NL');
expect('recipient: uses the remembered country', bumperchat_plate_input_to_words('AB123C'), 'ab 123 c');

// --- return paths ---
expect('help.php is an allowed return', bumperchat_safe_next('help.php', 'x'), 'help.php');
expect('google_details is an allowed return', bumperchat_safe_next('google_details.php', 'x'), 'google_details.php');
expect('account is an allowed return', bumperchat_safe_next('account.php', 'x'), 'account.php');
expect('chat.php is an allowed return', bumperchat_safe_next('chat.php', 'x'), 'chat.php');
expect('pending_send with query', bumperchat_safe_next('pending_send.php?words=ab+123+c', 'x'), 'pending_send.php?words=ab+123+c');
expect('absolute URL rejected', bumperchat_safe_next('https://3wordid.com/index.php', 'x'), 'x');
expect('main-site page rejected', bumperchat_safe_next('../index.php', 'x'), 'x');
expect('google start url (no next)', bumperchat_google_start_url(''), 'google.php');
expect('google start url (pending)', bumperchat_google_start_url('pending_send.php?words=ab+123+c'), 'google.php?next=pending_send.php%3Fwords%3Dab%2B123%2Bc');
expect('google start url drops unsafe next', bumperchat_google_start_url('../3wid_list.php'), 'google.php');

// --- NO redirect or link from /bumperchat/ to the main homepage, list or login ---
$bad = [
    '#\.\./index\.php#'               => '../index.php',
    '#\.\./3wid_list\.php#'           => '../3wid_list.php',
    '#\.\./login/options\.php#'       => '../login/options.php',
    '#\.\./login/logout\.php#'        => '../login/logout.php',
    '#/login/options\.php#'           => '/login/options.php',
    '#3wordid\.com/index\.php#i'      => '3wordid.com/index.php',
    '#Location:\s*/?index\.php#'      => 'Location: index.php (resolves inside /bumperchat/, ok) -- not matched below',
];
unset($bad['#Location:\s*/?index\.php#']);
$files = array_merge(glob($root . '/bumperchat/*.php'), [$root . '/php/bumperchat.php']);
foreach ($files as $f) {
    $s = file_get_contents($f);
    $s = preg_replace('#^\s*(//|\*|/\*).*$#m', '', $s); // comments may name the files
    foreach ($bad as $re => $label) {
        expect(basename($f) . ' has no ' . $label, preg_match($re, $s) === 1, false);
    }
}
// every Location: header in /bumperchat/ goes to a Bumperchat page, Google, Mollie, or a shared chat page
$allowed_shared = '#^(\.\./(3wid_list_messages|3wid_messageform|3wid_forward|3wid_privacy_consent)\.php)#';
foreach (glob($root . '/bumperchat/*.php') as $f) {
    $s = file_get_contents($f);
    if (preg_match_all("#header\\('Location: ([^'\\s]+)#", $s, $m)) {
        foreach ($m[1] as $target) {
            if (strpos($target, '../') === 0) {
                expect(basename($f) . ' redirect ' . $target . ' is a shared chat page', preg_match($allowed_shared, $target) === 1, true);
            }
        }
    }
}
// Bumperchat language strings never point at the main site
foreach (['en', 'nl', 'fr', 'es'] as $code) {
    $lang = include $root . '/lang/' . $code . '.php';
    foreach ($lang as $k => $v) {
        if (preg_match('/^(bc|bch|pending)\./', $k)) {
            expect("lang $code $k has no main-site link", preg_match('#\.\./index\.php|\.\./3wid_list\.php|\.\./login/#', (string) $v) === 1, false);
        }
    }
}

// --- Google login destinations (the two starts are tested separately) ---
$user_dest = ['id' => 9];
expect('BC Google, extras needed, no plate: extras form first', bumperchat_login_destination($user_dest, '', true, false, true), 'google_details.php');
expect('BC Google, extras needed, has plate: extras then chat', bumperchat_login_destination($user_dest, 'chat.php', false, true, true), 'google_details.php?next=chat.php');
expect('BC Google, extras done, no plate: plate registration', bumperchat_login_destination($user_dest, '', false, false, false), 'claim.php');
expect('BC Google, extras done, has plate: chat view', bumperchat_login_destination($user_dest, '', false, true, false), 'chat.php');
expect('BC Google, has plate, pending-message next kept', bumperchat_login_destination($user_dest, 'pending_send.php?words=ab+123+c', false, true, false), 'pending_send.php?words=ab+123+c');
expect('BC Google, no plate: pending-message next still goes to claim', bumperchat_login_destination($user_dest, 'pending_send.php?words=ab+123+c', false, false, false), 'claim.php');
expect('BC Google, no plate, mid-claim next kept', bumperchat_login_destination($user_dest, 'claim_register.php', false, false, false), 'claim_register.php');
expect('unsafe next is dropped, no plate: claim', bumperchat_login_destination($user_dest, '../3wid_list.php', false, false, false), 'claim.php');
expect('unsafe next is dropped, has plate: chat', bumperchat_login_destination($user_dest, '../3wid_list.php', false, true, false), 'chat.php');

$_SERVER['SCRIPT_NAME'] = '/login/welcome.php';
expect('href from login/welcome.php prefixes /bumperchat/', bumperchat_href('claim.php'), '../bumperchat/claim.php');
$_SERVER['SCRIPT_NAME'] = '/bumperchat/login.php';
expect('href from a Bumperchat page stays relative', bumperchat_href('claim.php'), 'claim.php');
unset($_SERVER['SCRIPT_NAME']);

// --- Google return (login/welcome.php): Bumperchat vs 3wordID are separate ---
$welcome = src('login/welcome.php');
expect('welcome reads the Bumperchat return context', strpos($welcome, "oauth_return") !== false, true);
expect('welcome marks the Bumperchat session', strpos($welcome, 'bumperchat_mark_session(') !== false, true);
expect('welcome Bumperchat success uses after_login_redirect (chat or claim, not the list)', strpos($welcome, 'bumperchat_after_login_redirect(') !== false, true);
expect('welcome error exits go back to bumperchat/login.php', substr_count($welcome, '../bumperchat/login.php') >= 1, true);
expect('welcome 3wordID Google success still goes to 3wid_list.php', strpos($welcome, "header('Location: ../3wid_list.php')") !== false, true);
// The 3wid_list redirect must stay outside the Bumperchat branch
$pos_bc = strpos($welcome, 'if ($from_bumperchat)');
$pos_bc_redir = strpos($welcome, 'bumperchat_after_login_redirect(');
$pos_list = strpos($welcome, "header('Location: ../3wid_list.php')");
expect('welcome Bumperchat branch comes before the 3wordID list redirect', $pos_bc !== false && $pos_list !== false && $pos_bc < $pos_list, true);
expect('welcome Bumperchat success redirect is not the list page', $pos_bc_redir !== false && $pos_bc_redir < $pos_list, true);
$bc_block = ($pos_bc !== false && $pos_list !== false) ? substr($welcome, $pos_bc, $pos_list - $pos_bc) : '';
expect('welcome Bumperchat branch does not redirect to 3wid_list.php', $bc_block !== '' && strpos($bc_block, '3wid_list.php') === false, true);
expect('claim page shows the flashed notice', strpos(src('bumperchat/claim.php'), 'bumperchat_take_notice') !== false && strpos(src('bumperchat/claim.php'), '$notice') !== false, true);
expect('chat view refuses accounts with no plate', strpos(src('bumperchat/chat.php'), 'bumperchat_user_has_plate') !== false && strpos(src('bumperchat/chat.php'), 'bc.first_register_plate') !== false, true);
$google = src('bumperchat/google.php');
expect('google.php stores the return context', strpos($google, "'bumperchat' => 1") !== false, true);
expect('google.php uses the existing Google client', strpos($google, 'createAuthUrl') !== false && strpos($google, 'login/config.php') !== false, true);
$login = src('bumperchat/login.php');
expect('login offers Google', strpos($login, 'bumperchat_google_start_url') !== false, true);
expect('login shows only known Google errors', strpos($login, "'welcome.expired', 'welcome.failed', 'welcome.spam'") !== false, true);
expect('main login clears the Bumperchat context', strpos(src('login/options.php'), 'bumperchat_ctx_forget()') !== false, true);

// --- shared pages stay in context ---
foreach (['3wid_list_messages.php', '3wid_message_view.php', '3wid_messageform.php', '3wid_messageform_process.php', '3wid_message_delete.php', '3wid_chat_print.php', '3wid_privacy_consent_process.php'] as $f) {
    expect("$f links back through bumperchat_or / chat_list_button", strpos(src($f), 'bumperchat_or(') !== false || strpos(src($f), 'chat_list_button()') !== false, true);
}
foreach (['3wid_forward.php', '3wid_messageform_ext.php', '3wid_inactive.php', '3wid_notification.php', '3wid_privacy_consent.php', 'terms.php'] as $f) {
    expect("$f swaps the home link in Bumperchat context", strpos(src($f), "bumperchat/index.php") !== false, true);
}
expect('chat top bar helper is context aware', strpos(src('php/chat.php'), "bumperchat/chat.php") !== false, true);
expect('message form converts a typed plate on the server', strpos(src('3wid_messageform_process.php'), 'bumperchat_plate_input_to_words(') !== false, true);
expect('plate-only guard on list', strpos(src('3wid_list.php'), 'bumperchat_route_chat_only($data)') !== false, true);

// --- main homepage: the one link to bumperchat.com, left of the lock icon ---
$index = src('index.php');
$pos_car = strpos($index, 'id="bumperchatLink"');
$pos_lock = strpos($index, 'id="searchLock"');
expect('index has the bumperchat.com car link', strpos($index, 'href="https://bumperchat.com"') !== false, true);
expect('car link title is the lang key to bumperchat.com', strpos($index, "t('home.to_bumperchat')") !== false, true);
expect('car link sits left of the lock icon', $pos_car !== false && $pos_lock !== false && $pos_car < $pos_lock, true);
$en = include $root . '/lang/en.php';
expect('tooltip text', $en['home.to_bumperchat'] ?? '', 'to bumperchat.com');
expect('register-plate notice (en)', $en['bc.first_register_plate'] ?? '', 'First register your licence plate');
foreach (glob($root . '/*.php') as $f) {
    if (basename($f) === 'index.php') { continue; }
    expect(basename($f) . ' has no bumperchat.com link (only index.php may)', stripos(file_get_contents($f), 'bumperchat.com"') !== false && stripos(file_get_contents($f), 'href="https://bumperchat.com') !== false, false);
}

// --- chat view: plate form with the shared formatter and a country icon button ---
$chat = src('bumperchat/chat.php');
expect('chat form uses the shared plate face', strpos($chat, 'bumperchat_plate_face_html(') !== false, true);
expect('chat form has the country icon button and hidden dropdown', strpos($chat, 'id="bcCountryBtn"') !== false && strpos($chat, 'id="bcCountryPop"') !== false && strpos($chat, 'id="bcCountryPop" hidden') !== false, true);
expect('chat form no longer posts free text to 3wid_forward', strpos($chat, 'action="../3wid_forward.php"') === false, true);
expect('chat form goes to the validating page', strpos($chat, 'action="pending_send.php"') !== false, true);
expect('chat form defaults to the remembered country', strpos($chat, 'bumperchat_remembered_country()') !== false, true);
expect('js remembers the country', strpos(src('js/bumperchat.js'), 'saveCountry(') !== false, true);

// --- help page ---
$help = src('bumperchat/help.php');
expect('help page exists and uses lang keys', strpos($help, "bch.login_h") !== false, true);
foreach (['bch.login_h', 'bch.msg_unclaimed', 'bch.country_p1', 'bch.claim_h', 'bch.chat_h', 'bch.del_h'] as $k) {
    foreach (['en', 'nl', 'fr', 'es'] as $code) {
        $lang = include $root . '/lang/' . $code . '.php';
        expect("help key $k in $code", isset($lang[$k]) && $lang[$k] !== '', true);
    }
}
expect('footer links help', strpos(bumperchat_footer_links(), 'href="help.php"') !== false, true);
expect('footer terms stay in context', strpos(bumperchat_footer_links(), 'terms.php?from=bumperchat') !== false, true);
expect('header has the help icon', strpos(bumperchat_header(null), 'href="help.php"') !== false, true);

if ($fail) {
    echo "$fail failed\n";
    exit(1);
}
echo "ok\n";
exit(0);
