<?php
/**
 * How a 3WordID is split into words (create form, insert, exists, lookup).
 *
 * Only whitespace separates groups. A mixed group like "2562AE" is one word;
 * a single character like "0" is allowed. Groups are never split where letters
 * and digits change. That split is only used by Bumperchat plate formatting
 * when the plate does not already have three groups.
 */

/** @return list<string> */
function threeword_split(string $threeword): array
{
    $threeword = trim($threeword);
    if ($threeword === '') {
        return [];
    }
    $parts = preg_split('/\s+/u', $threeword, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($parts) ? $parts : [];
}

/**
 * Why a typed 3WordID is not accepted, as a lang key (null = fine so far).
 * While typing ($complete = false), fewer than three words is not an error.
 * Free accounts may only use letters; paying accounts may use letters and digits.
 */
function threeword_error(string $threeword, bool $paying, bool $complete = true): ?string
{
    $threeword = trim($threeword);
    if ($threeword === '') {
        return $complete ? 'js.add_need_3wid' : null;
    }
    $parts = threeword_split($threeword);
    foreach ($parts as $part) {
        if (!$paying && preg_match('/[0-9]/', $part)) {
            return 'js.add_err_digits';
        }
        if (!preg_match($paying ? '/^[a-zA-Z0-9]+$/' : '/^[a-zA-Z]+$/', $part)) {
            return $paying ? 'js.add_err_chars_paid' : 'js.add_err_chars';
        }
    }
    if (count($parts) > 3 || ($complete && count($parts) !== 3)) {
        return 'js.max_three';
    }
    return null;
}
