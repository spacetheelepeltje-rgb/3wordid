# Licence plate → three words (Bumperchat)

Bumperchat (`/bumperchat/`) lets people use a licence plate as a 3WordID. A 3WordID is always
**three words of letters and digits**, so every plate has to become exactly three words.
The rules live in `bumperchat/plate_formats.json` (easy to edit; the page reads it directly) and
the conversion code in `js/bumperchat.js` (`plateToWords()` / `formatPlate()`) and the matching PHP
in `php/bumperchat.php`. This formatter is used only on the Bumperchat chain (`/bumperchat/` index,
claim and onboarding). The main site (`/index.php`) and the create form (`3wid_add.php`) do not
apply plate formatting: three words stay as typed, split only on spaces.
`node tools/test_bumperchat_plates.js` and `php tools/test_bumperchat_flow.php` check the examples
and the official mixed-group cases.

## The general rule

1. Type the plate **as printed**: the groups on the plate are separated by a space, dash or dot
   (emblems, seals, stickers and the blue EU strip are left out).
2. If the plate already has **three groups**, those are the three words (Netherlands, France,
   Germany, Italy, ...). Example: `AB-123-C` → `ab 123 c`.
3. If the plate has **two groups**, the code from the plate's blue EU strip goes in front
   (Finland `ABC-123` → `fin abc 123`). We use the strip code because people can read it on the plate.
4. **United States / Canada**: pick the state / province. One block → `us [state] [plate]`
   (`cdn [province] [plate]` for Canada); two groups → `[state] [group 1] [group 2]`.
5. Everything is lower case; letters with accents are typed without them (Č → c). Cyrillic / Greek
   letters on Bulgarian / Greek plates are typed as the Latin letter that looks the same.

Modes in the JSON: `three` (three groups as printed; `fixed` = lengths used when typed without
spaces), `prefix` (strip code + two groups; `three_ok` = an older plate with three groups is kept as
it is), `mixed` (three groups as printed, else strip code + two groups), `region` (US / Canada).

`group_patterns` lists official group layouts for a country (character classes only). On the
Bumperchat index, claim and onboarding, when a country is selected and has patterns, the formatter
follows that format while typing, including groups that mix letters and digits (Czech `1A2`,
Swedish `12A`, Polish `1234A`, German `123E`). A compact plate is split only when every official
reading agrees on the same groups. The generic letter/digit split is used when no country format
is known, or when the official groups are themselves a run of letters or of digits (so the
transition is the official boundary). Region plates (US / Canada) are kept as printed: one block
stays one block. If the user already typed exactly three groups, those stay the three words.

## All countries

⚠️ = part of the rule is our own convention (not an official format), please review.

| Country | Strip | Mode | Format | Examples | Rule | Source |
|---|---|---|---|---|---|---|
| Austria | A | three | `XX 123 AB` | `W 123 AB` → `w 123 ab`; `GU 12 ABC` → `gu 12 abc` | District code, then the numbers, then the letters (or for a personalised plate: district code, letters, number). *The exact split of the serial (how many digits and letters) varies by district; the rule "district · digits · letters" is our own convention.* ⚠️ | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Austria) |
| Belgium | B | mixed | `1-ABC-234` | `1-ABC-234` → `1 abc 234`; `ABC-123` → `b abc 123` | Current plates (since 2010) have three groups: digit, letters, digits. Older six-character plates (ABC-123 or 123-ABC) have two groups: put B in front. *Personalised Belgian plates do not follow a fixed notation and may not fit this rule.* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Belgium) |
| Bulgaria | BG | three | `A[A] 1234 AA` | `CA 1234 AB` → `ca 1234 ab`; `B 1234 KM` → `b 1234 km` | Region code, four digits, two letters. Bulgarian plates use Cyrillic letters that look like Latin ones: type the Latin letter that looks the same. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Bulgaria) |
| Canada | CDN | region | `[province] ABCD 123` | `ON · ABCD 123` → `on abcd 123`; `QC · 123ABC` → `cdn qc 123abc` | Pick the province or territory. One block becomes CDN [province] [plate]. Two groups become [province] [group 1] [group 2]. *We use CDN (Canada's international vehicle code) instead of CA, because CA is also the code of California.* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Canada) |
| Croatia | HR | three | `ZG 123-AB` | `ZG 1234-AB` → `zg 1234 ab`; `ST 123-A` → `st 123 a` | City code, digits, letters (the coat of arms between the city code and the digits is left out). *City codes with Č or Ž (e.g. ČK) are typed without the accent: ck.* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Croatia) |
| Cyprus | CY | prefix | `ABC 123` | `ABC 123` → `cy abc 123` | The plate has two groups: put the country letters from the blue strip (CY) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Cyprus) |
| Czechia | CZ | prefix | `1A2 3456` | `1A2 3456` → `cz 1a2 3456`; `1AA 0001` → `cz 1aa 0001` | The plate has two groups: put the country letters from the blue strip (CZ) in front. Keep the first group (digit, region letter, then a letter or digit) together, including the later `1AA 0000` typography. *Personalised Czech plates can look different.* | [vyhláška 343/2014 Sb.](https://www.zakonyprolidi.cz/cs/2014-343); [region codes (Ministerstvo dopravy)](https://md.gov.cz/Dokumenty/Silnicni-doprava/Registrace-vozidel/Kody-kraju-registracni-znacky) |
| Denmark | DK | three | `AB 12 345` | `AB 12 345` → `ab 12 345` | Two letters, then the five digits as printed in two groups (2 + 3). *Danish plates print the digits as "12 345"; if a plate shows them as one block, split them 2 + 3.* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Denmark) |
| Estonia | EST | prefix | `123 ABC` | `123 ABC` → `est 123 abc` | The plate has two groups: put the country letters from the blue strip (EST) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Estonia) |
| Finland | FIN | prefix | `ABC-123` | `ABC-123` → `fin abc 123` | The plate has two groups: put the country letters from the blue strip (FIN) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Finland) |
| France | F | three | `AB-123-CD` | `AB-123-CD` → `ab 123 cd`; `1234 AB 75` → `1234 ab 75` | The plate already has three groups: type them as printed, with a space between the groups. Old plates (until 2009, e.g. 1234 AB 75) also have three groups. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_France) |
| Germany | D | three | `M AB 1234` | `M AB 1234` → `m ab 1234`; `B XY 123E` → `b xy 123e` | City/district code (1–3 letters), identifying letters (1–2), digits (1–4). An official E (electric) or H (historic) suffix stays in the number group, with no space (`123E`, not `123-E`). Compact plates without separators are not auto-split, because the city code length is otherwise ambiguous. | [FZV 2023](https://www.gesetze-im-internet.de/fzv_2023/BJNR0C70B0023.html) (§ 9–11, Anlage 4) |
| Greece | GR | prefix | `ABC-1234` | `ABC-1234` → `gr abc 1234` | The plate has two groups: put the country letters from the blue strip (GR) in front. Greek plates only use letters that look like Latin ones: type the Latin letter. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Greece) |
| Hungary | H | prefix | `AA AA-123` | `AA BC-123` → `h aabc 123`; `ABC-123` → `h abc 123` | Put H in front, then all letters as one word, then the digits. New plates (since 2022, AA AA-123) and old ones (ABC-123) follow the same rule. *Joining the four letters of the new format into one word is our own convention.* ⚠️ | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Hungary) |
| Ireland | IRL | three | `251-D-12345` | `251-D-12345` → `251 d 12345`; `08-KY-1234` → `08 ky 1234` | Year (and half-year), county letters, number: three groups as printed. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_the_Republic_of_Ireland) |
| Italy | I | three | `AB 123 CD` | `AB 123 CD` → `ab 123 cd` | The plate already has three groups: type them as printed, with a space between the groups. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Italy) |
| Latvia | LV | prefix | `AB-1234` | `AB-1234` → `lv ab 1234` | The plate has two groups: put the country letters from the blue strip (LV) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Latvia) |
| Lithuania | LT | prefix | `ABC 123` | `ABC 123` → `lt abc 123` | The plate has two groups: put the country letters from the blue strip (LT) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Lithuania) |
| Luxembourg | L | prefix | `AB 1234` | `AB 1234` → `l ab 1234`; `12345` → `l 12 345` | The plate has two groups: put the country letters from the blue strip (L) in front. Plates with only digits: L, the first two digits, the rest. *The digits-only split (2 + rest) is our own convention.* ⚠️ | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Luxembourg) |
| Malta | M | prefix | `ABC 123` | `ABC 123` → `m abc 123` | The plate has two groups: put the country letters from the blue strip (M) in front. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Malta) |
| Netherlands | NL | three | `AB-123-C` | `AB-123-C` → `ab 123 c`; `1-KBB-00` → `1 kbb 00`; `GZ-12-KL` → `gz 12 kl` | Always three groups: type the groups between the dashes, with a space instead of each dash. *Dutch plates can be checked in the RDW register (yes/no only).* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_the_Netherlands) |
| Poland | PL | prefix | `WA 12345` | `WA 12345` → `pl wa 12345`; `KR 1234A` → `pl kr 1234a` | The plate has two groups: put the country letters from the blue strip (PL) in front. The district code is two or three letters; the vehicle identifier follows the official layouts (which may mix letters and digits) in the 2024 ordinance. | [Dz.U. 2024 poz. 1709, § 30](https://eli.gov.pl/api/acts/DU/2024/1709/text.html) |
| Portugal | P | three | `AB-12-CD` | `AB-12-CD` → `ab 12 cd`; `12-AB-34` → `12 ab 34` | The plate already has three groups: type them as printed, with a space between the groups. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Portugal) |
| Romania | RO | three | `CJ 12 ABC` | `CJ 12 ABC` → `cj 12 abc`; `B 123 ABC` → `b 123 abc` | County code, digits, letters. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Romania) |
| Slovakia | SK | three | `AB 123 CD` | `AB 123 CD` → `ab 123 cd`; `BA-123AB` → `ba 123 ab` | Official groups are letters, digits, letters (current `AA 123 BB` and older `BA-123AB`). These groups are homogeneous, so a compact older plate still splits `123AB` into `123` and `AB`. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Slovakia) |
| Slovenia | SLO | three | `LJ 123-AB` | `LJ 123-AB` → `lj 123 ab`; `KR 12-ABC` → `kr 12 abc` | Area code, then the two groups around the dash (leave out the coat of arms). | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Slovenia) |
| Spain | E | prefix | `1234 BCD` | `1234 BCD` → `e 1234 bcd`; `M-1234-AB` → `m 1234 ab` | The plate has two groups: put the country letters from the blue strip (E) in front. Old provincial plates (until 2000, e.g. M-1234-AB) already have three groups. | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_Spain) |
| Sweden | S | prefix | `ABC 12A` | `ABC 123` → `s abc 123`; `ABC 12A` → `s abc 12a` | The plate has two groups: put the country letters from the blue strip (S) in front. Since 16 January 2019 the last character may be a digit (`ABC 123`) or a letter (`ABC 12A`); that last group is kept together. | [TSFS 2019:53](https://www.transportstyrelsen.se/TSFS/TSFS%202019_53.pdf) |
| United States | US | region | `[state] ABC 1234` | `CA · 7ABC123` → `us ca 7abc123`; `NY · ABC-1234` → `ny abc 1234` | Pick the state. A plate printed as one block stays one block (`7ABC123` is not split into `7 ABC 123`). A plate printed in two groups (with a space or dash) becomes [state] [group 1] [group 2]. *Each state has its own formats and many vanity plates; the state is not printed as a code, so pick it from the list.* | [Wikipedia](https://en.wikipedia.org/wiki/Vehicle_registration_plates_of_the_United_States) |

General source for the EU formats: [Wikipedia: European vehicle registration plate](https://en.wikipedia.org/wiki/European_vehicle_registration_plate)
(section "Format", read on 12 November 2026). Mixed-group official sources (Czechia, Germany,
Poland, Sweden) are linked in the table; other country pages still use the Wikipedia links.

## Honest limits

- The formats are taken from public sources and, where mixed letter/digit groups needed an official
  specification, from the statutes and agencies linked in the table. They cover the **current standard
  private car plates**, plus the most common older series. Diplomatic, military, trade, temporary,
  motorcycle and personalised plates may not fit.
- Austrian serials, Hungary (letters joined into one word), Luxembourg digits-only plates and the
  CDN prefix for Canada are **our own conventions** (marked ⚠️ or explained in the note).
- **Collisions between countries are possible**: an Italian `AB 123 CD`, a French `AB-123-CD`
  and a Slovak `AB 123 CD` all become `ab 123 cd`; a Dutch and a Portuguese `AB-12-CD` both become
  `ab 12 cd`. Only one person can own a 3WordID. A country prefix for every plate would avoid this,
  but would make four words. Open question for the owner.
- Only Dutch plates are checked against an official register (RDW, yes/no only). For other
  countries nobody checks that the plate exists or belongs to the person claiming it.
