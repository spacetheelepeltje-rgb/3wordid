# Checking an upload (deploy check)

The site is deployed by uploading files by hand. `deploy_check.php` on the server lists a
fingerprint (SHA-256) of every site file, and `tools/deploy_compare.php` compares that list
with the repo. It shows which files are missing on the server, which differ from the repo,
and which exist only on the server.

## One-time setup on the server

1. Make a long random key, for example with:

       php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"

2. Add this line to `login/config.php` on the server (that file is not in the repo):

       define('DEPLOY_CHECK_KEY', 'paste-the-long-random-key-here');

3. Upload `deploy_check.php` to the web root (next to `index.php`).

Without that line (or with a key shorter than 16 characters) the page answers "404 Not Found",
so it is switched off. Remove the line to switch it off again.

## What the page shows and what it doesn't

- It only answers when the key is sent as the POST field `key` or the `X-Deploy-Key` header.
  A key in the URL is ignored, so it never appears in access logs. A wrong key gets a 404 after a short delay.
- It returns file names and fingerprints only: no file contents, no settings, no passwords.
- Included: `.php`, `.js`, `.css`, `.html` files and `.htaccess` files.
- Left out: `login/config.php`, `errors.php`, hidden files and folders (such as `.git`), symlinks,
  and the folders `vendor`, `login/vendor`, `node_modules`, `qr_codes`, `logos`, `uploads`,
  `logging`, `backups`, `old`, `old_april`, `old_error`, `tools` and `sql`.
- Fingerprints are taken after turning Windows line endings (CRLF) into LF, so a file uploaded in
  FTP "ASCII" mode doesn't show up as different. Any other change does.

## Comparing (on your own computer)

From a checkout of this repo:

    git fetch origin
    DEPLOY_CHECK_KEY='the-key' php tools/deploy_compare.php

Options: `--ref=origin/main` (the git branch or commit to compare with, default `origin/main`) and
`--url=https://3wordid.com/deploy_check.php` (also settable with the `DEPLOY_CHECK_URL` environment variable).
The key is read from the environment and is never printed. Exit code: 0 all fine, 1 files missing or
different, 2 the check itself failed (no connection, wrong key, unknown ref).

`3wid_show_qr.php` lives only on the server, so it is listed under "only on the server" as expected.
`tools/deploy_compare.php` and this file don't need to be uploaded.
