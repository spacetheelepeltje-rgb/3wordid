<?php
// The chat of ONE 3WordID for a member who logged in with Chat login (3wid_chat_login.php).
// Same look as the owner's chats (3wid_list_messages.php), but bound to the 3WordID in the
// member session: no list of 3WordIDs, no account, no edit/delete, no payments.
// Every request checks the member session again (chat_member_require), so a password
// change or switching chat login off by the owner ends it at once.
//
//   GET                list of conversations of this 3WordID
//   GET ?with=<key>    one conversation (marks the messages to this 3WordID as read)
//   GET ?new=1         write to a 3WordID that is not in the list yet
//   POST do=reply      reply in conversation 'with' (recipient taken from the conversation)
//   POST do=new        new message to the typed three words
//
// When the 3WordID is switched off (enabled = 0) the member can still log in and read,
// but not send (others can't write to a switched-off 3WordID either).

require_once 'php/functions.php';
require_once 'login/config.php';
require_once 'php/chat.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$wid_row = chat_member_require();
$threeword_id = (int) $wid_row['id'];
$owner_id = (int) $wid_row['user_id'];
$threeword = (string) $wid_row['threeword'];
$read_only = ((int) $wid_row['enabled'] !== 1);
$self = '3wid_chat.php';

// Everything this 3WordID received and sent (sent = from this 3WordID by its owner account,
// which includes what the member sent: members send in the owner's name)
function member_chat_conversations($threeword_id, $owner_id) {
    $messages = db_3wordid_list_messages($threeword_id);
    $sent_rows = db_3wordid_list_sent($threeword_id, $owner_id);
    $all_ids = array_merge(array_column((array) $messages, 'id'), array_column($sent_rows, 'id'));
    return chat_conversations((array) $messages, db_3wordid_message_dates($all_ids), $sent_rows, db_3wordid_message_reads($all_ids));
}

// ---------------------------------------------------------------- sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = (string) ($_POST['do'] ?? '');
    $message = trim((string) ($_POST['message'] ?? ''));
    $message = chat_substr($message, 0, 5000);
    $back = $self;
    $error = '';
    $recipient = null;

    if (!check_action_token($_POST['action_token'] ?? '')) {
        $_SESSION['chat_error'] = 'common.session_expired_retry';
        header('Location: ' . $self);
        exit;
    }

    if ($do === 'reply') {
        // The recipient comes from one of THIS 3WordID's own conversations, never from the form
        $with = (string) ($_POST['with'] ?? '');
        $conversations = member_chat_conversations($threeword_id, $owner_id);
        if (!isset($conversations[$with])) {
            header('Location: ' . $self);
            exit;
        }
        $conv = $conversations[$with];
        $back = $self . '?with=' . rawurlencode($with);
        if ($conv['visitor']) {
            $error = 'chat.err_visitor';
        } elseif (!empty($conv['deleted'])) {
            $error = 'chat.err_recipient';
        } else {
            $recipient = db_3wordid_get_threeword((string) $conv['name']);
            if (!$recipient || (int) $recipient['id'] !== (int) $conv['wid']) {
                $recipient = null;
                $error = 'chat.err_recipient';
            }
        }
    } elseif ($do === 'new') {
        $words = chat_normalize_words(chat_substr((string) ($_POST['threeword'] ?? ''), 0, 200));
        $back = $self . '?new=1';
        $recipient = $words !== '' ? db_3wordid_get_threeword($words) : null;
        $_SESSION['chat_new_to'] = chat_substr($words, 0, 80);
    } else {
        header('Location: ' . $self);
        exit;
    }

    if ($error === '' && $read_only) {
        $error = 'cl.read_only';
    }
    if ($error === '') {
        $error = chat_recipient_error($recipient);
    }
    if ($error === '' && (int) $recipient['id'] === $threeword_id) {
        $error = 'chat.err_recipient';
    }
    if ($error === '') {
        // Open / closed chats (php/car_chat.php)
        $error = chat_access_error(db_3wordid_get($threeword_id) ?: $wid_row, $recipient);
    }
    if ($error === '' && $message === '') {
        $error = 'chat.err_empty';
    }
    if ($error === '') {
        // The owner account accepted the privacy terms once; without that nothing is sent
        $owner = db_3wordid_get_google_user($owner_id);
        if (!is_array($owner) || (int) ($owner['privacy_consent'] ?? 0) !== 1) {
            $error = 'cl.err_owner';
        }
    }
    if ($error === '') {
        $error = db_3wordid_message_rate_error((int) $recipient['id'], $threeword_id);
    }

    if ($error !== '') {
        $_SESSION['chat_error'] = $error;
        $_SESSION['chat_draft'] = $message;
        header('Location: ' . $back);
        exit;
    }

    // Sent in the owner's name from this 3WordID, like a reply from the owner's own chat
    db_3wordid_messageform_insert($owner_id, (int) $recipient['id'], $threeword_id, (int) $recipient['user_id'], chat_title_from_message($message), $message, 1);
    unset($_SESSION['chat_new_to']);
    $key = 'w' . (int) $recipient['id'];
    $_SESSION['chat_conv_sent'] = $threeword_id . '|' . $key;
    header('Location: ' . $self . '?with=' . rawurlencode($key));
    exit;
}

// ---------------------------------------------------------------- reading
$conversations = member_chat_conversations($threeword_id, $owner_id);
$with = isset($_GET['with']) ? (string) $_GET['with'] : '';
$open = ($with !== '' && isset($conversations[$with])) ? $conversations[$with] : null;
$new = ($open === null && isset($_GET['new']) && !$read_only);
// Never sent or received anything yet: show the composer straight away (recipient field,
// input bar and send arrow) with a "choose a recipient" note, instead of an empty list
$first_chat = (!$conversations && $open === null && !$read_only);
if ($first_chat) {
    $new = true;
}

// Opening a conversation marks the messages sent to this 3WordID in it as read
if ($open !== null && $open['unread'] > 0) {
    if (!empty($open['deleted'])) {
        $unread_ids = array();
        foreach ($open['items'] as $it) {
            if (!$it['mine'] && $it['read_at'] === null) { $unread_ids[] = (int) $it['id']; }
        }
        db_3wordid_mark_read_ids($threeword_id, $unread_ids);
    } else {
        db_3wordid_mark_read($threeword_id, $open['visitor'] ? CHAT_VISITOR_WID : $open['wid']);
    }
}

$just_sent = ($open !== null && ($_SESSION['chat_conv_sent'] ?? '') === $threeword_id . '|' . $open['key']);
unset($_SESSION['chat_conv_sent']);
$known_errors = array('chat.err_visitor', 'chat.err_recipient', 'chat.err_inactive', 'chat.err_empty', 'chat.err_rate_pair', 'chat.err_rate_recipient', 'chat.err_closed_in', 'chat.err_closed_out', 'cl.read_only', 'cl.err_owner', 'common.session_expired_retry');
$conv_error = (!empty($_SESSION['chat_error']) && in_array($_SESSION['chat_error'], $known_errors, true)) ? $_SESSION['chat_error'] : '';
unset($_SESSION['chat_error']);
$draft = (string) ($_SESSION['chat_draft'] ?? '');
unset($_SESSION['chat_draft']);
$new_to = (string) ($_SESSION['chat_new_to'] ?? '');
if (!$new) { $new_to = ''; }
unset($_SESSION['chat_new_to']);

$action_token = get_action_token();
$threeword_safe = chat_e($threeword);
$total = count($conversations);

// Top bar: no way to other pages, only this chat; Log out always visible
$logout = '<form class="tb-logout-form" method="post" action="3wid_chat_logout.php">'
    . '<input type="hidden" name="action_token" value="' . chat_e($action_token) . '">'
    . '<button type="submit" class="tb-btn tb-logout" title="' . t('cl.logout') . '">' . chat_tb_icon('logout') . '<span>' . t('cl.logout') . '</span></button></form>';
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo t('cl.chat_title'); ?></title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <link href="/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo chat_asset('css/chat.css'); ?>">
</head>
<body class="chat-body member-chat">
<?php if ($open === null && !$new): ?>
    <?php echo chat_topbar(array(
        'back'    => null,
        'avatar'  => chat_avatar($threeword),
        'name'    => $threeword_safe,
        'sub'     => t_n('chat.conversations', $total),
        'actions' => $read_only ? '' : chat_tb_button($self . '?new=1', 'compose', t_plain('chat.new_message')),
        'after'   => $logout,
    )); ?>
<div class="chat-app">
    <main class="chat-scroll plain">
        <?php if ($read_only): ?>
        <div class="pill-wrap"><span class="pill info" role="note"><?php echo t('cl.read_only'); ?></span></div>
        <?php endif; ?>
        <?php if ($conv_error !== ''): ?>
        <div class="pill-wrap"><span class="pill info" role="alert"><?php echo t($conv_error); ?></span></div>
        <?php endif; ?>
        <?php if (!$conversations): ?>
        <div class="pill-wrap"><span class="pill"><?php echo t('cl.empty'); ?></span></div>
        <?php endif; ?>
        <ul class="chat-list">
        <?php foreach ($conversations as $c):
            $last = $c['items'][count($c['items']) - 1];
            $label = $c['visitor'] ? t('chat.visitors') : chat_e($c['name']);
            $show_title = !$last['mine'] && !chat_title_redundant($last['title'], $last['message']);
        ?>
            <li>
                <a class="chat-row<?php echo $c['unread'] > 0 ? ' unread' : ''; ?>" href="<?php echo chat_e($self . '?with=' . rawurlencode($c['key'])); ?>">
                    <?php echo ($c['visitor'] || !empty($c['deleted'])) ? chat_visitor_avatar() : chat_avatar($c['name']); ?>
                    <span class="cr-main">
                        <span class="cr-top">
                            <span class="cr-name"><?php echo $label; ?></span>
                            <span class="cr-time"><?php echo chat_e(chat_time_label($last['sent_at'])); ?></span>
                        </span>
                        <span class="cr-bottom">
                            <span class="cr-preview"><?php if ($last['mine']): ?><span class="cr-you"><?php echo t('chat.you_prefix'); ?></span> <?php endif; ?><?php if ($show_title): ?><b><?php echo chat_e(chat_preview($last['title'], 40)); ?></b> <?php endif; ?><?php echo chat_e(chat_preview($last['message'])); ?></span>
                            <?php if ($c['unread'] > 0): ?><span class="cr-unread" role="img" title="<?php echo t_n('chat.unread', $c['unread']); ?>" aria-label="<?php echo t_n('chat.unread', $c['unread']); ?>"></span><?php endif; ?>
                        </span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </main>

<?php elseif ($new): ?>
    <?php echo chat_topbar(array(
        'back'    => $first_chat ? null : array($self, t('tb.chats'), t('tb.back_chats', ['words' => $threeword])),
        'avatar'  => $first_chat ? chat_avatar($threeword) : '',
        'name'    => $first_chat ? $threeword_safe : t('chat.new_message'),
        'sub'     => $first_chat ? t('cl.empty') : t('tb.chatting_as', ['words' => $threeword]),
        'after'   => $logout,
    )); ?>
<div class="chat-app">
    <main class="chat-scroll" data-scroll="end">
        <?php if ($conv_error !== ''): ?>
        <div class="pill-wrap"><span class="pill info" role="alert"><?php echo t($conv_error); ?></span></div>
        <?php elseif ($first_chat): ?>
        <div class="pill-wrap"><span class="pill info first-chat" role="note"><?php echo t('cl.choose_recipient'); ?></span></div>
        <?php endif; ?>
        <div class="setup">
            <div class="f">
                <label for="threeword"><?php echo t('msg.recipient'); ?></label>
                <input type="text" name="threeword" id="threeword" form="3widForm" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" maxlength="200" placeholder="<?php echo t('common.placeholder_words'); ?>" value="<?php echo chat_e($new_to); ?>">
            </div>
        </div>
    </main>
    <form class="composer" id="3widForm" action="<?php echo chat_e($self); ?>" method="post">
        <input type="hidden" name="do" value="new">
        <div class="c-row">
            <div class="c-box">
                <textarea name="message" id="message" class="c-text" rows="1" required maxlength="5000" placeholder="<?php echo t('chat.type_message'); ?>" aria-label="<?php echo t('msg.message_label'); ?>"><?php echo chat_e($draft); ?></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
        <input type="hidden" name="action_token" value="<?php echo chat_e($action_token); ?>">
    </form>

<?php else:
    $is_visitor = $open['visitor'];
    $is_deleted = !empty($open['deleted']);
    $label = $is_visitor ? t('chat.visitors') : chat_e($open['name']);
    $prev_day = null;
    echo chat_topbar(array(
        'back'    => array($self, t('tb.chats'), t('tb.back_chats', ['words' => $threeword])),
        'avatar'  => ($is_visitor || $is_deleted) ? chat_visitor_avatar() : chat_avatar($open['name']),
        'name'    => $label,
        'sub'     => t('tb.chatting_as', ['words' => $threeword]),
        'after'   => $logout,
    )); ?>
<div class="chat-app">
    <main class="chat-scroll" data-scroll="end" data-refresh="15">
        <?php foreach ($open['items'] as $i => $m):
            $day = chat_day_key($m['sent_at']);
            $new_day = ($day !== '' && $day !== $prev_day);
            if ($new_day):
                $prev_day = $day; ?>
        <div class="pill-wrap"><span class="pill"><?php echo chat_e(chat_day_label($m['sent_at'])); ?></span></div>
        <?php endif;
            $prev_m = $i > 0 ? $open['items'][$i - 1] : null;
            $first = ($i === 0 || $new_day || $prev_m['mine'] !== $m['mine']);
        ?>
        <div class="row <?php echo $m['mine'] ? 'out' : 'in'; ?><?php echo $first ? ' first' : ''; ?>" data-id="<?php echo (int) $m['id']; ?>">
            <div class="bubble">
                <?php if (!chat_title_redundant($m['title'], $m['message']) && !$m['mine']): ?><div class="b-title"><?php echo chat_e($m['title']); ?></div><?php endif; ?>
                <div class="b-text"><?php echo chat_text($m['message']); ?></div>
                <div class="b-meta">
                    <?php if (chat_clock($m['sent_at']) !== ''): ?><time><?php echo chat_e(chat_clock($m['sent_at'])); ?></time><?php endif; ?>
                    <?php if ($m['mine']): ?><?php echo (is_string($m['read_at']) && $m['read_at'] !== '') ? chat_icon('ticks') : chat_icon('tick'); ?><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </main>

    <?php if ($is_deleted): ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('chat.deleted_no_reply'); ?></p></div>
    <?php elseif ($is_visitor): ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('chat.no_reply'); ?></p></div>
    <?php elseif ($read_only): ?>
    <div class="reply-bar"><p class="note" role="note"><?php echo t('cl.read_only'); ?></p></div>
    <?php else: ?>
    <form class="composer conv" id="3widForm"<?php echo $just_sent ? ' data-sent="1"' : ''; ?> action="<?php echo chat_e($self); ?>" method="post">
        <?php if ($conv_error !== ''): ?><p class="c-note err" role="alert"><?php echo t($conv_error); ?></p><?php endif; ?>
        <input type="hidden" name="do" value="reply">
        <input type="hidden" name="with" value="<?php echo chat_e($open['key']); ?>">
        <div class="c-row">
            <div class="c-box">
                <textarea name="message" id="message" class="c-text" rows="1" required maxlength="5000" placeholder="<?php echo t('lm.reply', ['name' => $open['name']]); ?>" aria-label="<?php echo t('msg.message_label'); ?>"><?php echo chat_e($draft); ?></textarea>
            </div>
            <button type="submit" id="submitBtn" class="send" aria-label="<?php echo t('common.send'); ?>" title="<?php echo t('common.send'); ?>"><?php echo chat_icon('send'); ?></button>
        </div>
        <input type="hidden" name="action_token" value="<?php echo chat_e($action_token); ?>">
    </form>
    <?php endif; ?>
<?php endif; ?>
</div>
<script src="<?php echo chat_asset('js/chat.js'); ?>"></script>
</body>
</html>
