# AUDIT P1 FIXES — the founder's words, 2026-11-24 (04:31 Baku)

Saved word for word. He pasted seven findings from the audit register of the same day (a read-only audit; its register is an
artifact on his claude.ai account, "Vyrobel Audit Register") and one instruction.

> Fix the followings "VY-02
> P1
> Owner money
> Reproduced
> A full refund of a stay that carried a tax leaves the owner in debt
> Room 200.00 plus city tax 20.00, paid 220.00, owner credited 170.00 at 15% commission. After the full refund the owner's net was −17.00. The owner's share is taken from the whole refund, tax included.
> FIXBase the owner's share on the refunded room amount and cap it at what the owner was credited.
> app/Services/OwnerAttributionService.php:174
> VY-03
> P1
> Availability
> Reproduced
> Taking a unit out of service can oversell a room type with no warning
> Two units, two bookings with no room assigned yet. One unit was put out of service and the other blocked. No error, real capacity −2, the stored figure shown as 0 because it is clamped, and no conflict record.
> FIXIn both paths lock the nights and compare rooms sold to what remains, then refuse or raise a visible conflict. Before a work order is saved, show how many bookings would be left without a room. Stop clamping silently.
> app/Services/AssignmentService.php:701 · app/Services/MaintenanceService.php:435 · app/Models/Availability.php
> VY-04
> P1
> Booking
> Reproduced
> Posting the checkout form twice makes two bookings
> The same form sent twice produced 2 confirmed reservations and used both units.
> FIXA one-time token per checkout page that refuses a repeat, and a button that disables on submit.
> app/Http/Controllers/BookingEngineController.php:457
> VY-05
> P1
> Staff access
> Reproduced
> Staff limited to one property can act on another property's reservations
> A Front Desk user assigned only to property A opened property B's reservation, downloaded its folio PDF and checked its guest in. Lists, units, work orders and housekeeping do respect the limit. This is inside one organisation; no leak between organisations was found.
> FIXAdd a reservation lookup to the property scope and use it on every reservation, folio and front-desk route.
> app/Support/Property/PropertyScope.php · app/Livewire/ReservationDetail.php:101 · app/Http/Controllers/FrontDeskActionController.php:45 · app/Http/Controllers/Operator/FolioPdfController.php:23 · app/Http/Controllers/Operator/ReservationLifecycleController.php:125 · app/Http/Controllers/ReservationFolioActionController.php:38
> VY-06
> P1
> Guest access
> Reproduced
> The door code is shown to guests who have not paid
> A stay with 200.00 owing and no online check-in showed its code two days before arrival. The only rule is the date, fixed at two days, and the pre-arrival email carries the same code.
> FIXRelease on conditions: balance settled, optionally check-in complete, with a lead time each property sets. Until then show "pay your balance to see your code".
> app/Support/Guest/ArrivalPack.php · app/Mail/PreArrivalMail.php
> VY-07
> P1
> Availability
> Read in code
> Nothing extends the bookable calendar
> Nights are published for 365 days when a unit or room type is created or toggled. No scheduled task adds days, so the window shrinks daily and closes a year after the last inventory change. The local demo data now ends 95 and 35 days ahead.
> FIXA daily job that publishes every room type to today + N, with an alert when the window runs short.
> app/Http/Controllers/Operator/InventoryActionController.php:85 · routes/console.php
> VY-08
> P1
> Public site
> Seen live
> A fictional villa from the internal test tenant is live, bookable and indexable
> "Villa Azure Lumière, Nice" answers 200 with index, follow. It and the test tenant's landing page are the two entries in the storefront sitemap.
> FIXUnpublish or noindex internal tenants and keep them out of the sitemap." Once you finish it, then update the memory, deploy everything on live server, test everything with MCP playwright, and update the artifact and remove the things those we have done. Update the same artifact.

## His answers during the pass (each to a question with two options)

- **The MariaDB engine swap (F9), ~06:50 Baku:** "Yes, swap (Recommended)".
- **The local MySQL would not start again after the swap** (Homebrew had removed the `abseil` library version MySQL 8.4.10 was built
  with; asked whether to run `brew upgrade mysql@8.4`), ~07:31 Baku: "Yes, upgrade it (Recommended)".
- **Run the new calendar job once on production now, or leave it to its 01:30 UTC schedule** (it writes calendar nights for every
  organisation, the two protected ones included), ~07:52 Baku: "Run it once now (Recommended)".

Twice he asked whether something was running ("Something is running or not?", "Something is running or everything is live? Or
you're working on something?") and was told what was running and what was left.
