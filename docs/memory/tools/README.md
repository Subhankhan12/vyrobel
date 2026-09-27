# Tools that are not in `ops/`: read-only production instruments

Saved by MEMORY 01 (2026-09-27) so they outlive the session that wrote them. **The PHP ones are stored as `*.php.txt`**:
the repo's style gate (`./vendor/bin/pint --test`) lints every `.php` file, `docs/` included, and would go red on them.
The bytes are unchanged. **Copy one to its `.php` name to run it.** **Verified:** `census.php.txt`, `footprint.php.txt` and
`proof.php.txt` are byte-identical (sha256) to the `.php` copies on the server in
`~/domains/vyrobel.com/env-backups/`. **Every one only reads.** None prints a secret, and none prints a personal value;
they print counts, sums, names of tables and settings, and yes/no.

Run each one **from inside the live release**, so it uses the app's own configuration, with the 8.4 binary:
`cd ~/domains/vyrobel.com/public_html/current && /opt/alt/php84/usr/bin/php <script> …`
(or `php artisan tinker --execute="require '<path>';"` for `prodstate.php`). The host's default `php` is 8.3, and
artisan exits 255 under it.

| Tool | What it answers | Use |
|---|---|---|
| `census.php.txt` | Every table's row count and the money sums, per organisation, for the whole database | `php census.php <out.json> <organisation id>`, before and after a deletion. The PW-SIGNUP baseline is `census-1-before.json` (2026-09-26 19:04 UTC). |
| `footprint.php.txt` | Every row, in EVERY table, that still names one organisation, its properties or its accounts. It sees rows whose organisation no longer exists, which counting per organisation cannot. | `php footprint.php before <ids.json> <out.json> <organisation id>`, then `php footprint.php after <ids.json> <out.json>` once the delete is done |
| `proof.php.txt` | The full before/after proof of one deletion: premises, controls, per-organisation counts with content fingerprints, orphan checks, the footprint and the people | `php proof.php before <organisation id> <dir>` (saves the ids), then `php proof.php after <dir>` (run it twice), then compare the two |
| `compare.py` | Judges a before and an after proof; prints every difference and exits 1 on any it cannot explain | Local: `python3 compare.py before.json after.json` |
| `prodstate.php.txt` | Runtime switches (yes/no only), the kill switches, organisations and their status, customer count | Inside `php artisan tinker`. This is how `../STATE.md` was read. |

⚠️ **The two `*-ids.json` files on the server hold the probe's address. They are not copied here, and are deleted after
the final after-run** (`../OPEN.md` A3). Treat anything these tools write as private: keep it in the 700 folder, mode
600.
