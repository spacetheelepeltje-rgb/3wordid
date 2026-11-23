<?php
/**
 * Notification hook for later SMS (and other channels).
 *
 * Bumperchat stores an optional E.164 mobile number and an explicit SMS
 * consent flag on the plate (3wordid.notify_phone / notify_sms_consent).
 * Sending is not implemented yet. All future sending must go through
 * notify_dispatch() in this file, not from claim/chat/pay pages.
 *
 * notify_on_new_message() is the hook called after a message is stored.
 */

/** True when the plate has a valid number and the owner consented to SMS. */
function notify_sms_allowed(?array $wid): bool
{
    if (!$wid) {
        return false;
    }
    if ((int) ($wid['notify_sms_consent'] ?? 0) !== 1) {
        return false;
    }
    $phone = function_exists('phone_store_plain') ? phone_store_plain($wid) : (string) ($wid['notify_phone'] ?? '');
    return notify_phone_is_e164($phone);
}

function notify_phone_is_e164(string $phone): bool
{
    return (bool) preg_match('/^\+[1-9]\d{7,14}$/', $phone);
}

/**
 * Normalise a typed phone number to E.164, or return:
 *   ['ok' => true, 'phone' => null]     empty (optional field unused)
 *   ['ok' => true, 'phone' => '+31...'] valid
 *   ['ok' => false, 'error' => 'bc.phone_err']
 *
 * Spaces, dashes, parentheses and dots are stripped. A leading 00 becomes +.
 * A local number without a country code (e.g. 06...) is not accepted.
 */
function notify_phone_normalize(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return ['ok' => true, 'phone' => null, 'error' => ''];
    }
    $compact = preg_replace('/[\s().-]+/', '', $raw);
    if (str_starts_with($compact, '00')) {
        $compact = '+' . substr($compact, 2);
    }
    if (notify_phone_is_e164($compact)) {
        return ['ok' => true, 'phone' => $compact, 'error' => ''];
    }
    return ['ok' => false, 'phone' => null, 'error' => 'bc.phone_err'];
}

/** Read phone + SMS consent from a Bumperchat claim form POST. */
function notify_read_claim_post(array $post): array
{
    $parsed = notify_phone_normalize((string) ($post['notify_phone'] ?? ''));
    if (!$parsed['ok']) {
        return $parsed + ['consent' => false];
    }
    $consent = !empty($post['notify_sms_consent']);
    if ($parsed['phone'] !== null && !$consent) {
        return ['ok' => false, 'phone' => $parsed['phone'], 'consent' => false, 'error' => 'bc.sms_consent_needed'];
    }
    if ($consent && $parsed['phone'] === null) {
        return ['ok' => false, 'phone' => null, 'consent' => false, 'error' => 'bc.phone_err'];
    }
    return ['ok' => true, 'phone' => $parsed['phone'], 'consent' => $consent, 'error' => ''];
}

/**
 * Whether an SMS provider is configured. Always false until sending is built.
 * A later change should only flip this (and notify_dispatch), not the hook call sites.
 */
function notify_sms_configured(): bool
{
    return false;
}

/**
 * Hook: a new message was stored for 3WordID $wid_id.
 * Does not send. Does not log the phone number.
 */
function notify_on_new_message(int $wid_id, int $from_user_id = 0): void
{
    if ($wid_id <= 0 || !function_exists('db_3wordid_get')) {
        return;
    }
    if (!function_exists('billing_schema_ready') || !billing_schema_ready()) {
        return;
    }
    $wid = db_3wordid_get($wid_id);
    if (!notify_sms_allowed($wid) || !notify_sms_configured()) {
        return;
    }
    notify_dispatch('sms', [
        'event' => 'new_message',
        'wid_id' => $wid_id,
        'user_id' => (int) ($wid['user_id'] ?? 0),
        'from_user_id' => $from_user_id,
    ]);
}

/**
 * Single outbound dispatcher. Empty until an SMS provider is added.
 * $payload must not include the phone number (look it up at send time).
 */
function notify_dispatch(string $channel, array $payload): void
{
    // Intentionally empty: no provider is configured.
}
