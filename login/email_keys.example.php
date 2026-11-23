<?php
/**
 * EXAMPLE ONLY — do not fill in real keys here, and do not rename this file
 * to config.php. Copy the two define() lines into the server-only file
 * login/config.php (gitignored) and paste keys you generated on the server.
 *
 * Generate each key once (run the command twice, keep the two results):
 *
 *     php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 *
 * EMAIL_ENC_KEY  encrypts email addresses and mobile numbers at rest
 *                (libsodium secretbox). Use one key for both.
 * EMAIL_HASH_KEY keyed hash of the email, used only to find an account
 *                at login. Must be different from EMAIL_ENC_KEY.
 *
 * Never commit a key, never print a key in a ticket, log or chat.
 */

// define('EMAIL_ENC_KEY',  'PASTE_BASE64_32_BYTE_KEY_HERE');
// define('EMAIL_HASH_KEY', 'PASTE_A_DIFFERENT_BASE64_32_BYTE_KEY_HERE');
