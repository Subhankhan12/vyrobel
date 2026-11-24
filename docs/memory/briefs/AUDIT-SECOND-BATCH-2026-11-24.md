# AUDIT, SECOND BATCH — the founder's words, 2026-11-24 (11:53 Baku)

Saved word for word. The findings he pasted are the text of the audit register's own entries (VY-01, VY-09 … VY-18) as they stood that morning.
⚠️ This repository is public. Every finding below that says HOW a weakness is reached was FIXED AND LIVE before this file was pushed
(see `HISTORY.md`, "THE AUDIT, SECOND BATCH"); VY-10 and the founder's half of VY-12 describe states already written in `OPEN.md` (F1, F2, F4, F13).

## What he wrote

> Fix all of the followings "
> VY-01
> P0
> Booking · payments
> Reproduced
> An anonymous visitor can create a booking that is recorded as fully paid
> A signed-out call to the public JSON booking endpoint, with any text as the card token, returned 201. Under the manual gateway, which is the default, the payment row read succeeded for 240.00, the reservation read paid and the folio balance was 0. The manual gateway approves every charge, and an existing test asserts this as correct. A booking faked this way also passes the collection gate, so it would become payable to an owner. Production's gateway setting was not read.
> FIXNever take payment on the public endpoint while the gateway is manual. Keep manual charges to signed-in staff. Replace the test.
> app/Http/Controllers/BookingEngineController.php:761 · app/Services/BookingEngineService.php:266 · app/Payments/Gateways/ManualPaymentGateway.php · tests/Feature/PublicBookingEngineTest.php:81
> VY-09
> P1
> Marketing
> Seen live
> Sixteen public statements describe things the code does not do
> Each one is listed under Public claims.
> FIXCorrect the copy before the first sale.
> VY-10
> P1
> Onboarding
> Read in code · records
> Sign-up with email and password stops at "verify your email"
> Email sending is off on production, and the verify page itself tells the visitor the link will not arrive.
> FIXRotate the exposed mailbox password and switch sending on. These are your steps F1 and F13.
> VY-11
> P1
> Dependencies
> Reproduced
> 28 open security advisories across 11 packages
> composer audit lists them. One on laravel/framework 13.18.0 is rated critical, a token-guard bypass; the app uses no token guard, so I judge it not reachable here. Livewire 4.3.3, Symfony 8.1.1 and commonmark 2.9.0 are also behind.
> FIXUpdate within the current major versions, run both database engines, release.
> VY-12
> P1
> Operations
> Read in code · records
> Backups are not scheduled, the alarm is unarmed, and the application key has one copy
> The backup script makes an encrypted archive but is not on the schedule and writes to the same server by default. The heartbeat address is not set, so a dead scheduler alerts nobody. Losing the key makes every encrypted guest record unreadable.
> FIXSchedule backups and ship them off the server, drill a restore, arm the alarm, store the key in a password manager.
> ops/backup.sh
> VY-13
> P2
> Front desk
> Reproduced
> Check-in works with no room, and into a room that is out of service
> The only condition is that the booking is confirmed. Both cases succeeded.
> FIXRequire a room. When the room is not ready, show a blocking dialog that names the problem.
> app/Actions/Reservations/CheckInReservation.php
> VY-14
> P2
> Staff access
> Reproduced
> Roles without reservation permission can read reservations and amounts
> A Housekeeping user opened the reservation list and one reservation's detail, and saw the amount and the guest's name. The two read routes carry no permission check.
> FIXGate both routes on reservations.view.
> routes/web.php:1308
> VY-15
> P2
> Booking
> Reproduced
> The public checkout accepts past dates, too many guests and a switched-off rate plan
> A stay on 2–4 July was booked with the clock at 1 August. Nine adults and seven children were booked into a room for 2 + 2. A deactivated plan was still bookable.
> FIXValidate dates, guest counts and plan state on both public write paths, and cap the guest fields at the room's limits.
> VY-16
> P2
> Reports
> Reproduced
> A rate change between quote and booking splits the booking from its folio
> The folio charged the quoted 240.00. The booking line stored 400.00, and occupancy and revenue reports read the line.
> FIXStore the quoted price on the booking line.
> app/Services/BookingEngineService.php:99
> VY-17
> P2
> Guest accounts
> Read in code
> Each direct booking creates a new guest record, and a portal account sees only one
> A second booking with the same email does not appear in the guest's account. Sign-in always resolves to the first claimed record with that email, across all organisations.
> FIXWhen a booking is made, match an existing guest by email within the organisation.
> app/Services/GuestAccountService.php:38 · app/Auth/GuestUserProvider.php
> VY-18
> P2
> Privacy
> Read in code
> ID images are collected but can never be viewed, and some are kept too long
> Images are encrypted on the private disk, but no screen or route opens one, so every document stays "pending". The 30-day delete counts from the original checkout date, so a cancelled future booking keeps its image for months and a deleted booking is never matched.
> FIXDecide first: build an audited viewer, or stop collecting. Then purge on cancellation.
> app/Services/Guest/GuestIdDocumentService.php · app/Console/Commands/PurgeExpiredGuestDocuments.php
> ", once all of them are fixed and tested then deploy all work live on server and test everything via mcp playwright to make sure everything is working fine and then update the github and artifact as well.

(The findings are the audit register's own entries as he pasted them, one field to a line.)

## The three questions he was asked, and his answers (the same morning)

1. **VY-18 — build a viewer so staff can open an ID image (every opening recorded), or stop collecting ID images at online check-in?**
   Options offered: "Build the viewer (Recommended)" · "Stop collecting" · "Only fix the deletion".
   **He chose: "Build the viewer (Recommended)".**
2. **VY-12 — where should the copy off the server go? Any destination needs a credential that only he can type on the server.**
   Options offered: "Schedule now, copy later (Recommended)" · "S3-compatible bucket" · "Leave backups alone".
   **He chose: "Schedule now, copy later (Recommended)".**
3. **The MariaDB test run needs the local MySQL stopped for about 25 minutes. May I swap when the fixes are ready?**
   Options offered: "Yes, swap (Recommended)" · "No, MySQL only".
   **He chose: "Yes, swap (Recommended)".**

## What his words do NOT cover (so nothing is read into them)

- **VY-10** is his own step by the finding's own text (F1, then F13): no session types a mailbox password. Nothing was built for it.
- **VY-12's other half** is his: the passphrase file on the server, a second copy of that passphrase, the off-server destination and its
  credential, the heartbeat address (F4), and the copy of `APP_KEY` in his password manager (F2).
- **VY-14** asks for `reservations.view`. The Accountant holds the folio and payment permissions and not that one, and the folio is
  worked on the reservation's page — so the gate was built as `reservations.view` OR `folios.view`. That widening is the session's
  judgement, told to him in the report; it is his to change.
- **VY-17's second sentence** (sign-in resolves to the first claimed record across all organisations) is not covered by the finding's FIX
  and was not changed.
