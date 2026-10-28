<?php
// Small view helpers for the chat style message pages (css/chat.css).
// Pure presentation: no database access and no schema knowledge in here.
// Requires php/i18n.php (t, current_lang) to be loaded already.

// Multibyte string helpers that also work when the mbstring extension is not installed
function chat_len($s) {
	$s = (string) $s;
	if (function_exists('mb_strlen')) {
		return mb_strlen($s, 'UTF-8');
	}
	return preg_match_all('/./us', $s, $m) ?: strlen($s);
}
function chat_substr($s, $start, $len) {
	$s = (string) $s;
	if (function_exists('mb_substr')) {
		return mb_substr($s, $start, $len, 'UTF-8');
	}
	if (preg_match_all('/./us', $s, $m)) {
		return implode('', array_slice($m[0], $start, $len));
	}
	return substr($s, $start, $len);
}
function chat_lower($s) {
	return function_exists('mb_strtolower') ? mb_strtolower((string) $s, 'UTF-8') : strtolower((string) $s);
}
function chat_upper($s) {
	return function_exists('mb_strtoupper') ? mb_strtoupper((string) $s, 'UTF-8') : strtoupper((string) $s);
}

// Escape for HTML output
function chat_e($s) {
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// First letter of a name, upper case, for the avatar circle
function chat_initial($name) {
	$name = trim((string) $name);
	if ($name === '') {
		return '?';
	}
	return chat_upper(chat_substr($name, 0, 1));
}

// Avatar colour class (c0..c7), stable for the same name
function chat_color($name) {
	return 'c' . (abs(crc32(chat_lower(trim((string) $name)))) % 8);
}

// Avatar circle markup
function chat_avatar($name, $extra_class = '') {
	return '<span class="avatar ' . chat_color($name) . ($extra_class ? ' ' . chat_e($extra_class) : '') . '" aria-hidden="true">' . chat_e(chat_initial($name)) . '</span>';
}

// Timestamp string -> unix time, or null
function chat_ts($ts) {
	if ($ts === null || $ts === '') {
		return null;
	}
	$u = strtotime((string) $ts);
	return $u === false ? null : $u;
}

// "14:05" today, otherwise the date. Empty string when there is no timestamp.
function chat_time_label($ts) {
	$u = chat_ts($ts);
	if ($u === null) {
		return '';
	}
	if (date('Y-m-d', $u) === date('Y-m-d')) {
		return date('H:i', $u);
	}
	return chat_date_text($u);
}

// "14:05" always (inside a bubble). Empty string when there is no timestamp.
function chat_clock($ts) {
	$u = chat_ts($ts);
	return $u === null ? '' : date('H:i', $u);
}

function chat_date_text($u) {
	// English uses short month names, the other languages a numeric day/month/year
	// (no month names to translate, so it reads the same everywhere)
	return current_lang() === 'en' ? date('j M Y', $u) : date('d/m/Y', $u);
}

// Date separator text: Today, Yesterday or the date
function chat_day_label($ts) {
	$u = chat_ts($ts);
	if ($u === null) {
		return '';
	}
	$day = date('Y-m-d', $u);
	if ($day === date('Y-m-d')) {
		return t('chat.today');
	}
	if ($day === date('Y-m-d', strtotime('-1 day'))) {
		return t('chat.yesterday');
	}
	return chat_date_text($u);
}

function chat_day_key($ts) {
	$u = chat_ts($ts);
	return $u === null ? '' : date('Y-m-d', $u);
}

// Shorten text for a preview line
function chat_preview($text, $max = 70) {
	$text = trim(preg_replace('/\s+/u', ' ', (string) $text));
	if (chat_len($text) <= $max) {
		return $text;
	}
	return rtrim(chat_substr($text, 0, $max - 1)) . '…';
}

// Messages have no subject field any more. The database column `title` is still filled,
// with the start of the message, so older code, lists and the print view keep working.
function chat_title_from_message($message) {
	return chat_preview($message, 40);
}

// The title is made from the start of the message (see chat_title_from_message). Do not
// show it twice when the message just starts with it. Older messages with a real,
// different subject still show it.
function chat_title_redundant($title, $message) {
	$title = trim((string) $title);
	if ($title === '') {
		return true;
	}
	$title = (string) preg_replace('/[\x{2026}.\s]+$/u', '', $title);
	$message = chat_lower(trim(preg_replace('/\s+/u', ' ', (string) $message)));
	return $title !== '' && strncmp($message, chat_lower($title), strlen(chat_lower($title))) === 0;
}

// Message text: escaped, line breaks kept
function chat_text($text) {
	return nl2br(chat_e($text));
}

// Inline SVG icons
function chat_icon($name) {
	switch ($name) {
		case 'back':
			return '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg>';
		case 'send':
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4l17.45-7.48a1 1 0 0 0 0-1.84L3.4 3.6a.99.99 0 0 0-1.38 1.05L3 10.5l9 1.5-9 1.5-.98 5.85a.99.99 0 0 0 1.38 1.05z"/></svg>';
		case 'trash':
			return '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg>';
		case 'reply':
			return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14L4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 6 6v3"/></svg>';
		case 'user':
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0z"/></svg>';
		case 'print':
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>';
		case 'compose':
			// pencil on a square = start a new message
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>';
		case 'ticks':
			// double check mark = read
			return '<svg class="tick read" viewBox="0 0 20 12" width="20" height="12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="' . chat_e(t('chat.read')) . '"><path d="M1.5 6.5L5 10l7.5-7.5"/><path d="M8.5 9.5l.5.5 7.5-7.5"/></svg>';
		case 'tick':
			// single check mark = sent
			return '<svg class="tick" viewBox="0 0 16 12" width="16" height="12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="' . chat_e(t('chat.sent')) . '"><path d="M2 6.5l3.5 3.5L13 2.5"/></svg>';
	}
	return '';
}

// Avatar for an anonymous visitor (user icon instead of an initial)
function chat_visitor_avatar() {
	return '<span class="avatar visitor" aria-hidden="true">' . chat_icon('user') . '</span>';
}

// Group the rows of db_3wordid_list_messages() into conversations, one per sender
// 3WordID. Anonymous visitors (user 25) share one group: there is no sender to tell apart.
// Returns [key => ['key', 'name', 'visitor', 'items' (oldest first, with 'sent_at')]],
// newest conversation first. $dates comes from db_3wordid_message_dates().
// Anonymous visitors (3wid_messageform_ext_process.php) are stored with this
// placeholder user and placeholder 3WordID. They have no account: no replies.
if (!defined('CHAT_VISITOR_USER_ID')) { define('CHAT_VISITOR_USER_ID', 25); }
if (!defined('CHAT_VISITOR_WID')) { define('CHAT_VISITOR_WID', 178); }

// True when a 3wordid row (from db_3wordid_get_threeword) is the visitor placeholder
function chat_is_visitor_wid($row) {
	if (!is_array($row)) { return false; }
	return (int) ($row['id'] ?? 0) === CHAT_VISITOR_WID || (int) ($row['user_id'] ?? 0) === CHAT_VISITOR_USER_ID;
}

// Three words as typed in the recipient field, the way they are stored: trimmed,
// single spaces, lower case ("Red  Blue Dog " -> "red blue dog").
function chat_normalize_words($words) {
	return chat_lower(trim(preg_replace('/\s+/u', ' ', (string) $words)));
}

// Can a logged-in member send a message to this 3WordID row (from db_3wordid_get_threeword)?
// '' = yes, else the lang key why not. A real recipient: the 3WordID exists (deleted ones
// are moved to 3wordid_deleted, so they are not found), is switched on, and is not the
// placeholder used for anonymous visitors.
function chat_recipient_error($row) {
	if (!is_array($row) || empty($row['id'])) { return 'chat.err_recipient'; }
	if (chat_is_visitor_wid($row)) { return 'chat.err_visitor'; }
	if (isset($row['enabled']) && (int) $row['enabled'] !== 1) { return 'chat.err_inactive'; }
	return '';
}

// Group messages into conversations, one per partner 3WordID, in BOTH directions:
// $incoming = db_3wordid_list_messages() rows (sent to my 3WordID), $sent =
// db_3wordid_list_sent() rows (sent from my 3WordID). Anonymous visitors (user 25)
// share one group 'visitors'. $dates / $reads: [id => creation_date] / [id => read_at]
// (empty when those columns do not exist yet).
// Returns [key => ['key', 'name', 'visitor', 'wid', 'unread', 'items' (oldest first;
// each with 'mine', 'sent_at', 'read_at' (null = unread, '' = unknown))]], newest first.
function chat_conversations($incoming, $dates, $sent = array(), $reads = array()) {
	$conversations = array();
	$add = function ($key, $name, $is_visitor, $wid, $m, $mine) use (&$conversations, $dates, $reads) {
		if (!isset($conversations[$key])) {
			$conversations[$key] = array('key' => $key, 'name' => $name, 'visitor' => $is_visitor, 'wid' => $wid, 'unread' => 0, 'items' => array());
		}
		$id = (int) $m['id'];
		$m['mine'] = $mine;
		$m['sent_at'] = $dates[$id] ?? '';
		$m['read_at'] = array_key_exists($id, $reads) ? $reads[$id] : '';
		if (!$mine && $m['read_at'] === null) {
			$conversations[$key]['unread']++;
		}
		$conversations[$key]['items'][] = $m;
	};
	foreach ((array) $incoming as $m) {
		$is_visitor = ((int) $m['from_id'] === CHAT_VISITOR_USER_ID);
		$wid = $is_visitor ? 0 : (int) ($m['3wid_from_id'] ?? 0);
		$key = $is_visitor ? 'visitors' : ($wid > 0 ? 'w' . $wid : ((int) $m['from_id'] . ':' . $m['threeword']));
		$add($key, $is_visitor ? '' : $m['threeword'], $is_visitor, $wid, $m, false);
	}
	foreach ((array) $sent as $m) {
		$wid = (int) ($m['3wid_id'] ?? 0);
		if ($wid < 1 || $wid === CHAT_VISITOR_WID) {
			continue;
		}
		$add('w' . $wid, $m['threeword'], false, $wid, $m, true);
	}
	foreach ($conversations as &$c) {
		usort($c['items'], function ($a, $b) { return (int) $a['id'] <=> (int) $b['id']; });
	}
	unset($c);
	uasort($conversations, function ($a, $b) {
		return (int) $b['items'][count($b['items']) - 1]['id'] <=> (int) $a['items'][count($a['items']) - 1]['id'];
	});
	return $conversations;
}

// ---------------------------------------------------------------------------
// Profile photos (opt-in per 3WordID, logged-in members only)
// ---------------------------------------------------------------------------

// Signature that ties a photo URL to this session, so 3wid_photo.php only serves
// photos the page actually showed (no guessing ids). Uses the session's action token.
function chat_photo_sig($wid_id) {
	return substr(hash_hmac('sha256', 'photo:' . (int) $wid_id, get_action_token()), 0, 32);
}

function chat_photo_url($wid_id) {
	return '3wid_photo.php?wid=' . (int) $wid_id . '&s=' . chat_photo_sig($wid_id);
}

// Avatar for a 3WordID: its photo when $photo_ids[$wid_id] is set (see db_3wordid_photo_ids,
// call only for logged-in viewers), otherwise the letter avatar. With $zoom the photo
// is a button that opens it larger (do not use $zoom inside a link).
function chat_avatar_for($name, $wid_id, $photo_ids, $extra_class = '', $zoom = false) {
	$wid_id = (int) $wid_id;
	if ($wid_id < 1 || empty($photo_ids[$wid_id])) {
		return chat_avatar($name, $extra_class);
	}
	$url = chat_e(chat_photo_url($wid_id));
	$cls = 'avatar photo ' . chat_color($name) . ($extra_class ? ' ' . chat_e($extra_class) : '');
	// The letter stays underneath: it shows when the image cannot be loaded
	$inner = '<span class="ini">' . chat_e(chat_initial($name)) . '</span><img src="' . $url . '" alt="" loading="lazy" onerror="this.remove()">';
	if ($zoom) {
		return '<button type="button" class="' . $cls . '" data-photo="' . $url . '" aria-label="' . t('chat.photo_open', ['name' => $name]) . '" title="' . t('chat.photo_open', ['name' => $name]) . '" data-close="' . t('chat.photo_close') . '">' . $inner . '</button>';
	}
	return '<span class="' . $cls . '" aria-hidden="true">' . $inner . '</span>';
}

// ---------------------------------------------------------------------------
// Top bar of the chat pages (css/chat.css .chat-topbar)
// ---------------------------------------------------------------------------

// Version of css/chat.css and js/chat.js: raise it when they change, so browsers
// load the new files without a hard refresh (Ctrl+F5).
if (!defined('CHAT_ASSET_VERSION')) { define('CHAT_ASSET_VERSION', '7'); }

function chat_asset($path) {
	return chat_e($path . '?v=' . CHAT_ASSET_VERSION);
}

// Icons only used in the top bar
function chat_tb_icon($name) {
	switch ($name) {
		case 'list':
			// "My 3WordIDs": a list with bullets
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/></svg>';
		case 'globe':
			return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
		case 'home':
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>';
		case 'login':
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>';
	}
	return chat_icon($name);
}

// Round icon button for the right side of the bar. $label is plain text.
function chat_tb_button($href, $icon, $label, $attrs = '') {
	return '<a class="tb-btn" href="' . chat_e($href) . '" title="' . chat_e($label) . '" aria-label="' . chat_e($label) . '"' . ($attrs !== '' ? ' ' . $attrs : '') . '>' . chat_tb_icon($icon) . '</a>';
}

// Language menu: current language code, the others in a small drop-down (no page JS needed)
function chat_lang_menu() {
	$names = array('en' => 'English', 'nl' => 'Nederlands', 'fr' => 'Français', 'es' => 'Español');
	$current = current_lang();
	$label = t_plain('lang.switch_label');
	$out = '<details class="tb-lang"><summary title="' . chat_e($label) . '" aria-label="' . chat_e($label . ': ' . ($names[$current] ?? $current)) . '">'
		. chat_tb_icon('globe') . '<span>' . chat_e(i18n_language_label($current)) . '</span></summary><ul role="list">';
	foreach (i18n_languages() as $code) {
		$out .= '<li><a href="' . chat_e(lang_url($code)) . '" hreflang="' . chat_e($code) . '" lang="' . chat_e($code) . '"'
			. ($code === $current ? ' aria-current="true"' : '') . '>' . chat_e($names[$code] ?? i18n_language_label($code)) . '</a></li>';
	}
	return $out . '</ul></details>';
}

// The top-left button. Logged in: back to the list of your 3WordIDs. Not logged in: a
// "Login" button to the login page (the homepage is the home icon on the right, see
// chat_home_button). Returns [url, visible label, accessible label, optional icon].
// ($main_url is no longer used; kept so older calls keep working.)
function chat_back_home($logged_in, $main_url = 'index.php') {
	if ($logged_in) {
		return array('3wid_list.php', t('tb.my_ids'), t('tb.back_my_ids'));
	}
	return array('/login/options.php', t('nav.login'), t('nav.login'), 'login');
}

// Home icon for the right side of the bar (visitors who are not logged in)
function chat_home_button() {
	return chat_tb_button('index.php', 'home', t_plain('tb.back_home'));
}

// The bar itself. All values are HTML (already escaped):
//   back      [url, visible label, accessible label]
//   avatar    avatar markup (chat_avatar_for / chat_visitor_avatar)
//   name      bold line: who you are chatting with
//   name_id   optional id of the name element (updated by page JS)
//   sub       small second line
//   sub_attrs optional extra attributes of the sub line
//   actions   buttons on the right (chat_tb_button), before the language menu
function chat_topbar($o) {
	$back = $o['back'];
	$h = '<header class="chat-topbar"><div class="tb-inner">';
	// An optional 4th value is the icon instead of the back arrow; 'login' also keeps the
	// "Login" text visible on phones (it is a button, not a way back)
	$back_icon = $back[3] ?? '';
	$h .= '<a class="tb-back' . ($back_icon === 'login' ? ' tb-back-login' : '') . '" href="' . chat_e($back[0]) . '" aria-label="' . $back[2] . '" title="' . $back[2] . '">'
		. ($back_icon !== '' ? chat_tb_icon($back_icon) : chat_icon('back')) . '<span class="tb-back-label">' . $back[1] . '</span></a>';
	$h .= '<div class="tb-who">' . ($o['avatar'] ?? '');
	$h .= '<div class="tb-text"><div class="tb-name"' . (!empty($o['name_id']) ? ' id="' . chat_e($o['name_id']) . '"' : '') . '>' . $o['name'] . '</div>';
	if (isset($o['sub']) && $o['sub'] !== '') {
		// sub_attrs may bring its own class attribute (it must then include tb-sub)
		$sub_attrs = !empty($o['sub_attrs']) ? $o['sub_attrs'] : '';
		$h .= '<div' . (strpos($sub_attrs, 'class=') === false ? ' class="tb-sub"' : '') . ($sub_attrs !== '' ? ' ' . $sub_attrs : '') . '>' . $o['sub'] . '</div>';
	}
	$h .= '</div></div>';
	$h .= '<div class="tb-actions">' . ($o['actions'] ?? '') . chat_lang_menu() . '</div>';
	return $h . '</div></header>';
}
