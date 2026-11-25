# AUDIT, THIRD BATCH — the founder's words, 2026-11-25 (01:32 Baku)

Saved word for word. The findings he pasted are the text of the audit register's own entries (VY-23 … VY-35, without VY-19 … VY-22) as they
stood that night. ⚠️ This repository is public. Every finding below that says HOW a weakness is reached was FIXED AND LIVE before this file
was pushed (see `HISTORY.md`, "THE AUDIT, THIRD BATCH").

## What he wrote

> "VY-23
> P2
> Booking
> Read in code
> Unpaid bookings hold rooms indefinitely
> A booking is confirmed before any payment. An abandoned card checkout stays confirmed, nothing cancels it, and quotes are never expired by a schedule. Anyone can fill a calendar with only a rate limit in the way.
> FIXRelease unpaid card bookings on a timer, schedule quote expiry, and ask for a card guarantee or deposit.
> app/Http/Controllers/Concerns/HandlesStripeLifecycleEvents.php:58
> VY-24
> P2
> Housekeeping
> Read in code
> A housekeeper cannot report a defect
> Opening a work order needs maintenance.manage, which the Housekeeping role lacks.
> FIXA "report a problem" action on the housekeeping board that opens a work order.
> VY-25
> P2
> Migration
> Read in code
> There is no import of any kind
> No file import exists for bookings, guests, units or statements.
> FIXBuild a CSV import with a preview and a reconciliation step, or stop promising one.
> VY-26
> P2
> Operations
> Read in code
> Statements, the night audit and no-shows are not automated
> Statements are made by hand from each owner's page. No night audit and no automatic no-show exist.
> FIXBuild them, or remove the claims.
> app/Http/Controllers/Operator/OwnerManageController.php:258
> VY-35
> P2
> Platform
> Read in code
> A lapsed trial closes the back office but leaves the storefront open
> The public storefront closes only for suspended and cancelled organisations, so bookings can arrive that nobody can see.
> FIXPause the storefront when the workspace lapses.
> app/Http/Middleware/ResolvePublicProperty.php
> VY-27
> P3
> Housekeeping
> Read in code
> A room can go from pending to inspected in one step, by the same person
> FIXAn optional "inspection required" setting.
> app/Services/HousekeepingService.php:214
> VY-28
> P3
> Security
> Seen live
> The content security policy allows inline and eval scripts
> FIXMove to nonces when practical.
> VY-29
> P3
> SEO
> Seen live
> The marketing pages are hard to find and cannot be cached
> No sitemap for the marketing pages and none named in robots.txt. No social preview image. All 27 languages share one address, so only English can be indexed. Every anonymous visit starts a session and is marked private, so the CDN cannot cache the page.
> FIXAdd a sitemap and a preview image; skip sessions on marketing pages.
> VY-30
> P3
> Accessibility
> Reproduced
> 22 footer links are under the 24 px touch-target size
> FIXMore line height or padding.
> VY-31
> P3
> Website
> Seen live
> The /case-studies link on the example-scenarios page returns 404
> FIXRemove the link.
> VY-32
> P3
> Guest · statements
> Read in code
> Five small defects
> HEIC photos are listed as accepted but refused. A multi-room stay shows only the first room's door code. The code's release day uses the server's clock, not the property's. Statement numbering takes no lock. Currency conversion on a statement uses floating point.
> FIXSmall, separate fixes.
> VY-33
> P3
> Operations
> Read in code
> Queue dashboard routes are registered on a host with no Redis
> FIXDo not register them in production.
> VY-34
> P3
> Privacy
> Read in code
> Housekeeping and maintenance photos open without sign-in
> They are served from the public media path by an address that is hard to guess.
> FIXMove them to the private disk.
> app/Http/Controllers/PublicMediaController.php
> " now fix these, you fix all these, then test everything via MCP playwright, ship them live. But when you're testing everything via MCP playwright, test them on the live server and make sure everything is working fine. Then update the GitHub and also update the entire memory.

## His answers to the two questions asked (same night)

- **VY-25:** "Correct the pages (Recommended)" — remove or reword every import promise to match the code; a CSV import stays a later build.
- **The engine swap:** "Yes, swap" — the full suite on MariaDB 11.8.9 as well as MySQL.
