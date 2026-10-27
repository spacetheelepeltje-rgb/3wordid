# 3WordID

3WordID.com links three words to a URL, a notification or a message box. PHP and MySQL.
See `ROADMAP.md` for what is planned.

## Languages

All pages (public, logged-in and admin) are translated with a small, plain-PHP system (`php/i18n.php`).

- Texts live in `lang/<code>.php`. Each file returns an array of `'key' => 'text'`.
- `lang/en.php` is the source and the fallback: a key missing in another language is shown in English.
- In a page use `t('key')` (text is HTML-escaped) or `t_raw('key')` (text that contains intended markup such as links or `<b>`).
  Placeholders look like `{n}`: `t('list.create_title', ['n' => 5])`. Singular/plural: `t_n('home.views', 5)` uses the keys `home.views_one` and `home.views_other`.
- Messages that travel in a redirect URL (`?message=...`) or JSON use `t_plain('key')` (no escaping) and are written in the visitor's current language.
- The language is chosen from `?lang=nl`, then the earlier choice in the session, then the browser's
  `Accept-Language` header, then English. There is no IP lookup and no extra cookie.
- Names of 3WordIDs typed by users are never translated.

### Add a language

1. Copy `lang/en.php` to `lang/<code>.php` (for example `lang/de.php`) and translate the texts on the right of each `=>`.
   Keep the keys, the `{placeholders}` and the HTML tags as they are.
2. Add the code to `i18n_languages()` in `php/i18n.php` (for example `['en', 'nl', 'de']`).
3. Upload both files. The language switch in the header shows the new code automatically.

### Convert another page

Add `<html lang="<?php echo current_lang(); ?>">`, replace each visible text with `t('...')` / `t_raw('...')`,
add the keys to `lang/en.php` and the other languages, and add `<?php echo lang_head_tags(); ?>` inside `<head>`
and `<?php echo lang_switcher(); ?>` in the header. Texts used by JavaScript are passed with `i18n_js([...])`.
The form scripts in `js/` use keys that start with `js.`: put `<?php echo i18n_js_all(); ?>` before the script tag and
write `tr('name', 'English fallback')` in the script (`js.name` in the language files).
The language stays in the session, also across login and logout (`login/logout.php` passes `?lang=` on).

Pages that are not in this repository (server-only files) get the switch with `require_once` of `php/i18n.php`,
`<html lang="<?php echo current_lang(); ?>">` and `<?php echo lang_switcher(); ?>`.
