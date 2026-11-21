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
- [ ] Εγγραφή με επιβεβαίωση email, αποδοχή όρων με έκδοση και ώρα
- [ ] Login, logout, reset password, αλλαγή κωδικού, session lifetime από settings
- [ ] Προφίλ πελάτη, διαγραφή λογαριασμού (ανωνυμοποίηση)
- [ ] Ρόλοι customer / instructor / reception / admin με Policies. Δημιουργία πελάτη από admin με πρόσκληση
- [ ] `checkin_token` ανά χρήστη

## 2. Catalog και Scheduling (SC-01 έως SC-04)
- [ ] Κατηγορίες, υπηρεσίες (duration, capacity, credit_cost, policy_overrides) στο Filament
- [ ] Schedules, closures, `class_sessions`
- [ ] `GenerateSessions` job (idempotent, παραλείπει αργίες)
- [ ] Αλλαγή και ακύρωση session (το refund των κρατήσεων έρχεται στο βήμα 4)

## 3. Membership (MB-01, MB-02, MB-04, MB-05, MB-07)
- [ ] Packages + pivot υπηρεσιών
- [ ] Memberships με snapshot όρων πακέτου
- [ ] `CreditLedger` interface + υλοποίηση με `credit_transactions` (append-only, `balance_after`)
- [ ] `MembershipSelector` (FEFO)
- [ ] Πώληση πακέτου από υποδοχή, χειροκίνητη διόρθωση με αιτιολογία
- [ ] `ExpireMemberships`, `WarnExpiringMemberships`
- [ ] Test: ledger invariant

## 4. Booking (BK-01, BK-02, BK-04 έως BK-09)
- [ ] Enum `BookingStatus` με transitions (ενότητα 7.1)
- [ ] `BookingRule` + `BookingRuleChain` και οι 6 κανόνες της ενότητας 4
- [ ] `BookSession` με `lockForUpdate` και unique index ενεργών κρατήσεων
- [ ] `CancelBooking` (εντός και εκτός ορίου), `RescheduleBooking` (ατομική συναλλαγή)
- [ ] Κράτηση και ακύρωση από προσωπικό με override και audit
- [ ] Παρουσία / no-show, `MarkNoShows`
- [ ] Ακύρωση session → refund σε όλους
- [ ] Customer UI: υπηρεσία → μηνιαίο ημερολόγιο → ώρες με διαθέσιμες θέσεις και λίστα αναμονής → επιβεβαίωση
- [ ] «Τα ραντεβού μου»: Upcoming, Μήνας, Εβδομάδα, Ιστορικό, λεπτομέρειες, .ics
- [ ] `SendBookingReminders`
- [ ] Test: δύο ταυτόχρονες κρατήσεις για την τελευταία θέση

## 5. Waitlist (WL-01 έως WL-03)
- [ ] Είσοδος και έξοδος από λίστα αναμονής, θέση στην ουρά
- [ ] `SeatFreed` → `PromoteFromWaitlist` (auto_book και offer), `ExpireWaitlistOffers`, cut-off

## 6. Communication (CM-01, CM-02, CM-04)
- [ ] Notifications για όλα τα events της ενότητας 8.1 (email), σε queue
- [ ] Προτιμήσεις ειδοποιήσεων ανά τύπο και κανάλι
- [ ] Ανακοινώσεις / carousel στην αρχική πελάτη
- [ ] Επεξεργάσιμα πρότυπα email από admin

## 7. Billing, ελάχιστο (BL-01)
- [ ] Καταχώρηση είσπραξης (μετρητά, POS, έμβασμα) συνδεδεμένη με συνδρομή

## 8. Check-in κάρτα (CI-01)
- [ ] Σελίδα με QR από `checkin_token`, επανέκδοση token

## 9. Reporting (RP-01, RP-02)
- [ ] Dashboard: σημερινά sessions και πληρότητα, έσοδα μήνα, ενεργές συνδρομές, λήξεις εβδομάδας, no-show rate
- [ ] Εξαγωγή σε Excel σε όλες τις λίστες

## 10. Παράδοση
- [ ] PWA: manifest, service worker, responsive από 360 px
- [ ] Seeders με ρεαλιστικά demo δεδομένα
- [ ] Deploy script (Deployer), backups (`spatie/laravel-backup`), uptime και error monitoring
- [ ] Πιλοτική χρήση (Πύλη 1)
