#!/usr/bin/env bash
#
# ops/release.sh — KNOWN-LIMITS L12k, the release layout, AS A SCRIPT. The procedure is unchanged; this only
# stops a person (or a session) from skipping a step. ⚠️ Nothing here decides anything — every step reads.
#
#   ops/release.sh status                 which release is live (READ from .release), which are retained
#   ops/release.sh stage   rel_<X>        build the new release beside the live one (never touches the live one)
#   ops/release.sh stage   rel_<X> --with-vendor   the same, AND rebuild vendor/ from composer.lock (only when the lock changed)
#   ops/release.sh recache rel_<X>        the SAME code, config + routes rebuilt from the .env as it is now (L14)
#   ops/release.sh migrate rel_<X>        run PENDING migrations from the new release — ADDITIVE ONLY, checked
#   ops/release.sh swap    rel_<X>        the one atomic step: repoint `current`
#   ops/release.sh rollback rel_<X>       repoint `current` back (the previous release is never modified)
#
# ⚠️⚠️ THE TRAPS THIS ENCODES, EACH ONE MEASURED:
#   · `cp -al` makes every unchanged file a HARDLINK shared with the live release — so a file is never written
#     in place: it is staged, then `rm -f` + `cp` (a new inode). `scp` straight onto it would edit the LIVE
#     release mid-upload (L12k note, 2026-09-24).
#   · `bootstrap/cache/config.php` bakes ABSOLUTE PATHS (`view.paths` and 19 more). A copied cache serves the
#     PREVIOUS release's views while the new code runs — 200 OK, byte-identical, nothing red. So config:cache
#     and route:cache are rebuilt in EVERY stage, whether or not any config value changed.
#   · the commit is READ from git and written to `.release` by the script — never typed.
#   · a migration is ADDITIVE or it is refused here: its up() may not drop, rename or change a column. That is
#     what lets the old release keep running on the new schema, and keeps rollback a symlink.
#   · a migration is "pending" when its STATUS column says Pending — not when its NAME contains the word
#     (`…add_pending_email…` fooled a case-insensitive grep once, on the first run of this script).
#   · anything needing a vendor/ or public/build rebuild (composer.*, package*.json, resources/css|js) is REFUSED
#     here — those are built on the target / off-host by a separate, deliberate step, never slipped into a copy.
#   · ⚠️⚠️ THAT DELIBERATE STEP FOR vendor/ IS `stage rel_<X> --with-vendor` (VY-11, 2026-11-24 — until then no such step
#     existed, so a dependency update could not be released at all). A composer.json / composer.lock change is STILL
#     refused without the flag, and the flag is refused when the lock did NOT change. What it does:
#       1. builds vendor/ OFF-HOST, in a clean export of the commit being staged: `composer install --no-dev
#          --optimize-autoloader --no-scripts` — from the committed composer.lock, never from the working folder's vendor/
#          (which holds the dev packages and whatever was last installed by hand);
#       2. refuses if `composer audit --locked` still reports an advisory for that lock (the reason to rebuild is to clear them);
#       3. uploads it as ONE archive, digest-checked;
#       4. on the host: `rm -rf` the NEW release's vendor/ — its files are HARDLINKS shared with the live release, and
#          removing a link never touches the file the live release still holds — then unpacks the archive: every file a new
#          inode. Composer is deliberately NOT run in place on the host: it rewrites vendor/composer/*.php by truncating
#          them, which would have written through the hardlink into the LIVE release.
#       5. proves it: the new vendor's autoload has a link count of 1, and the LIVE release's installed.json has the same
#          digest after as before;
#       6. rebuilds the package manifest (`package:discover`) and then the config and route caches, as every stage does.
#     A failure anywhere stops BEFORE the swap; the live release and every rollback target keep their own vendor/.
set -euo pipefail

HOST="u545270279@82.25.102.188"; PORT=65002; KEY="$HOME/.ssh/vyrobel_deploy"
ROOT='domains/vyrobel.com'; PHP=/opt/alt/php84/usr/bin/php
REPO="$(cd "$(dirname "$0")/.." && pwd)"

ssh_() { ssh -i "$KEY" -p "$PORT" -o ConnectTimeout=60 "$HOST" "$@"; }
die() { echo "  ✗ $*" >&2; exit 1; }
say() { echo "  $*"; }

live_release() { ssh_ "readlink ~/$ROOT/public_html/current" | sed 's#.*/##'; }
live_commit()  { ssh_ "cat ~/$ROOT/public_html/current/.release" | tr -d '[:space:]'; }

cmd="${1:-status}"; NEW="${2:-}"

case "$cmd" in
status)
    say "live release : $(live_release)"
    say "live commit  : $(live_commit)   (read from .release)"
    ssh_ "cd ~/$ROOT/releases && for d in rel_*; do printf '    %-8s %s\n' \"\$d\" \"\$(cut -c1-12 \$d/.release 2>/dev/null || echo '?')\"; done"
    ;;

stage)
    [[ "$NEW" =~ ^rel_[A-Z]+$ ]] || die "usage: stage rel_<LETTERS> [--with-vendor]"
    WITH_VENDOR=0
    case "${3:-}" in
        '') ;;
        --with-vendor) WITH_VENDOR=1 ;;
        *) die "unknown option: $3 (the only one is --with-vendor)" ;;
    esac
    cd "$REPO"
    [ -z "$(git status --porcelain)" ] || die "the working tree is not clean — stage only committed code"
    CUR="$(live_release)"; FROM="$(live_commit)"; TO="$(git rev-parse HEAD)"
    [[ "$FROM" =~ ^[0-9a-f]{40}$ ]] || die "live .release is not a 40-char commit: [$FROM]"
    git cat-file -e "$FROM^{commit}" 2>/dev/null || die "the live commit $FROM is not in this repository"
    [ "$FROM" != "$TO" ] || die "HEAD is already live"
    say "live $CUR @ ${FROM:0:8}  →  staging $NEW @ ${TO:0:8}"

    # ⚠️ I18N-01 Phase E — the translation gate BLOCKS (a finished language with a broken placeholder, tag or quote can break a
    #    page or a script); what still needs translation only WARNS (a new English text shows in English until DELTA runs).
    LANG_CHECK="$(php artisan lang:check 2>&1)" || { printf '%s\n' "$LANG_CHECK" | tail -n 15; die "lang:check fails — a release never ships a broken translation"; }
    say "translation: $(printf '%s\n' "$LANG_CHECK" | tail -n 1)"
    say "needs translation since live: $(php artisan lang:todo --since="$FROM" 2>/dev/null | tail -n 1)"

    # ⚠️ bash 3.2 (macOS) has no `mapfile`, and treats an EMPTY array as unbound under `set -u` — so the file
    #    lists are plain files read with `while read`, never arrays.
    WORK="$(mktemp -d -t vyrel)"
    git diff --name-only --diff-filter=ACMRT "$FROM" "$TO" > "$WORK/changed"
    git diff --name-only --diff-filter=D "$FROM" "$TO" > "$WORK/deleted"

    # ⚠️⚠️ ADMIN 06 (#6) — DRIFT. The diff above ships what CHANGED between two commits, so a file the host holds in a
    #    DIFFERENT form — a hand copy, a copy older than the release layout — is never corrected by any later stage.
    #    🔬 Measured 2026-09-27 on rel_AE: 4 of 1,395 deployable files differed from git at its own commit — the three
    #    ops check files (hand copies from 2026-09-21) and ops/ps-check.sh (never shipped). So every deployable file
    #    the LIVE release holds is checked against git at the LIVE commit, and each that differs or is missing ships
    #    too, at the new commit. The server's copies come from git through the deploy — never a hand copy.
    mkdir -p "$WORK/tree"
    git archive "$FROM" -- app bootstrap config database public resources routes ops artisan | tar -x -C "$WORK/tree"
    (cd "$WORK/tree" && find . -type f ! -path './bootstrap/cache/*' ! -path './public/build/*' ! -path './resources/css/*' ! -path './resources/js/*' -print0 \
        | xargs -0 shasum -a 256) > "$WORK/manifest"
    [ "$(wc -l < "$WORK/manifest" | tr -d ' ')" -gt 1000 ] || die "the drift manifest lists too few files to be the application"
    scp -i "$KEY" -P "$PORT" -q "$WORK/manifest" "$HOST:~/vy-release-drift.manifest"
    ssh_ "cd ~/$ROOT/releases/$CUR && { sha256sum -c ~/vy-release-drift.manifest 2>/dev/null || true; } | grep -v ': OK\$' | sed -E 's#^\./##; s#: FAILED( open or read)?\$##' || true; rm -f ~/vy-release-drift.manifest" > "$WORK/drift"
    if [ -s "$WORK/drift" ]; then
        say "drifted from git on the host (shipped from git now): $(wc -l < "$WORK/drift" | tr -d ' ')"
        sed 's/^/      · /' "$WORK/drift"
        # at the NEW commit — only where the path still exists there: a file deleted or RENAMED since is not shipped under
        # its old name (the diff above already ships the new one; `git archive` would refuse the old path outright)
        while IFS= read -r f; do
            [ -n "$f" ] || continue
            if git cat-file -e "$TO:$f" 2>/dev/null; then echo "$f" >> "$WORK/changed"; else say "      (not at the new commit, not shipped: $f)"; fi
        done < "$WORK/drift"
        sort -u "$WORK/changed" -o "$WORK/changed"
    else
        say "drifted from git on the host: none"
    fi
    LOCK_CHANGED=0; VFILES=0
    while IFS= read -r f; do
        case "$f" in
            composer.json|composer.lock)
                # ⚠️ VY-11 — still REFUSED unless the deliberate step was asked for by name (see the header).
                [ "$WITH_VENDOR" = 1 ] || die "$f changed — vendor/ needs a deliberate rebuild: stage with --with-vendor (it builds vendor/ off-host from composer.lock and ships it with its own inodes); refusing to copy around it"
                LOCK_CHANGED=1;;
            package.json|package-lock.json|vite.config.*|resources/css/*|resources/js/*)
                die "$f changed — public/build needs a deliberate rebuild step; refusing to copy around it";;
        esac
    done < <(cat "$WORK/changed" "$WORK/deleted")
    [ "$WITH_VENDOR" = 0 ] || [ "$LOCK_CHANGED" = 1 ] || die "--with-vendor was given but neither composer.json nor composer.lock changed since the live commit — nothing to rebuild; stage without it"
    [ "$WITH_VENDOR" = 0 ] || git cat-file -e "$TO:composer.lock" 2>/dev/null || die "the commit being staged has no composer.lock"
    # Only what the app needs on the host: docs/ and tests/ are not deployed.
    grep -vE '^(docs/|tests/|\.github/)|\.md$' "$WORK/changed" > "$WORK/ship" || true
    say "files to ship: $(wc -l < "$WORK/ship" | tr -d ' ') · deleted: $(wc -l < "$WORK/deleted" | tr -d ' ')"
    [ -s "$WORK/ship" ] || die "nothing to ship"

    TAR="$WORK/payload.tar.gz"
    # ⚠️ 2026-11-05 — NOT `xargs -0 git archive -o "$TAR" … --`: with ~10,400 files (a release that adds 17 languages) xargs splits
    #    the list over several `git archive` runs and each one OVERWRITES the -o file, so the payload held only the last
    #    slice (caught by the count check below). The new commit's tree is extracted once and tarred from the list.
    mkdir -p "$WORK/newtree"
    git archive "$TO" | tar -x -C "$WORK/newtree"
    tar -czf "$TAR" -C "$WORK/newtree" -T "$WORK/ship"
    [ "$(tar -tzf "$TAR" | grep -cv '/$')" = "$(wc -l < "$WORK/ship" | tr -d ' ')" ] || die "the payload does not contain every file to ship"
    # ⚠️⚠️ VY-11 — THE DELIBERATE vendor/ REBUILD, OFF-HOST (only with --with-vendor; see the header for why each line is there).
    if [ "$WITH_VENDOR" = 1 ]; then
        say "vendor: building off-host from composer.lock at ${TO:0:8} (no dev packages) …"
        (cd "$WORK/newtree" && composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --no-progress >"$WORK/composer.log" 2>&1) \
            || { tail -n 20 "$WORK/composer.log" >&2; die "composer install failed in the clean export — nothing was uploaded"; }
        [ -f "$WORK/newtree/vendor/autoload.php" ] || die "composer install left no vendor/autoload.php"
        (cd "$WORK/newtree" && composer audit --locked --no-dev --no-interaction >"$WORK/audit.log" 2>&1) \
            || { tail -n 25 "$WORK/audit.log" >&2; die "composer audit still reports advisories for the lock being shipped"; }
        say "vendor: $(php -r 'echo count(json_decode((string) file_get_contents($argv[1]), true)["packages"]);' "$WORK/newtree/vendor/composer/installed.json") packages · composer audit: $(tail -n 1 "$WORK/audit.log")"
        VTAR="$WORK/vendor.tar.gz"
        # ⚠️⚠️ NO macOS METADATA IN THE ARCHIVE (measured 2026-11-24, on this step's FIRST run): files composer downloads carry the
        #    `com.apple.provenance` attribute, and macOS tar packs each one as a second `._<name>` file. The first staged vendor/
        #    held 12,945 of them beside its 11,251 real files — `._Foo.php` in every package folder, where anything that globs
        #    `*.php` (the migrations, config or routes of a package) would read binary junk. The stage's other checks all passed
        #    over it; it was found by counting the files on the host. That release (rel_CT) was never made live.
        COPYFILE_DISABLE=1 tar --no-xattrs -czf "$VTAR" -C "$WORK/newtree" vendor
        # ⚠️ COUNTED WITH A READER THAT DOES NOT HIDE THEM: macOS `tar -t` folds each `._` member back into its file and lists
        #    none (measured: it printed 0 for an archive that held 12,946), so the listing is read with Python's tarfile.
        VCOUNT="$(python3 -c 'import sys, tarfile
m = [x.name for x in tarfile.open(sys.argv[1]).getmembers() if not x.isdir()]
print(len(m), sum(1 for n in m if n.rsplit("/", 1)[-1].startswith("._")))' "$VTAR")" || die "could not read the vendor archive back"
        VFILES="${VCOUNT%% *}"; VJUNK="${VCOUNT##* }"
        [ "$VJUNK" = 0 ] || die "the vendor archive contains $VJUNK macOS ._ metadata files — refusing to upload it"
        [ "$VFILES" -gt 1000 ] || die "the vendor archive lists too few files ($VFILES) to be vendor/"
        VLOCAL="$(shasum -a 256 "$VTAR" | cut -d' ' -f1)"
        scp -i "$KEY" -P "$PORT" -q "$VTAR" "$HOST:~/vy-release-vendor.tar.gz"
        VREMOTE="$(ssh_ 'sha256sum ~/vy-release-vendor.tar.gz' | cut -d' ' -f1)"
        [ "$VLOCAL" = "$VREMOTE" ] || die "vendor archive digest mismatch after upload"
        say "vendor: archive uploaded, sha256 matches (${VLOCAL:0:16}…)"
    fi

    LOCAL_SHA="$(shasum -a 256 "$TAR" | cut -d' ' -f1)"
    scp -i "$KEY" -P "$PORT" -q "$TAR" "$HOST:~/vy-release-payload.tar.gz"
    scp -i "$KEY" -P "$PORT" -q "$WORK/ship" "$HOST:~/vy-release-payload.list"
    scp -i "$KEY" -P "$PORT" -q "$WORK/deleted" "$HOST:~/vy-release-payload.del"
    REMOTE_SHA="$(ssh_ 'sha256sum ~/vy-release-payload.tar.gz' | cut -d' ' -f1)"
    [ "$LOCAL_SHA" = "$REMOTE_SHA" ] || die "payload digest mismatch after upload"
    say "payload uploaded, sha256 matches (${LOCAL_SHA:0:16}…)"

    ssh_ "set -euo pipefail
      cd ~/$ROOT/releases
      [ ! -e $NEW ] || { echo '  ✗ $NEW already exists'; exit 1; }
      cp -al $CUR $NEW
      rm -rf ~/vy-release-stage && mkdir -p ~/vy-release-stage
      tar -xzf ~/vy-release-payload.tar.gz -C ~/vy-release-stage
      # rm-then-copy: a NEW inode, so the hardlink to the live release is broken, never written through
      while IFS= read -r f; do [ -n \"\$f\" ] || continue
        mkdir -p \"$NEW/\$(dirname \"\$f\")\"; rm -f \"$NEW/\$f\"; cp -p ~/vy-release-stage/\"\$f\" \"$NEW/\$f\"
      done < ~/vy-release-payload.list
      while IFS= read -r f; do [ -n \"\$f\" ] || continue; rm -f \"$NEW/\$f\"; done < ~/vy-release-payload.del
      rm -f $NEW/.release; printf '%s\n' '$TO' > $NEW/.release
      if [ $WITH_VENDOR = 1 ]; then
        # ⚠️⚠️ VY-11 — the NEW release gets its OWN vendor/. rm -rf removes this release's LINKS only (the live release keeps the
        #    files); the archive then unpacks as new inodes. Proven below, before anything is cached.
        LIVE_BEFORE=\$(sha256sum $CUR/vendor/composer/installed.json | cut -d' ' -f1)
        rm -rf $NEW/vendor
        tar -xzf ~/vy-release-vendor.tar.gz -C $NEW
        [ -f $NEW/vendor/autoload.php ] || { echo '  ✗ the new vendor/ has no autoload.php'; exit 1; }
        [ \"\$(find $NEW/vendor $NEW/._vendor -name '._*' 2>/dev/null | wc -l | tr -d ' ')\" = 0 ] || { echo '  ✗ the new vendor/ holds macOS ._ metadata files'; exit 1; }
        [ \"\$(find $NEW/vendor ! -type d | wc -l | tr -d ' ')\" = $VFILES ] || { echo \"  ✗ the new vendor/ holds \$(find $NEW/vendor ! -type d | wc -l) files; the archive held $VFILES\"; exit 1; }
        [ \"\$(stat -c %h $NEW/vendor/autoload.php)\" = 1 ] && [ \"\$(stat -c %h $NEW/vendor/composer/installed.json)\" = 1 ] || { echo '  ✗ the new vendor/ shares an inode with another release'; exit 1; }
        [ -f $CUR/vendor/autoload.php ] && [ \"\$(sha256sum $CUR/vendor/composer/installed.json | cut -d' ' -f1)\" = \"\$LIVE_BEFORE\" ] || { echo '  ✗ the LIVE release vendor/ changed'; exit 1; }
        # the package manifest and the caches name the OLD packages: unlink them (they are hardlinks too), rebuild from the new vendor
        rm -f $NEW/bootstrap/cache/packages.php $NEW/bootstrap/cache/services.php $NEW/bootstrap/cache/config.php $NEW/bootstrap/cache/routes-v7.php
        (cd $NEW && $PHP artisan package:discover >/dev/null)
        echo \"  ✓ vendor/ rebuilt from composer.lock — \$(ls $NEW/vendor | wc -l | tr -d ' ') top-level folders, $VFILES files (the archive's count; no ._ metadata), its own inodes; the live vendor/ is unchanged (installed.json digest before = after)\"
        rm -f ~/vy-release-vendor.tar.gz
      fi
      cd $NEW
      $PHP artisan config:cache >/dev/null
      $PHP artisan route:cache >/dev/null
      chmod 600 bootstrap/cache/config.php
      # ⚠️ TONIGHT 01 — the ONE shared .env: every release LINKS it (never a copy), and it stays 600. Re-asserted on
      #    every stage, because a hand edit in the hosting panel's file editor can rewrite it with a wider mode.
      [ -L .env ] && [ \"\$(readlink .env)\" = '../../public_html/.env' ] || { echo '  ✗ .env is not the link to the shared file'; exit 1; }
      chmod 600 ../../public_html/.env
      echo \"  .env → \$(readlink .env) · shared file mode \$(stat -c %a ../../public_html/.env)\"
      echo \"  caches rebuilt → paths point at: \$(grep -oE 'releases/rel_[A-Z]+' bootstrap/cache/config.php | sort -u | tr '\n' ' ')\"
      echo \"  .release = \$(cat .release)\"
      # ⚠️ THE LIVE RELEASE MUST BE UNTOUCHED: every shipped file that exists in it has its OWN inode now.
      shared=0
      while IFS= read -r f; do [ -n \"\$f\" ] || continue
        [ -e ../$CUR/\"\$f\" ] || continue
        [ \"\$(stat -c %i ../$CUR/\"\$f\")\" != \"\$(stat -c %i \"\$f\")\" ] || { echo \"  ✗ SHARED INODE: \$f\"; shared=1; }
      done < ~/vy-release-payload.list
      [ \$shared = 0 ] && echo '  ✓ every shipped file has its own inode — the live release is untouched' || exit 1
      rm -rf ~/vy-release-stage ~/vy-release-payload.tar.gz ~/vy-release-payload.list ~/vy-release-payload.del"
    rm -rf "$WORK"

    PENDING="$(ssh_ "cd ~/$ROOT/releases/$NEW && $PHP artisan migrate:status 2>/dev/null | grep -E 'Pending[[:space:]]*\$' | awk '{print \$1}'" || true)"
    say "pending migrations: ${PENDING:-none}"
    ;;

migrate)
    [[ "$NEW" =~ ^rel_[A-Z]+$ ]] || die "usage: migrate rel_<LETTERS>"
    cd "$REPO"
    PENDING="$(ssh_ "cd ~/$ROOT/releases/$NEW && $PHP artisan migrate:status 2>/dev/null | grep -E 'Pending[[:space:]]*\$' | awk '{print \$1}'" || true)"
    [ -n "$PENDING" ] || { say "no pending migrations — nothing to do"; exit 0; }
    # ⚠️ ADDITIVE ONLY — read each pending migration's up() and refuse a drop / rename / change / NOT NULL add.
    for m in $PENDING; do
        f="database/migrations/$m.php"; [ -f "$f" ] || die "pending migration $m is not in this repository"
        UP="$(awk '/function up\(/{f=1} /function down\(/{f=0} f' "$f")"
        if echo "$UP" | grep -qE 'drop(Column|IfExists|s?Table)?\(|renameColumn\(|->change\(\)|Schema::rename\(|dropForeign|dropIndex'; then
            die "$m's up() drops, renames or changes something — not additive; refusing"
        fi
        say "✓ $m — additive"
    done
    ssh_ "cd ~/$ROOT/releases/$NEW && $PHP artisan migrate --force 2>&1 | tail -20"
    ;;

recache)
    # ⚠️ TONIGHT 01 — an .env-only change (a password, a key, a URL) needs the config cache REBUILT (L14), and `stage`
    #    rightly refuses a commit that is already live. This builds a new release of the SAME commit — a hardlink copy
    #    of the live one — with config and routes rebuilt from the .env as it is NOW. `swap` makes it live; a rollback
    #    to the previous release restores the previous config exactly, because that release is never touched.
    [[ "$NEW" =~ ^rel_[A-Z]+$ ]] || die "usage: recache rel_<LETTERS>"
    CUR="$(live_release)"; FROM="$(live_commit)"
    [[ "$FROM" =~ ^[0-9a-f]{40}$ ]] || die "live .release is not a 40-char commit: [$FROM]"
    say "live $CUR @ ${FROM:0:8}  →  $NEW: the same code, config + routes rebuilt from the current .env"
    ssh_ "set -euo pipefail
      cd ~/$ROOT/releases
      [ ! -e $NEW ] || { echo '  ✗ $NEW already exists'; exit 1; }
      cp -al $CUR $NEW
      cd $NEW
      # the caches are HARDLINKS shared with the live release: unlink them first, so the rebuild writes NEW inodes
      rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php
      $PHP artisan config:cache >/dev/null
      $PHP artisan route:cache >/dev/null
      chmod 600 bootstrap/cache/config.php
      [ -L .env ] && [ \"\$(readlink .env)\" = '../../public_html/.env' ] || { echo '  ✗ .env is not the link to the shared file'; exit 1; }
      chmod 600 ../../public_html/.env
      echo \"  .env → \$(readlink .env) · shared file mode \$(stat -c %a ../../public_html/.env)\"
      echo \"  caches rebuilt → paths point at: \$(grep -oE 'releases/rel_[A-Z]+' bootstrap/cache/config.php | sort -u | tr '\n' ' ')\"
      echo \"  .release = \$(cat .release)  (unchanged: the same code)\"
      [ \"\$(stat -c %i ../$CUR/bootstrap/cache/config.php)\" != \"\$(stat -c %i bootstrap/cache/config.php)\" ] && echo '  ✓ its own config cache — the live release is untouched' || { echo '  ✗ SHARED config-cache inode'; exit 1; }"
    ;;

swap|rollback)
    [[ "$NEW" =~ ^rel_[A-Z]+$ ]] || die "usage: $cmd rel_<LETTERS>"
    ssh_ "set -e; cd ~/$ROOT; [ -d releases/$NEW ] || { echo '  ✗ no such release'; exit 1; }
      S=\$(date +%s%3N); ln -s ../releases/$NEW public_html/current.tmp && mv -T public_html/current.tmp public_html/current; E=\$(date +%s%3N)
      echo \"  current → \$(readlink public_html/current)  (\$((E-S)) ms)\"
      echo \"  .release = \$(cat public_html/current/.release)\""
    ;;

*) die "unknown command: $cmd" ;;
esac
