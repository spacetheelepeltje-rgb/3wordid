<?php
/**
 * Deleting chat messages (3wid_message_delete.php, and own messages in 3wid_chat.php).
 *
 * Deleting never removes data: db_message_delete() copies the row to
 * 3wordid_messages_deleted first (kept, e.g. for the police) and only then removes it from
 * 3wordid_messages. The message is then gone from the chat on BOTH sides: for the account
 * that deleted it and for the other person.
 *
 * Who may delete a message:
 *   received  the owner of the 3WordID it was sent to (as before)
 *   sent      the account that sent it (from_id), or the owner of the 3WordID it was sent from;
 *             always, also after it has been read
 */

/** One message row without joins (also when the sender account or 3WordID is gone), or null. */
function db_message_row(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $conn = get_db_conn();
    if (!$conn || $conn->connect_error) {
        return null;
    }
    $stmt = $conn->prepare('SELECT id, 3wid_id, 3wid_from_id, from_id, to_id FROM 3wordid_messages WHERE id = ?');
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Why $user_id may delete message $row (from db_message_row): 'received', 'sent', or '' (not allowed).
 * 'received' wins when both apply (a message from one of your 3WordIDs to another).
 */
function message_delete_role(?array $row, int $user_id): string
{
    if (!$row || $user_id < 1) {
        return '';
    }
    if (db_3wordid_owned_by((int) $row['3wid_id'], $user_id)) {
        return 'received';
    }
    if ((int) $row['from_id'] === $user_id
        || ((int) $row['3wid_from_id'] > 0 && db_3wordid_owned_by((int) $row['3wid_from_id'], $user_id))) {
        return 'sent';
    }
    return '';
}

/**
 * Where to go after deleting $row: the conversation it was in, seen from the side of the
 * account that deleted it ($role from message_delete_role). When that conversation is now
 * empty, 3wid_list_messages.php shows the list of conversations (or My 3WordIDs).
 */
function message_delete_return_url(array $row, string $role, int $user_id): string
{
    if ($role === 'received') {
        $from_wid = (int) $row['3wid_from_id'];
        if ((int) $row['from_id'] === (defined('CHAT_VISITOR_USER_ID') ? CHAT_VISITOR_USER_ID : 25)) {
            $key = 'visitors';
        } elseif ($from_wid < 1 || (string) db_3wordid_threeword_name($from_wid) === '') {
            $key = 'deleted';
        } else {
            $key = 'w' . $from_wid;
        }
        return '3wid_list_messages.php?wid_id=' . (int) $row['3wid_id'] . '&with=' . rawurlencode($key);
    }
    $from_wid = (int) $row['3wid_from_id'];
    if ($role === 'sent' && $from_wid > 0 && db_3wordid_owned_by($from_wid, $user_id)) {
        return '3wid_list_messages.php?wid_id=' . $from_wid . '&with=' . rawurlencode('w' . (int) $row['3wid_id']);
    }
    return '3wid_list.php';
}
