# Converting a file to translation keys (I18N-01 Phase A)

**The goal:** every text a person reads becomes a translation key, and **the English stays exactly the same on the page**.
Nothing else changes: not the markup, the classes, the logic or the layout. The worked example is
`resources/views/auth/login.blade.php` with `lang/en/auth/login.php` and `lang/en/auth/common.php`.

## 1. Where the English goes

- **A view's keys live in a group named after the view's path** (from `resources/views/`, without `.blade.php`):
  `resources/views/livewire/owner/statement.blade.php` → `lang/en/livewire/owner/statement.php` → `__('livewire/owner/statement.net_payable')`.
- **A PHP class's keys live under `php/` + its path after `App\`, each part in snake_case:**
  `App\Http\Controllers\Owner\OwnerSecurityController` → `lang/en/php/http/controllers/owner/owner_security_controller.php` →
  `__('php/http/controllers/owner/owner_security_controller.password_changed')`.
- **The generic words** are in `lang/en/common.php` (`common.save`, `common.cancel`, `common.close`, `common.status` …). Use one only
  where the English is exactly that word in that sense. Never add to `common.php` yourself.
- The file's group file starts with a one-line comment naming the source file, then `return [ … ];`, keys in the order they appear.
- **Key names:** snake_case, saying what the text IS (`heading`, `empty_state`, `pay_button`, `refund_confirm`), at most ~40
  characters, unique in the file. **One key per meaning:** "Book" the verb and "Book" the noun are two keys; so are "Open" the state
  and "Open" the action, "Balance", "Rate", "Stay", "Block".

## 2. What to convert

Every text a person sees or hears: text between tags, `<title>`, and the attributes `placeholder`, `title`, `aria-label`, `alt`,
`label`, `data-confirm`, `wire:confirm`, the `value` of a submit/button input, the meta description, text props passed to
components (`title="…"` → `:title="__('…')"`), `@props` defaults, strings in `@php` / `{{ }}` that are shown (`$x ? 'Paid' : 'Due'`),
strings in `<script>` and in Alpine attributes that end up on screen, and in PHP: flash messages (`->with('status', …)`), validation
messages, `addError`, labels returned by `label()`-style methods and `match` arms, mail subjects and lines, notification titles, and
exception messages that reach a person's screen (`withErrors`, `abort(403, '…')`, `ValidationException::withMessages`).

**Do NOT convert:** brand and product names on their own (Vyrobel, Google, X, Stripe, Booking.com, Airbnb, Channex), codes and
identifiers, CSS classes, route/view/config names, log messages and exceptions meant for developers, comments, people's content
and anything read from the database, currency codes, and the legal documents (`pdfs/owner-agreement*.blade.php`,
`pdfs/owner-annual-tax.blade.php`, terms, privacy) and the marketing pages. Leave those exactly as they are.

## 3. The rules (each one caused a real bug in hrmzy)

1. **One key per whole sentence or phrase.** Never put two keys side by side to make a sentence (`{{ __('a') }} {{ __('b') }}`):
   word order differs between languages. A value inside a sentence is a **placeholder**: `__('…paid_of', ['amount' => $paid, 'total' => $total])`
   with the English `'Paid :amount of :total'`. Placeholder names are snake_case words, never `:count` unless it is the plural count.
2. **Plurals** use `trans_choice('…nights', $n, ['count' => $n])` with the English written so EVERY n prints exactly what it printed
   before, e.g. `'{1} :count night|[0,*] :count nights'`. Check what 0 printed before.
3. **A sentence with markup inside** (a link, `<strong>`) is ONE key with the markup in the value and placeholders for anything
   dynamic: `'new_to_vyrobel' => 'New to Vyrobel? <a class="vy-forgot" href=":url">Create an account</a>'`, printed with
   `{!! __('…', ['url' => e(route('signup'))]) !!}`. **Every replacement printed through `{!! !!}` is wrapped in `e()`** (data printed raw
   is a security hole); only a static string or another `__()` may go unwrapped. Everything else uses `{{ __('…') }}`.
4. **The page must read exactly as before.** Write the value as it READS: an entity in the old source (`&amp;`, `&mdash;`, `&nbsp;`)
   becomes the character itself (`&`, `—`, a no-break space); line breaks and runs of spaces inside one text become one space.
   Keep typographic quotes and apostrophes exactly as the old text had them.
5. **JavaScript:** a string a script or Alpine shows becomes `@js(__('…'))` (safe inside a double-quoted attribute and inside
   `<script>`). A value inside it: keep the placeholder in the translation and replace it in JS (`@js(__('…', ['n' => ':n'])).replace(':n', n)`).
6. **Never key a PHP array by a translated label** (`[__('a') => …]`): two labels that translate the same collapse and an option disappears.
7. **Translate at render time.** Never call `__()` in a class constant, a static property, a constructor or anything that runs
   before the request's language is set; in a Livewire class, inside `render()` or the method that produces the text.
8. **Dates people read:** `->format('…')` with a month or day name (`M`, `F`, `D`, `l`) becomes `->translatedFormat('…')` with the
   same format (identical in English). Formats for machines (`Y-m-d` in a value, an id, a URL) stay `format`.
10. **Never make logic depend on a displayed text.** A script that searches a button's text (`textContent.indexOf('pay')`) or a
   view that compares a label (`$line['label'] === 'Commission'`) breaks in every other language. Compare a code instead: a flag the
   server already has, or a `kind`/`code` field added beside the label in the PHP that builds it. If such a comparison reads a label
   from a file outside your batch, report it rather than translating that label.
11. **Write every key out in full.** A key built by joining strings (`__('x.status_'.$code)`) cannot be checked (it reads as
   missing) or found by lang:todo; use a `match` keyed by the code that names each key.
12. **A plural a script shows is worked out on the server.** Never `n === 1 ? 'night' : 'nights'` in JS (wrong for languages with
   three or more plural forms): build the text with `trans_choice` for every count the page can show (a map 0…max), and let the
   script pick from the map.
13. **Inside a translation value never write an Alpine `:attr` shorthand or `@event`** — `:href` reads as a placeholder. Use
   `x-bind:href` / `x-on:click`; keep such markup out of values where you can (build it as a `$…Html` variable from static
   markup and `e()`/`Js::from()` parts, and pass it in as a placeholder).
14. **Notifications (app/Notifications):** every text `data()` returns is made with `$this->t('title', 'php/notifications/…key', [params])`
   (FeedNotification records the key so the bell shows each READER's language). Parameters are plain strings/numbers already
   formatted (names, dates via translatedFormat, money via MoneyFormatter). The category stays a code.
15. **Mailables (app/Mail + resources/views/emails):** subject, greeting, lines and buttons become keys, translated where the Mailable
   builds them (envelope()/content()/build() or the view) — never in the constructor. The sender already sets the recipient's language
   (`Mail::to(…)->locale(…)`), so `__()` there is in the recipient's language. Data (names, amounts, references) are placeholders.
9. **Blade traps (CLAUDE.md):** never write a directive straight after a word character (`word@if`); never add a public Livewire
   property; don't touch `wire:` / `x-` logic beyond its displayed strings.

## 4. Check, then hand back

Run `php ops/i18n/check-file.php <file> …` on every file you changed: it must print `OK`. It lists the English still hardcoded:
convert each, or name it in your report as not text (brand, code, data) with the reason. For PHP also `php -l <file>`.
Do NOT run the test suite, the render tools, Pint, or any git command that changes state — the orchestrator does that.
Touch only the files you were given and their own group files under `lang/en/`.

**Report (under 250 words):** files done · keys added · what you left hardcoded and why · anything you were unsure of.
