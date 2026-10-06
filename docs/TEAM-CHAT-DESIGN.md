# Team chat and a live Messages page — design (2026-10-06)

**His request (2026-10-06, verbatim in `docs/memory/HISTORY.md`):** messages between the owner and the employees on
`/app/inbox`; a toggle at the top to choose clients or employees; attachments, pictures and voice notes; a notification icon
and a notification sound; the chat "Ajax based runtime"; neat and clean; every operation working; tested live.

**How it is read (assumptions he can correct):** "owner" = the account owner in the back office (the `Property Owner`
role — him) and "employees" = the other back-office staff. Unit owners (External Owners, the owner portal) keep their own
conversations, which already appear among the Guests side's threads. "Clients" = guests.

## 1 · What the audit found (read-only, 2026-10-06)

- There is **no staff-to-staff messaging** anywhere, **no live updating** (no polling, no websockets; every reply is a
  POST and a page reload), **no sound**, and the bell changes only on reload.
- The Inbox (`/app/inbox`) is gated by `guests.view`: **Housekeeping, Maintenance, Accountant and Revenue Manager cannot
  open it** — four of the eleven back-office roles, all of them employees.
- Guest and owner threads share `message_threads` with **one unread counter for all staff**, and the page lists every thread
  of the organisation. A staff conversation stored there would show in every `guests.view` holder's list, and its files
  would open through `app.inbox.attachment`, which checks only the organisation.
- The guest portal shows no attachments; the operator composer cannot attach; there is no audio anywhere.
- **The microphone is switched off on every page by our own header** (`Permissions-Policy: microphone=()`), and the policy
  has no `media-src`, so a recording cannot be previewed before sending (locally; see the next point).
- 🔬 **Production replaces our Content-Security-Policy with `upgrade-insecure-requests`** (the host — every other header of
  ours arrives, the Permissions-Policy included). Recorded as `OPEN.md` D-PROD-CSP; this design does not depend on it.
- When a guest or an owner writes, **no one on the staff is notified** (`inbox.message_received` has no handler).
- A support (break-glass) session **borrows a real staff member's view** — without a guard it could read their private
  messages.

## 2 · Decisions

1. **Team chat gets its own tables**, not `message_threads`. A guest thread belongs to the organisation (every
   `guests.view` holder reads it; one shared unread counter); a team conversation belongs to its members (each has their
   own read position). Sharing the table would need a "not team" filter at every guest-inbox read site — one missed filter
   leaks private staff messages. Files reuse the shared polymorphic `media` table and the private disk (DEC-m).
2. **Every back-office user can use team chat** — the `/app` group's back-office gate, no new permission (the RBAC pins
   stay). The Guests side keeps `guests.view` untouched (#30: authorization at the route).
3. **A conversation, its messages and its files are visible only to its members.** Anything else is a 404, never a 403.
4. **A support (break-glass) session cannot open team chat** — the page says so; the data endpoints answer 404.
5. **Live means polling** (production is shared hosting: no websockets, no Redis). The open conversation every 3 s while
   the tab is visible, every 20 s when hidden, and nothing after 30 minutes without a key or click (so an open tab does not
   keep a sign-in alive for ever); the rest of the back office every 20 s for the bell and badges.
6. **Rate limits are named per purpose** (`team-chat-poll`, `team-chat-write`, `pulse`) — an unnamed `throttle:N,1`
   shares one counter per user with the password change and the sign-in actions.
7. **Notifications are in-app only** (never emailed: one email per chat message would be noise), one bell entry per
   conversation while it has unread messages ("Anna Lee sent you 3 messages"), cleared when the conversation is read.
   The text names the sender only — never the message (a message stays in its own table).
8. **Sound is a short chime synthesised in the browser** (Web Audio — no audio file), on by default, with a switch in the
   chat; browsers allow sound only after the first click or key on the page, so before that the badge alone changes.
9. **The microphone is allowed only on the two Messages pages** (`microphone=(self)`); every other page keeps `()`.

## 3 · Data model (one additive migration)

- `team_conversations` — `id` (ULID), `organization_id`, `kind` (`direct` | `group`), `title` (groups, optional),
  `direct_key` (the two members' ids in order — unique per organisation, so two people have one direct conversation),
  `created_by` (users, null on delete), `last_message_at`, timestamps.
- `team_conversation_members` — `id`, `organization_id`, `conversation_id` (cascade), `user_id` (cascade),
  `last_read_message_id` (a read position is a message id — ULIDs sort by the millisecond; a timestamp is one second
  wide), `last_read_at`, timestamps; unique (conversation, user).
- `team_messages` — `id`, `organization_id`, `conversation_id` (cascade), `user_id` (the sender; null on delete), `body`
  (nullable: a voice note or files alone), timestamps.
- Files: `media` rows on a `team_message` (morph alias), collection `attachments` (pictures and files) or `voice`; private
  disk, `team-chat/{conversation}/{message}/{ulid}.{ext}`; a voice note's length (seconds) in `meta`.

## 4 · Server

- **`App\Services\TeamChatService`** — the one writer: `start()` (one teammate → your direct conversation, reopened if it
  exists; several → a group), `send()` (text, up to 5 files, one voice note; members only; audit + domain event inside the
  service, ids and counts — never the text; sending reads nothing), `markRead()` (up to the message the page was shown;
  forward only, in one statement).
- **`App\Support\TeamChat\TeamChatReader`** — the one reader: a person's conversations (unread counts, last message),
  messages after a cursor **and the 30 seconds before it again** (`LOOK_BACK_SECONDS` — §9), members' read positions. The
  page and the poll both use it, so they cannot disagree.
- **`App\Support\TeamChat\TeamChatFiles`** — store and serve: types checked from the file's own bytes; pictures inline,
  voice notes playable (byte ranges, for Safari), everything else downloaded; `private, no-store`, `nosniff`.
- Routes, inside `/app` (registered before `/inbox/{thread}`):
  - `GET /app/inbox/team` — the page (`app.inbox.team`)
  - `GET /app/inbox/team/poll` — JSON: the person's conversations, new messages in the open one, read positions
  - `POST /app/inbox/team/conversations` — start (direct or group; reopens an existing direct one)
  - `POST /app/inbox/team/{conversation}/messages` — send (multipart; JSON back)
  - `POST /app/inbox/team/{conversation}/read` — mark read (a POST: a support session's GETs never write)
  - `GET /app/inbox/team/files/{media}` — a file, members only
  - `GET /app/pulse` — JSON for every back-office page: unread notifications, team unread, guests unread (for
    `guests.view` holders)
  - `GET /app/inbox/live` — the open Inbox's stamp (`guests.view`); `POST /app/inbox/{thread}/read` — reading a guest or
    owner conversation, on purpose (`app.inbox.read`, `guests.view`; §6)
- JSON errors are written by the controllers (the app renders JSON errors only under `api/*`). Signed out — 401, 419, or a
  request sent on to another page (the sign-in, the paywall) — stops the page and says so; busy or failing — 429, 5xx, no
  connection — only slows it, doubling the wait (§9).

## 5 · The page

- **A toggle under the title on both sides: `Guests | Team`**, each with its unread count ("Guests & owners" where the
  organisation has unit owners). Someone without `guests.view` sees Team only, and the sidebar's Messages leads there.
- **Team:** conversations on the left (search, New chat), the open conversation on the right; on a phone one at a time,
  with a back arrow. New chat: pick one teammate (a direct conversation) or several (a group, optionally named).
- **Messages:** your own on the right in the accent colour, theirs on the left; names in groups; day dividers;
  pictures as thumbnails that open full size; files as cards (name, size, download); voice notes with a play button, a
  progress bar and their length; "Sent" / "Seen" (or "Seen by Anna, Ben") under your last message.
- **Composer:** Enter sends, Shift+Enter a new line; attach files, add pictures (also pasted or dropped in), record a voice
  note (up to 3 minutes; listen before sending; cancel). A message shows at once with "Sending…"; if it is not sent it comes
  back into the box — words, files and recording — with the reason. Each conversation keeps its own words, files and finished
  recording while you are elsewhere, and a message refused after you moved on waits in ITS conversation ("Not sent — your
  message is waiting here"), never yours. Files are checked against this server's own ceilings before sending (§9).

## 6 · The Guests side

- The toggle; the bell, the badges and the chime when a guest or an owner writes (the assigned person, else everyone who
  holds `guests.view`; one entry per conversation while unread; cleared when anyone opens it — the counter is shared).
- **Live:** every 5 seconds the open Inbox asks a small stamp; when it moved, the page re-reads ITSELF — the conversation it
  shows (`c`), flagged `live=1`, so the server reads nothing for the person — and swaps the counts, the list, the
  conversation's header and its messages in place. Never the reply box; never a voice note while it plays (that part waits
  for the next check); never another conversation (the part says which conversation it is). Reading is the person's act:
  the page posts `app.inbox.read` for a conversation they opened (never a preview), only while they are at the page (visible,
  focused, touched in the last two minutes). In a reply, Enter is a new line and Ctrl+Enter (⌘+Enter) sends — these words
  go to guests and owners.
- **Files, pictures and voice notes in a reply** where the other side can see them: an owner's conversation (their portal
  shows them) and a guest's Direct conversation (their guest account will show them — built here). Booking.com, Airbnb,
  WhatsApp and email threads stay text-only until those channels are connected.

## 7 · Not in this pass (said, not hidden)

Editing or deleting a sent message, reactions in team chat, typing indicators, "online now", desktop (system)
notifications, message search inside a conversation, and starting a conversation with a unit owner from the back office.

## 8 · Build order and gates

- **GATE 1 — Team chat:** tables, service, reader, files, routes, page, live polling, read receipts, notifications, pulse,
  sound, the toggle, the sidebar. Tests first; a mutation set; an independent review.
- **GATE 2 — the Guests side live:** inbound notifications, the pulse's guests count, the live refresh.
- **GATE 3 — files, pictures and voice notes to guests and owners** (the owner portal plays voice notes; the guest portal
  shows files).
- Then: both engines, every mutation set, the money gate, the docs, a local commit, `ops/release.sh`, the post-deploy
  checks, and the live test with his sign-in. Production holds his workspace and 14 probes; a message sent there is a write
  in a protected workspace, so the live test sends only with his go.

## 9 · The independent review (2026-10-06) — what it found, and what changed

A second reader went through the whole change before it shipped. Three findings were checked against the code and were
real before anything was fixed; every fix has a test that failed first, or a mutation that proves the test can fail.

- **A colleague's message could be skipped** — the page asked "after the newest message I hold", and that could be your
  own, sent a moment after theirs. Sending also moved your read position, so theirs counted as read. Now the page's cursor
  is only what the server delivered; the poll looks 30 seconds back from it; every message is kept once, by id; sending
  reads nothing. The same 30 seconds cover a message whose files took a moment to save (its id is made when it is written,
  its row seen when it is saved).
- **The open Inbox read conversations nobody looked at — and refreshed itself for ever.** Its refresh re-read the page, and
  an opened conversation is read on every read; a read is itself a change, so the stamp moved again. Now the refresh is
  `live=1` (nothing is read), and reading is a POST the page sends only while the person is there.
- **The refresh could show a different conversation** — the newest — when the page had none named. Now it always asks
  for the one it shows, and refuses a part that says it is another.
- **A Vyrobel support session saw the staff member's team-chat bell entries** (they name conversations and people) — now
  left out of the bell, the feed and the counts during a support session.
- Also fixed: a person whose membership ended was still rung; read bell entries piled up (now one per conversation);
  downloads kept a name's invisible direction marks and its claimed extension (now its own words and the extension its
  bytes earned — "invoice.vbs" holding text downloads as `invoice.txt`; a CSV stays a CSV); a guest's erasure left the
  staff's bell entries naming them (and the older "Conversation assigned to you · <name>" — the same erase now takes both);
  a reply's files are now kept on the disk BEFORE it is sent (a disk that refuses one means nothing is sent); a stored file
  whose record failed is removed; erased files go only once the erase is kept; one microphone at a time ("Starting…",
  and a recording that ends after it was cancelled is ignored); the pulse backs off instead of stopping on a passing error,
  and redraws the bell once a burst of changes settles (its limit is now 60 a minute — several tabs); the voice slider is a
  real slider (arrow keys, Home/End).
- 🔬 **File sizes are this server's, not a constant:** `ChatFiles::limits()` reads PHP's own ceilings where the page is
  served. This Mac's PHP takes 2 MB a file and 8 MB a request (PHP's defaults), so "up to 10 MB" was not true here; the
  page and the refusal now say the real figure, and a message too large altogether is refused before it is sent.

## 10 · Known limits (said, not hidden)

- The look-back is 30 seconds: a message whose save took longer than that after a newer one was shown would appear only
  when the conversation is opened again; and a read position already past it counts it as read.
- Staff may write about a guest in team chat; a guest's erasure cannot find those words — team messages are not linked to
  a guest. Recorded as `OPEN.md` D-TC-PII.
- If the files of an Inbox reply cannot be RECORDED after the words went (a database refusal in that instant), the words
  stay sent and the page says the files were not — a reply of files alone would then be an empty message.
- The upload ceilings read are PHP's; a web server's own request limit, if lower, answers 413 — the page says "too large".
- How it looks, plays and records is what a browser does: the tests prove the rules; the 🖥 walks prove the rest.
