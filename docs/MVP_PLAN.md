# MVP plan (Φάση 1)

Υλοποίηση με τη σειρά. Κάθε βήμα τελειώνει με πράσινα Pint, PHPStan, Pest και commit. Τα IDs παραπέμπουν στην ενότητα 3 του `docs/SPEC.md`.

## 0. Υποδομή
- [x] Laravel 12 project, Sail (MySQL 8, Mailpit), `.env.example`
- [x] Pint, Larastan level 8, Rector, Pest + arch plugin, `tests/Arch` με τους κανόνες layering του CLAUDE.md
- [x] Δομή `app/Modules/*` με έναν ServiceProvider ανά module, `app/Shared` (Money, Credits, Clock)
- [x] Filament panel `/admin`, Livewire layout `/app`, i18n `el` (default) + `en`
- [x] spatie packages (data, settings, permission + Shield, activitylog, translatable)
- [x] `BookingPolicySettings` με όλα τα κλειδιά της ενότητας 4 και τα defaults τους
- [x] GitHub Actions: Pint check → PHPStan → Pest (MySQL service)

## 1. Identity (ID-01 έως ID-06)
- [x] Εγγραφή με επιβεβαίωση email, αποδοχή όρων με έκδοση και ώρα
- [x] Login, logout, reset password, αλλαγή κωδικού, session lifetime από settings
- [x] Προφίλ πελάτη, διαγραφή λογαριασμού (ανωνυμοποίηση)
- [x] Ρόλοι customer / instructor / admin με Policies (reception αργότερα, βλ. ADR 0002). Δημιουργία πελάτη από admin με πρόσκληση
- [x] `checkin_token` ανά χρήστη

## 2. Catalog και Scheduling (SC-01 έως SC-04)
- [x] Κατηγορίες, υπηρεσίες (duration, capacity, credit_cost, policy_overrides) στο Filament
- [x] Schedules, closures, `class_sessions`
- [x] `GenerateSessions` job (idempotent, παραλείπει αργίες)
- [x] Αλλαγή και ακύρωση session (το refund των κρατήσεων έρχεται στο βήμα 4)

## 3. Membership (MB-01, MB-02, MB-04, MB-05, MB-07)
- [x] Packages + pivot υπηρεσιών
- [x] Memberships με snapshot όρων πακέτου
- [x] `CreditLedger` interface + υλοποίηση με `credit_transactions` (append-only, `balance_after`)
- [x] `MembershipSelector` (FEFO)
- [x] Πώληση πακέτου από το admin (υποδοχή αργότερα, ADR 0002), χειροκίνητη διόρθωση με αιτιολογία
- [x] `ExpireMemberships`, `WarnExpiringMemberships`
- [x] Test: ledger invariant

## 4. Booking (BK-01, BK-02, BK-04 έως BK-09)
- [x] Enum `BookingStatus` με transitions (ενότητα 7.1)
- [x] `BookingRule` + `BookingRuleChain` και οι 6 κανόνες της ενότητας 4
- [x] `BookSession` με `lockForUpdate` και unique index ενεργών κρατήσεων
- [x] `CancelBooking` (εντός και εκτός ορίου), `RescheduleBooking` (ατομική συναλλαγή)
- [x] Κράτηση και ακύρωση από προσωπικό με override και audit
- [x] Παρουσία / no-show, `MarkNoShows` (χωρίς check-in: αυτόματα attended, ADR 0005)
- [x] Ακύρωση session → refund σε όλους
- [x] Customer UI: υπηρεσία → μηνιαίο ημερολόγιο → ώρες με διαθέσιμες θέσεις και λίστα αναμονής → επιβεβαίωση
- [x] «Τα ραντεβού μου»: Upcoming, Μήνας, Εβδομάδα, Ιστορικό, λεπτομέρειες, .ics
- [x] `SendBookingReminders`
- [x] Test: δύο ταυτόχρονες κρατήσεις για την τελευταία θέση

## 5. Waitlist (WL-01 έως WL-03)
- [x] Είσοδος και έξοδος από λίστα αναμονής, θέση στην ουρά
- [x] `SeatFreed` → `PromoteFromWaitlist` (auto_book και offer), `ExpireWaitlistOffers`, cut-off

## 6. Communication (CM-01, CM-02, CM-04)
- [x] Notifications για όλα τα events της ενότητας 8.1 (email, SMS πίσω από `SmsSender`), σε queue (σταθερές θέσεις και αποδείξεις όταν υπάρξουν)
- [x] Προτιμήσεις ειδοποιήσεων ανά τύπο και κανάλι
- [x] Ανακοινώσεις / carousel στην αρχική πελάτη
- [x] Επεξεργάσιμα πρότυπα email από admin

## 7. Billing, ελάχιστο (BL-01)
- [x] Καταχώρηση είσπραξης (μετρητά, POS, έμβασμα) συνδεδεμένη με συνδρομή

## 8. Check-in κάρτα (CI-01)
- [x] Σελίδα με QR από `checkin_token` (υπογεγραμμένο με HMAC), επανέκδοση token

## 9. Reporting (RP-01, RP-02)
- [x] Dashboard: σημερινά sessions και πληρότητα, έσοδα μήνα, ενεργές συνδρομές, λήξεις εβδομάδας, no-show rate
- [x] Εξαγωγή σε Excel (xlsx/csv, queued) στις κύριες λίστες: χρήστες, κρατήσεις, συνδρομές, πακέτα, εισπράξεις, μαθήματα, υπηρεσίες

## 10. Παράδοση
- [x] PWA: manifest, service worker, responsive από 360 px
- [x] Seeders με ρεαλιστικά demo δεδομένα
- [x] Deploy script (Deployer), backups (`spatie/laravel-backup`), uptime και error monitoring (οδηγός: `docs/DEPLOYMENT.md`)
- [ ] Πιλοτική χρήση (Πύλη 1)
