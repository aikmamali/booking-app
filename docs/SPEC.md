# Προδιαγραφές: Εφαρμογή Κρατήσεων & Συνδρομών

Nov 21, 2026 · @Katerina

Μια web εφαρμογή (PWA) για **μία επιχείρηση** (π.χ. studio γυμναστικής): οι πελάτες κλείνουν θέσεις σε ομαδικά ή ατομικά μαθήματα και χρησιμοποιούν πακέτα credits, ενώ η επιχείρηση διαχειρίζεται πρόγραμμα, συνδρομές, πληρωμές και επικοινωνία. Τεχνολογίες: Laravel 12, Filament (admin), Livewire (περιοχή πελάτη), MySQL 8. Η λειτουργικότητα βασίζεται στην ανάλυση του Bookup.gr. Κώδικας, κείμενα και γραφικά γράφονται από την αρχή.

## 1. Επισκόπηση

### Εύρος

| Μέσα στο εύρος | Εκτός εύρους |
| --- | --- |
| Κρατήσεις με χωρητικότητα, ακύρωση, μεταφορά | Marketplace / κατάλογος επιχειρήσεων |
| Λίστα αναμονής με αυτόματη προώθηση | Πολλές επιχειρήσεις (multi-tenancy) |
| Σταθερές θέσεις ανά σεζόν | Native εφαρμογές iOS/Android (αντί αυτών PWA) |
| Πακέτα credits και συνδρομές | Αγαπημένα, παραπομπές (μεταγενέστερα, αν χρειαστούν) |
| Online πληρωμές (Stripe), αποδείξεις myDATA | Λογιστικό / μισθοδοσία |
| Check-in με barcode/QR |  |
| Email/SMS ειδοποιήσεις, ανακοινώσεις |  |
| Admin panel, στατιστικά, εξαγωγή Excel |  |
| GDPR: συναίνεση, εξαγωγή και διαγραφή δεδομένων |  |

### Γλωσσάρι

| Όρος | Ορισμός |
| --- | --- |
| **Υπηρεσία (Service)** | Είδος μαθήματος, π.χ. Group Training. Έχει διάρκεια, χωρητικότητα και κόστος σε credits |
| **Πρόγραμμα (Schedule)** | Επαναλαμβανόμενος κανόνας: «Τρίτη 18:30, Group Training, εκπαιδευτής Χ, 5 θέσεις» |
| **Session** | Συγκεκριμένη εμφάνιση στο ημερολόγιο (24/11/2026 18:30), παραγόμενη από το πρόγραμμα |
| **Κράτηση (Booking)** | Θέση ενός πελάτη σε ένα session |
| **Credit** | Μονάδα χρέωσης. Ένα session μπορεί να κοστίζει 1 ή 0,5 credit |
| **Πακέτο (Package)** | Προϊόν προς πώληση: «10 μαθήματα, ισχύς 3 μήνες, 50€» |
| **Συνδρομή (Membership)** | Ένα αγορασμένο πακέτο ενός πελάτη, με υπόλοιπο και ημερομηνία λήξης |
| **Σταθερή θέση (Fixed seat)** | Μόνιμη κράτηση ενός πελάτη σε επαναλαμβανόμενο slot για μια σεζόν |
| **Ledger** | Αμετάβλητο ιστορικό κινήσεων credits, από το οποίο προκύπτει το υπόλοιπο |

## 2. Ρόλοι και δικαιώματα

Τέσσερις ρόλοι με RBAC μέσω `spatie/laravel-permission`. Οι έλεγχοι γίνονται με Laravel Policies, ποτέ με έλεγχο ρόλου μέσα σε controllers ή views.

| Δικαίωμα | Πελάτης | Εκπαιδευτής | Υποδοχή | Διαχειριστής |
| --- | --- | --- | --- | --- |
| Κράτηση / ακύρωση / μεταφορά για τον εαυτό του | ✓ (εντός κανόνων) | – | – | – |
| Κράτηση / ακύρωση για άλλον πελάτη (και εκτός κανόνων) | – | Μόνο στα δικά του sessions | ✓ | ✓ |
| Προβολή λίστας συμμετεχόντων και check-in | – | Μόνο τα δικά του | ✓ | ✓ |
| Σημειώσεις πελάτη / ασκησιολόγιο | Βλέπει όσες είναι «ορατές στον πελάτη» | ✓ | ✓ | ✓ |
| Πώληση πακέτου, χειροκίνητη είσπραξη, έκδοση απόδειξης | Αγορά online | – | ✓ | ✓ |
| Χειροκίνητη διόρθωση credits (με αιτιολογία) | – | – | – | ✓ |
| Υπηρεσίες, πρόγραμμα, πακέτα, ρυθμίσεις κανόνων | – | – | – | ✓ |
| Εξοδα, στατιστικά, εξαγωγές Excel | – | – | Προβολή ημέρας | ✓ |
| Μαζικά email/SMS, ανακοινώσεις | – | – | – | ✓ |
| Διαχείριση χρηστών προσωπικού | – | – | – | ✓ |
| Προφίλ, εξαγωγή και διαγραφή των δικών του δεδομένων | ✓ | ✓ | ✓ | ✓ |

Ο πελάτης μπαίνει στην περιοχή `/app` (Livewire). Το προσωπικό μπαίνει στο `/admin` (Filament panel), όπου κάθε Resource κρύβεται ανάλογα με την Policy του.

## 3. Λειτουργικές απαιτήσεις

Οι απαιτήσεις ομαδοποιούνται σε 10 modules. Κάθε κωδικός (π.χ. BK-03) γίνεται ένα ticket. Η στήλη Φάση αντιστοιχεί στο roadmap (ενότητα 13).

### 3.1 Λογαριασμοί (Identity)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| ID-01 | πελάτης, να εγγραφώ με email και κωδικό | Επιβεβαίωση email. Υποχρεωτικά: όνομα, επώνυμο, κινητό (E.164). Αποδοχή όρων και πολιτικής απορρήτου με καταγραφή έκδοσης και ώρας | MVP |
| ID-02 | πελάτης, να επαναφέρω τον κωδικό μου και να τον αλλάξω | Link με λήξη 60'. Η αλλαγή θέλει τον παλιό κωδικό | MVP |
| ID-03 | πελάτης, να ενημερώνω το προφίλ μου | Ονοματεπώνυμο, δύο τηλέφωνα, email (με επανεπιβεβαίωση), ΑΦΜ για τιμολόγιο (προαιρετικό), γλώσσα | MVP |
| ID-04 | πελάτης, να διαγράψω τον λογαριασμό μου | Ανωνυμοποίηση προσωπικών στοιχείων. Τα φορολογικά παραστατικά διατηρούνται. Μελλοντικές κρατήσεις ακυρώνονται | MVP |
| ID-05 | διαχειριστής, να δημιουργώ πελάτη από το admin και να στέλνω πρόσκληση | Email με link ορισμού κωδικού | MVP |
| ID-06 | χρήστης, να αποσυνδέομαι αυτόματα μετά από αδράνεια | Session lifetime ρυθμιζόμενο (default 120'). «Να με θυμάσαι» για το PWA | MVP |
| ID-07 | διαχειριστής, 2FA στο admin | TOTP υποχρεωτικό για ρόλους προσωπικού | Φάση 2 |

### 3.2 Κατάλογος και πρόγραμμα (Catalog & Scheduling)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| SC-01 | διαχειριστής, να ορίζω κατηγορίες και υπηρεσίες | Όνομα (πολυγλωσσικό), φωτογραφία, περιγραφή, διάρκεια, default χωρητικότητα, κόστος credits (δεκαδικό, π.χ. 0,5), τιμή drop-in σε € (προαιρετική), ενεργό/ανενεργό, σειρά εμφάνισης | MVP |
| SC-02 | διαχειριστής, να ορίζω επαναλαμβανόμενο εβδομαδιαίο πρόγραμμα | Ημέρα, ώρα, υπηρεσία, εκπαιδευτής, χωρητικότητα, χώρος, ισχύς από/έως. Τα sessions παράγονται αυτόματα για N εβδομάδες μπροστά (ρύθμιση, default 8) | MVP |
| SC-03 | διαχειριστής, να αλλάζω ή να ακυρώνω ένα μεμονωμένο session | Αλλαγή εκπαιδευτή/χωρητικότητας/ώρας. Η ακύρωση επιστρέφει credits σε όλους και στέλνει ειδοποίηση | MVP |
| SC-04 | διαχειριστής, να ορίζω ωράριο και αργίες | Κλειστές μέρες/διαστήματα. Τα sessions σε αργία δεν παράγονται ή ακυρώνονται με ειδοποίηση | MVP |
| SC-05 | διαχειριστής, ατομικά ραντεβού (personal) από διαθεσιμότητα εκπαιδευτή | Διαθεσιμότητα εκπαιδευτή + διάρκεια υπηρεσίας → ελεύθερα slots χωρητικότητας 1 | Φάση 3 |

### 3.3 Κρατήσεις (Booking)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| BK-01 | πελάτης, να διαλέξω υπηρεσία → μέρα → ώρα | Μηνιαίο ημερολόγιο με καταστάσεις διαθέσιμη / κλειστή / παρελθόν. Ανά ώρα: ελεύθερες θέσεις και μέγεθος λίστας αναμονής | MVP |
| BK-02 | πελάτης, να κλείσω θέση χρεώνοντας τη συνδρομή μου | Ατομική συναλλαγή: έλεγχος χωρητικότητας (με row lock), έγκυρη συνδρομή που καλύπτει την υπηρεσία και είναι ενεργή την ημέρα του session, αρκετό υπόλοιπο, όχι διπλή κράτηση, όριο κρατήσεων ανά ημέρα. Χρέωση στο ledger και επιβεβαίωση με email | MVP |
| BK-03 | πελάτης χωρίς συνδρομή, να κλείσω με πληρωμή (drop-in) | Stripe Checkout. Η κράτηση είναι `pending_payment` με κράτηση θέσης 15' και γίνεται `confirmed` μόνο μέσω webhook | Φάση 2 |
| BK-04 | πελάτης, να ακυρώσω με προαιρετικό λόγο | Εντός ορίου (default 3 ώρες): επιστροφή credit. Εκτός ορίου: κουμπί ανενεργό με εξήγηση (late cancel μόνο από το προσωπικό). Η οθόνη δείχνει πριν την επιβεβαίωση αν θα επιστραφεί το credit | MVP |
| BK-05 | πελάτης, να μεταφέρω την κράτηση σε άλλη ώρα | Ιδιοι κανόνες ορίου με την ακύρωση. Ατομικά: ακύρωση παλιάς και κράτηση νέας σε μία συναλλαγή, χωρίς δεύτερη χρέωση. Δεν μετράει ως ακύρωση στον κανόνα σερί | MVP |
| BK-06 | πελάτης, να βλέπω τα ραντεβού μου | Προβολές Upcoming, Μήνας, Εβδομάδα, Ιστορικό. Λεπτομέρειες: αρ. κράτησης, κατάσταση, εκπαιδευτής, ιστορικό ενεργειών, σημειώσεις. Εξαγωγή .ics / «Προσθήκη στο Google Calendar» | MVP |
| BK-07 | υποδοχή, να κλείνω/ακυρώνω για πελάτη | Μπορεί να παρακάμψει όρια (override) με υποχρεωτική αιτιολογία. Το override καταγράφεται στο audit log | MVP |
| BK-08 | εκπαιδευτής, να σημειώνω παρουσία / no-show | Καταστάσεις `attended`, `no_show`. Το no-show δεν επιστρέφει credit | MVP |
| BK-09 | πελάτης, να λαμβάνω υπενθύμιση | Checkbox στην κράτηση (default on). Αποστολή X ώρες πριν (ρύθμιση) | MVP |
| BK-10 | διαχειριστής, μαζικές κρατήσεις | Επιλογή πελατών × sessions σε ένα βήμα, με αναφορά αποτυχιών ανά γραμμή (γεμάτο, χωρίς credits) | Φάση 2 |
| BK-11 | πελάτης και προσωπικό, μηνύματα πάνω στην κράτηση | Thread ανά κράτηση. Ειδοποίηση με email στον άλλον | Φάση 3 |

### 3.4 Λίστα αναμονής (Waitlist)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| WL-01 | πελάτης, να μπω σε λίστα αναμονής για γεμάτο session | Βλέπω τη θέση μου στη ουρά. Μέγιστο μέγεθος λίστας ρυθμιζόμενο. Δεν δεσμεύεται credit | MVP |
| WL-02 | σύστημα, να προωθεί αυτόματα τον πρώτο όταν αδειάσει θέση | Δύο στρατηγικές (ρύθμιση): **auto-book** (κράτηση και χρέωση, αν έχει credits) ή **offer** (ειδοποίηση με περιθώριο X λεπτών για αποδοχή, μετά στον επόμενο). Δεν γίνεται προώθηση μέσα στο cut-off (π.χ. 1 ώρα πριν) | MVP |
| WL-03 | πελάτης, να βγω από τη λίστα | Χωρίς συνέπειες | MVP |

### 3.5 Σταθερές θέσεις (Fixed seats)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| FS-01 | διαχειριστής, να ορίζω σεζόν (από/έως) | Π.χ. 31/8/2026–30/6/2027 | Φάση 2 |
| FS-02 | πελάτης, να ζητήσω σταθερή θέση σε ένα ή περισσότερα slots της εβδομάδας | Αίτηση `requested` → έγκριση/απόρριψη από διαχειριστή | Φάση 2 |
| FS-03 | σύστημα, να δημιουργεί αυτόματα κρατήσεις από σταθερές θέσεις | Κάθε φορά που παράγονται sessions. Χρέωση credit. Αν δεν υπάρχουν credits: ειδοποίηση και κράτηση θέσης ως `pending_credits` για X ώρες | Φάση 2 |
| FS-04 | σύστημα, να αφαιρεί τη σταθερή θέση μετά από N συνεχόμενες ακυρώσεις | Default N = 4. Ο μετρητής μηδενίζεται με κάθε παρουσία. Ειδοποίηση στην N−1 ακύρωση | Φάση 2 |

### 3.6 Συνδρομές και credits (Memberships)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| MB-01 | διαχειριστής, να ορίζω πακέτα | Ονομα, credits (ή «απεριόριστα» με όριο ανά εβδομάδα), διάρκεια ισχύος, τιμή, καλυπτόμενες υπηρεσίες, αγοράζεται online ναι/όχι | MVP |
| MB-02 | υποδοχή, να πουλάω πακέτο σε πελάτη (μετρητά/POS) | Δημιουργεί membership, κίνηση ledger `purchase` και είσπραξη. Η έναρξη είναι σήμερα ή επιλεγμένη ημερομηνία | MVP |
| MB-03 | πελάτης, να αγοράζω πακέτο online | Stripe Checkout. Η συνδρομή ενεργοποιείται μόνο από το webhook (idempotent) | Φάση 2 |
| MB-04 | πελάτης, να βλέπω τη συνδρομή μου | Περίοδος, κατάσταση, υπόλοιπο, ολοκληρωμένα / επόμενα ραντεβού, ιστορικό κινήσεων ledger | MVP |
| MB-05 | σύστημα, να λήγει συνδρομές και να ειδοποιεί | Ειδοποίηση στις 7 μέρες και όταν το υπόλοιπο ≤ 1. Κίνηση `expire` για τα αδιάθετα credits | MVP |
| MB-06 | διαχειριστής, παγωμάτηση ή παράταση συνδρομής | Π.χ. για τραυματισμό. Η λήξη μετατοπίζεται και καταγράφεται στο audit | Φάση 2 |
| MB-07 | διαχειριστής, χειροκίνητη διόρθωση credits | Κίνηση `adjustment` με υποχρεωτική αιτιολογία. Ποτέ UPDATE στο υπόλοιπο | MVP |

### 3.7 Πληρωμές και οικονομικά (Billing)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| BL-01 | υποδοχή, να καταγράφω είσπραξη | Τρόπος: μετρητά, POS, κάρτα online, έμβασμα. Συνδέεται με συνδρομή ή κράτηση | MVP |
| BL-02 | διαχειριστής, να καταγράφω έξοδα | Κατηγορία, ποσό, ημερομηνία, σημείωση, συνημμένο | Φάση 2 |
| BL-03 | σύστημα, να εκδίδει απόδειξη myDATA για κάθε είσπραξη | ΑΠΥ (ή ΤΠΥ αν δοθεί ΑΦΜ). Αποθήκευση MARK, UID και QR. Ο πελάτης κατεβάζει PDF. Η ακύρωση γίνεται με πιστωτικό, όχι με διαγραφή | Φάση 2 |
| BL-04 | πελάτης, να ζητώ επιστροφή χρημάτων | Αίτημα `requested` → έγκριση (refund μέσω Stripe ή χειροκίνητα) ή απόρριψη με αιτιολογία | Φάση 3 |

### 3.8 Check-in

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| CI-01 | πελάτης, να έχω ψηφιακή κάρτα | QR με υπογεγραμμένο token (όχι απλό αύξοντα αριθμό), επανεκδόσιμο | MVP |
| CI-02 | υποδοχή, να σκανάρω την κάρτα | Κάμερα στο τάμπλετ. Δείχνει στοιχεία, κράτηση της ώρας, υπόλοιπο και προειδοποιήσεις (λήξη, λείπει GDPR). Η κράτηση γίνεται `attended` | Φάση 2 |

### 3.9 Επικοινωνία (Communication)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| CM-01 | πελάτης, να λαμβάνω συναλλακτικά email | Κράτηση, ακύρωση, μεταφορά, προώθηση από λίστα αναμονής, λήξη, απόδειξη. Πρότυπα επεξεργάσιμα από το admin | MVP |
| CM-02 | πελάτης, να επιλέγω κανάλια ειδοποιήσεων | Email / SMS / push ανά τύπο. Τα marketing μηνύματα θέλουν ξεχωριστή συναίνεση | MVP |
| CM-03 | διαχειριστής, μαζικά email/SMS σε φιλτραρισμένο κοινό | Φίλτρα: ενεργή συνδρομή, λήγει σε X μέρες, υπηρεσία. Αποστολή μέσω queue, εξαιρούνται όσοι δεν έχουν συναίνεση | Φάση 2 |
| CM-04 | διαχειριστής, ανακοινώσεις και banner | Carousel εικόνων και κείμενο στην αρχική, με ημερομηνίες δημοσίευσης | MVP |
| CM-05 | πελάτης, αιτήματα υποστήριξης | Τίτλος, περιγραφή, κατάσταση (open → answered → closed), απαντήσεις | Φάση 3 |
| CM-06 | διαχειριστής, ψηφιακά ερωτηματολόγια (π.χ. ιατρικό ιστορικό, φόρμα εγγραφής) | Form builder με πεδία JSON. Υποχρεωτικό πριν την πρώτη κράτηση (ρύθμιση) | Φάση 3 |

### 3.10 Αναφορές και σημειώσεις (Reporting & CRM)

| ID | Ως … θέλω … | Acceptance criteria | Φάση |
| --- | --- | --- | --- |
| RP-01 | διαχειριστής, dashboard | Σημερινά sessions και πληρότητα, έσοδα μήνα, ενεργές συνδρομές, λήξεις εβδομάδας, no-show rate | MVP |
| RP-02 | διαχειριστής, εξαγωγή κάθε λίστας σε Excel | Filament export actions (queued) | MVP |
| RP-03 | εκπαιδευτής, σημειώσεις πελάτη και ασκησιολόγιο | Ανά πελάτη ή ανά κράτηση, με επιλογή «ορατή στον πελάτη» | Φάση 2 |
| RP-04 | διαχειριστής, στατιστικά ανά υπηρεσία / εκπαιδευτή / ώρα | Πληρότητα, ακυρώσεις, έσοδα ανά περίοδο | Φάση 3 |

## 4. Επιχειρησιακοί κανόνες ως ρυθμίσεις

Κανένας αριθμός δεν είναι hard-coded. Όλοι οι κανόνες ζουν σε ένα typed settings object (`spatie/laravel-settings`, κλάση `BookingPolicySettings`) και αλλάζουν από σελίδα ρυθμίσεων του Filament. Όπου χρειάζεται, μια υπηρεσία μπορεί να παρακάμψει την προεπιλογή (στήλη «Ανά υπηρεσία»).

| Κλειδί | Default | Ανά υπηρεσία | Σημασία |
| --- | --- | --- | --- |
| `cancellation_cutoff_minutes` | 180 | Ναι | Ακύρωση ή μεταφορά με επιστροφή credit έως τόσα λεπτά πριν |
| `late_cancel_allowed` | false | Ναι | Αν ο πελάτης μπορεί να ακυρώσει μετά το όριο χάνοντας το credit (απελευθερώνει θέση για λίστα αναμονής) |
| `booking_opens_days_ahead` | 14 | Ναι | Πόσες μέρες μπροστά ανοίγουν οι κρατήσεις |
| `booking_closes_minutes_before` | 0 | Ναι | Μέχρι πότε δέχεται νέες κρατήσεις |
| `max_bookings_per_day` | 1 | – | Ανά πελάτη |
| `max_active_bookings` | null | – | Ανώτατος αριθμός μελλοντικών κρατήσεων (null = χωρίς όριο) |
| `default_capacity` | 5 | Ναι | Θέσεις ανά session |
| `credit_cost` | 1.0 | Ναι | Στην υπηρεσία. Δεκαδικό με βήμα 0,5 |
| `waitlist_enabled` | true | Ναι |  |
| `waitlist_max_size` | 5 | Ναι |  |
| `waitlist_strategy` | auto\_book | – | `auto_book` ή `offer` |
| `waitlist_offer_ttl_minutes` | 30 | – | Μόνο για `offer` |
| `waitlist_cutoff_minutes` | 60 | – | Καμία προώθηση τόσα λεπτά πριν |
| `fixed_seat_max_consecutive_cancellations` | 4 | – | Κανόνας αυτόματης αφαίρεσης σταθερής θέσης |
| `fixed_seat_pending_credits_hours` | 24 | – | Πόσο κρατιέται η θέση αν λείπουν credits |
| `no_show_refunds_credit` | false | – |  |
| `reminder_hours_before` | \[24, 2\] | – | Πολλαπλές υπενθυμίσεις |
| `membership_expiry_warning_days` | 7 | – |  |
| `low_balance_threshold` | 1 | – |  |
| `sessions_generation_weeks` | 8 | – | Ορίζων παραγωγής sessions από το πρόγραμμα |
| `timezone` | Europe/Athens | – | Αποθήκευση σε UTC, εμφάνιση σε αυτή τη ζώνη |

**Κανόνας για το εξελίξιμο:** κάθε έλεγχος κράτησης είναι μια κλάση `BookingRule` με μία μέθοδο `check(BookingContext): RuleResult`. Οι κανόνες δηλώνονται σε μία λίστα (Chain of Responsibility). Νέος κανόνας = νέα κλάση και μία γραμμή καταχώρισης, χωρίς αλλαγή στον υπάρχοντα κώδικα (Open/Closed).

| Κανόνας κράτησης | Μήνυμα απόρριψης (i18n key) |
| --- | --- |
| `SessionIsBookable` (όχι παρελθόν, όχι ακυρωμένο, μέσα στο παράθυρο κρατήσεων) | `booking.session_closed` |
| `HasCapacity` | `booking.full` |
| `NotAlreadyBooked` | `booking.duplicate` |
| `WithinDailyLimit` | `booking.daily_limit` |
| `HasValidMembership` (καλύπτει υπηρεσία, ενεργή την ημέρα του session, αρκετό υπόλοιπο) | `booking.no_membership` / `booking.insufficient_credits` |
| `RequiredFormsCompleted` (GDPR, ερωτηματολόγιο) | `booking.forms_missing` |

## 5. Αρχιτεκτονική

**Modular monolith**: μία εφαρμογή Laravel, χωρισμένη σε επιχειρησιακά modules (bounded contexts) με σαφή όρια. Ένα deploy, μία βάση, αλλά κώδικας που μπορεί να σπάσει αργότερα αν χρειαστεί. Οι αρχές θα σου είναι οικείες από Spring: application services, DTOs, domain events, repositories μόνο όπου προσθέτουν αξία.

&#91;embedded content: αρχιτεκτονική · 3 επίπεδα, 8 modules\]

Το αίτημα κατεβαίνει μόνο προς τα κάτω. Το Booking είναι το κέντρο βάρους της επιχειρησιακής λογικής, και κάθε εξωτερική υπηρεσία αλλάζει με νέο adapter.

### 5.1 Layers και κανόνες εξάρτησης

| Layer | Περιέχει | Εξαρτάται από |
| --- | --- | --- |
| **UI** | Filament Resources, Livewire components, Blade, HTTP controllers (webhooks, API) | Application |
| **Application** | Actions (ένα use case ανά κλάση: `BookSession`, `CancelBooking`), DTOs, Queries | Domain |
| **Domain** | Eloquent models, Enums, Value Objects (`Credits`, `Money`, `TimeSlot`), Rules, Domain Events, Exceptions | Τίποτα έξω από το module |
| **Infrastructure** | Adapters για Stripe, myDATA, SMS, mail. Υλοποιούν interfaces του Domain | Domain (interfaces) |

- Controllers και Livewire είναι λεπτά: validation μέσω Form Request / DTO, κλήση ενός Action, επιστροφή αποτελέσματος. Καμία επιχειρησιακή λογική.
- Το Filament καλεί τα ίδια Actions με τον πελάτη. Ένας κανόνας κράτησης δεν γράφεται δύο φορές.
- Ένα module δεν διαβάζει πίνακες άλλου module. Συνεργάζεται μέσω δημόσιου συμβολαίου (π.χ. `CreditLedger` interface) ή domain events.
- Η αυστηρότητα ελέγχεται αυτόματα στο CI με **Pest arch tests** (π.χ. «το `Domain` δεν εξαρτάται από `Filament` ή `Http`»).

### 5.2 Modules

| Module | Ευθύνη | Δημόσιο συμβόλαιο |
| --- | --- | --- |
| `Identity` | Χρήστες, ρόλοι, προφίλ, GDPR | `User` model, `UserDeleted` event |
| `Catalog` | Κατηγορίες, υπηρεσίες, χώροι | `Service` |
| `Scheduling` | Πρόγραμμα, αργίες, παραγωγή sessions | `ClassSession`, `SessionsGenerated`, `SessionCancelled` |
| `Booking` | Κρατήσεις, λίστα αναμονής, σταθερές θέσεις, check-in | `BookSession`, `CancelBooking`, `BookingConfirmed`, `BookingCancelled`, `SeatFreed` |
| `Membership` | Πακέτα, συνδρομές, credits ledger | `CreditLedger` interface (`debit`, `refund`, `balance`) |
| `Billing` | Πληρωμές, επιστροφές, έξοδα, αποδείξεις | `PaymentGateway`, `ReceiptIssuer` interfaces, `PaymentSucceeded` |
| `Communication` | Ειδοποιήσεις, πρότυπα, ανακοινώσεις, tickets, ερωτηματολόγια | Ακούει events άλλων modules |
| `Reporting` | Dashboard, στατιστικά, εξαγωγές | Μόνο read queries |

### 5.3 Δομή φακέλων

```text
app/
├── Modules/
│   ├── Booking/
│   │   ├── Actions/            BookSession.php, CancelBooking.php, RescheduleBooking.php
│   │   ├── Data/               BookSessionData.php (spatie/laravel-data DTO)
│   │   ├── Enums/              BookingStatus.php
│   │   ├── Events/             BookingConfirmed.php, BookingCancelled.php, SeatFreed.php
│   │   ├── Exceptions/         BookingRejected.php
│   │   ├── Jobs/               PromoteFromWaitlist.php
│   │   ├── Listeners/
│   │   ├── Models/             Booking.php, WaitlistEntry.php, FixedSeat.php
│   │   ├── Policies/           BookingPolicy.php
│   │   ├── Rules/              HasCapacity.php, HasValidMembership.php, …
│   │   ├── Filament/           BookingResource.php
│   │   ├── Livewire/           BookingWizard.php, MyBookings.php
│   │   └── BookingServiceProvider.php
│   ├── Membership/
│   ├── Scheduling/
│   └── …
├── Shared/                     Money.php, Credits.php, Clock interface, base classes
database/migrations/            μία ανά αλλαγή, ονομασία με πρόθεμα module
tests/
├── Arch/                       κανόνες εξάρτησης
├── Unit/                       rules, value objects
└── Feature/                    actions end-to-end με βάση
```

### 5.4 Patterns και πακέτα

| Ανάγκη | Λύση |
| --- | --- |
| Use cases | Action classes με μία `handle()`, constructor injection |
| DTOs / validation | `spatie/laravel-data` |
| Καταστάσεις | PHP 8.3 backed enums με μέθοδο `canTransitionTo()` |
| Ρυθμίσεις | `spatie/laravel-settings` |
| Ρόλοι | `spatie/laravel-permission` + Filament Shield |
| Audit | `spatie/laravel-activitylog` |
| Εξωτερικές υπηρεσίες | Interface στο Domain + Adapter στο Infrastructure, bind στο ServiceProvider. Στα tests: fake adapter |
| Χρόνος | Ποτέ `now()` σε τεσταρισμένη λογική χωρίς `Carbon::setTestNow()`. Οι κανόνες δέχονται τον χρόνο στο `BookingContext` |
| Συνεχής επικοινωνία modules | Laravel events. Οι βαριοί listeners υλοποιούν `ShouldQueue` και `ShouldDispatchAfterCommit` |

## 6. Μοντέλο δεδομένων

Η βάση έχει περίπου 25 πίνακες. Οι κανόνες είναι κοινοί σε όλους:

- Ένα `id` (bigint) ανά πίνακα. Σε ό,τι εκτίθεται σε URL (κρατήσεις, αποδείξεις) υπάρχει και `public_id` (ULID).
- Αποθήκευση χρόνων σε UTC (`timestamp`).
- Χρήματα σε λεπτά (`unsigned int`). Credits σε `decimal(6,2)`.
- Soft deletes μόνο σε κατάλογο (υπηρεσίες, πακέτα). Οικονομικά και ledger δεν διαγράφονται ποτέ.

### 6.1 Πίνακες

| Πίνακας | Module | Βασικά πεδία | Σημειώσεις |
| --- | --- | --- | --- |
| `users` | Identity | first\_name, last\_name, email, phone, phone\_alt, vat\_number?, locale, checkin\_token, marketing\_consent\_at?, terms\_accepted\_version, terms\_accepted\_at, last\_login\_at | Ένας πίνακας για πελάτες και προσωπικό. Ο ρόλος δίνεται από το spatie permission |
| `service_categories` | Catalog | name (json, i18n), position |  |
| `services` | Catalog | category\_id, name (json), description (json), image\_path, duration\_minutes, default\_capacity, credit\_cost, drop\_in\_price\_cents?, is\_active, position, policy\_overrides (json) |  |
| `rooms` | Catalog | name, capacity | Προαιρετικό |
| `schedules` | Scheduling | service\_id, instructor\_id, room\_id?, weekday, start\_time, capacity, valid\_from, valid\_until? | Επαναλαμβανόμενος κανόνας |
| `class_sessions` | Scheduling | schedule\_id?, service\_id, instructor\_id, starts\_at, ends\_at, capacity, status (scheduled/cancelled), cancel\_reason?, **lock\_version** | UNIQUE(schedule\_id, starts\_at). INDEX(starts\_at, status) |
| `closures` | Scheduling | starts\_on, ends\_on, reason | Αργίες και κλειστές μέρες |
| `bookings` | Booking | public\_id, user\_id, class\_session\_id, membership\_id?, fixed\_seat\_id?, status, credits\_charged, source (customer/staff/fixed\_seat/waitlist), cancelled\_at?, cancel\_reason?, cancelled\_by?, late\_cancel (bool), checked\_in\_at?, reminded\_at? | UNIQUE(user\_id, class\_session\_id) για ενεργές (μέσω generated column `active_key`). INDEX(class\_session\_id, status) |
| `waitlist_entries` | Booking | user\_id, class\_session\_id, position, status (waiting/offered/promoted/expired/left), offered\_until? | UNIQUE(user\_id, class\_session\_id) |
| `seasons` | Booking | name, starts\_on, ends\_on |  |
| `fixed_seats` | Booking | user\_id, schedule\_id, season\_id, status (requested/active/revoked/ended), consecutive\_cancellations, revoked\_reason? |  |
| `packages` | Membership | name, credits? (null = απεριόριστα), weekly\_limit?, validity\_days, price\_cents, vat\_rate, is\_purchasable\_online, is\_active |  |
| `package_service` | Membership | package\_id, service\_id | Pivot: ποιες υπηρεσίες καλύπτει |
| `memberships` | Membership | user\_id, package\_id, starts\_on, ends\_on, status (pending/active/frozen/expired/cancelled), frozen\_until?, payment\_id? | Snapshot των όρων πακέτου τη στιγμή της αγοράς |
| `credit_transactions` | Membership | membership\_id, booking\_id?, type (purchase/debit/refund/expire/adjustment), amount (±), balance\_after, reason?, actor\_id?, created\_at | **Append-only.** Το υπόλοιπο υπολογίζεται από εδώ, ποτέ UPDATE |
| `payments` | Billing | public\_id, user\_id, payable\_type/id, amount\_cents, method (cash/pos/card/transfer), status (pending/succeeded/failed/refunded), provider\_ref?, paid\_at | Morph σε membership ή booking |
| `receipts` | Billing | payment\_id, series, number, type (ΑΠΥ/ΤΠΥ/πιστωτικό), net, vat, gross, mydata\_mark?, mydata\_uid?, qr\_url?, status (draft/transmitted/failed/cancelled), pdf\_path | UNIQUE(series, number) |
| `refund_requests` | Billing | user\_id, payment\_id, amount\_cents, reason, status, decided\_by?, decided\_at? |  |
| `expenses` | Billing | category, amount\_cents, spent\_on, note, attachment\_path? |  |
| `notes` | Communication | notable\_type/id (user ή booking), author\_id, body, visible\_to\_customer | Ασκησιολόγιο |
| `announcements` | Communication | title, body, image\_path?, published\_from, published\_until? |  |
| `notification_preferences` | Communication | user\_id, type, channel, enabled |  |
| `support_tickets` (+ `ticket_messages`) | Communication | user\_id, subject, status | Φάση 3 |
| `forms` / `form_submissions` | Communication | schema (json), required\_before\_booking / user\_id, answers (json), signed\_at | GDPR και ερωτηματολόγια |
| `activity_log` | Shared | spatie activitylog | Audit όλων των ενεργειών προσωπικού |

### 6.2 Αποφάσεις σχεδιασμού

- **Γιατί ledger και όχι στήλη `remaining_credits`;** Κάθε αλλαγή υπολοίπου έχει αιτία, χρόνο και υπεύθυνο. Η απάντηση στο «γιατί έχω 6 και όχι 7;» βγαίνει από ένα query. Το `balance_after` επιτρέπει γρήγορη ανάγνωση χωρίς SUM.
- **Γιατί `class_sessions` ως πραγματικές γραμμές;** Η χωρητικότητα κλειδώνει πάνω σε μία γραμμή (`SELECT … FOR UPDATE`). Αλλαγές σε ένα μόνο session δεν επηρεάζουν το πρόγραμμα.
- **Snapshot στη συνδρομή:** αν αλλάξει το πακέτο (τιμή, διάρκεια), οι υπάρχουσες συνδρομές δεν επηρεάζονται.
- **Επιλογή συνδρομής προς χρέωση:** αν ο πελάτης έχει πολλές, χρεώνεται αυτή που λήγει νωρίτερα (FEFO), μέσω του `MembershipSelector`.

## 7. Κύριες ροές και καταστάσεις

Κάθε αλλαγή κατάστασης περνά από μία μέθοδο του enum (`canTransitionTo`). Έτσι μια μη επιτρεπτή μετάβαση (π.χ. `cancelled → attended`) πετάει exception αντί να γραφτεί σιωπηλά.

### 7.1 Κράτηση: καταστάσεις

| Από | Προς | Πότε | Επίδραση στα credits |
| --- | --- | --- | --- |
| – | `confirmed` | Επιτυχής κράτηση με συνδρομή | `debit` |
| – | `pending_payment` | Drop-in, αναμονή Stripe | – |
| `pending_payment` | `confirmed` | Webhook `checkout.session.completed` | – |
| `pending_payment` | `expired` | 15' χωρίς πληρωμή (job) | – |
| – | `pending_credits` | Σταθερή θέση χωρίς υπόλοιπο | – |
| `pending_credits` | `confirmed` | Αγορά πακέτου εντός προθεσμίας | `debit` |
| `pending_credits` | `expired` | Πέρασε η προθεσμία | – |
| `confirmed` | `cancelled` | Ακύρωση εντός ορίου | `refund` |
| `confirmed` | `cancelled` (late) | Ακύρωση εκτός ορίου | καμία |
| `confirmed` | `attended` | Check-in ή σημείωση εκπαιδευτή | – |
| `confirmed` | `no_show` | Τέλος session χωρίς check-in (job) ή χειροκίνητα | `refund` μόνο αν `no_show_refunds_credit` |
| `confirmed` | `cancelled` (by staff) | Ακύρωση session από επιχείρηση | `refund` πάντα |

### 7.2 Ροή κράτησης (BookSession)

```php
final class BookSession
{
    public function __construct(
        private BookingRuleChain $rules,
        private CreditLedger $ledger,
        private MembershipSelector $memberships,
    ) {}

    public function handle(BookSessionData $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $session = ClassSession::lockForUpdate()->findOrFail($data->sessionId);
            $membership = $this->memberships->bestFor($data->user, $session);

            $this->rules->assertPasses(new BookingContext($data->user, $session, $membership, now()));

            $booking = Booking::confirm($data->user, $session, $membership, $data->source);
            $this->ledger->debit($membership, $session->service->credit_cost, $booking);

            BookingConfirmed::dispatch($booking); // listeners τρέχουν μετά το commit

            return $booking;
        });
    }
}
```

Το `lockForUpdate` στη γραμμή του session σειριοποιεί τις ταυτόχρονες κρατήσεις για την ίδια ώρα. Έτσι δεν υπάρχει υπερκράτηση όταν δύο πελάτες πατούν μαζί για την τελευταία θέση. Το unique index στα `bookings` είναι δεύτερη άμυνα.

### 7.3 Ακύρωση και λίστα αναμονής

1. `CancelBooking` ελέγχει το όριο. Αν ο πελάτης είναι εντός, κάνει `refund` στο ledger. Αν η κράτηση προέρχεται από σταθερή θέση, αυξάνει το `consecutive_cancellations`.
2. Εκπέμπει `SeatFreed(session)` μετά το commit.
3. Ο listener βάζει στην ουρά το `PromoteFromWaitlist(session)`.
4. Το job κλειδώνει το session. Αν υπάρχει θέση και δεν έχει περάσει το cut-off, παίρνει τον πρώτο `waiting`:
   - **auto\_book:** τρέχει το `BookSession` με source `waitlist`. Αν αποτύχει (π.χ. χωρίς credits), η εγγραφή γίνεται `expired` και δοκιμάζει τον επόμενο.
   - **offer:** η εγγραφή γίνεται `offered` με `offered_until`. Ένα scheduled job λήγει τις ανεκτέλεστες προσφορές και ξανατρέχει το `PromoteFromWaitlist`.
5. Ειδοποίηση στον πελάτη που προωθήθηκε.

### 7.4 Σταθερές θέσεις

1. Ο πελάτης αιτείται, ο διαχειριστής εγκρίνει και η θέση γίνεται `active`.
2. Το `GenerateSessions` (εβδομαδιαίο job) δημιουργεί τα νέα sessions. Για κάθε ενεργή σταθερή θέση του schedule καλεί το `BookSession` με source `fixed_seat`. Αν λείπουν credits, η κράτηση γίνεται `pending_credits`.
3. Οι σταθερές θέσεις παίρνουν προτεραιότητα: δημιουργούνται **πριν** ανοίξει το session για κρατήσεις (`booking_opens_days_ahead`).
4. Η ακύρωση αυξάνει τον μετρητή και η παρουσία τον μηδενίζει. Όταν ο μετρητής φτάσει το όριο: `revoked`, ακύρωση μελλοντικών κρατήσεων με refund, ειδοποίηση. Η μεταφορά (reschedule) δεν μετράει ως ακύρωση.

### 7.5 Credits ledger

| Τύπος | Πρόσημο | Δημιουργείται από |
| --- | --- | --- |
| `purchase` | + | Ενεργοποίηση συνδρομής |
| `debit` | − | Κράτηση |
| `refund` | + | Ακύρωση εντός ορίου, ακύρωση session |
| `expire` | − | Λήξη συνδρομής με υπόλοιπο |
| `adjustment` | ± | Διαχειριστής, με υποχρεωτική αιτιολογία |

Αμετάβλητο (invariant), ελεγμένο με test: `balance_after` τελευταίας κίνησης = SUM(amount) και ποτέ < 0. Για απεριόριστα πακέτα δεν γράφονται `debit`, μόνο εβδομαδιαίο όριο μέσω κανόνα.

## 8. Scheduled jobs, events και ειδοποιήσεις

Όλα τα περιοδικά jobs δηλώνονται στο `routes/console.php` και τρέχουν με ένα cron ανά λεπτό (`schedule:run`). Κάθε job είναι **idempotent** (αν τρέξει δύο φορές, δεν αλλάζει τίποτα) και χρησιμοποιεί `withoutOverlapping()`.

| Job | Συχνότητα | Τι κάνει |
| --- | --- | --- |
| `GenerateSessions` | Καθημερινά 02:00 | Συμπληρώνει sessions έως N εβδομάδες μπροστά, παραλείπει αργίες, δημιουργεί κρατήσεις σταθερών θέσεων |
| `SendBookingReminders` | Κάθε 5' | Υπενθυμίσεις στο παράθυρο `reminder_hours_before`. Αποθηκεύει `reminded_at` για να μη στείλει διπλό |
| `ExpireWaitlistOffers` | Κάθε λεπτό | `offered` με ληγμένο `offered_until` → `expired` → επόμενος |
| `ExpirePendingBookings` | Κάθε λεπτό | `pending_payment` / `pending_credits` που έληξαν → `expired`, απελευθέρωση θέσης |
| `MarkNoShows` | Κάθε 15' | Sessions που τελείωσαν: `confirmed` χωρίς check-in → `no_show` (αν είναι ενεργό το check-in) |
| `ExpireMemberships` | Καθημερινά 00:10 | `active` με `ends_on < σήμερα` → `expired` + κίνηση `expire` |
| `WarnExpiringMemberships` | Καθημερινά 10:00 | Λήξη σε X μέρες ή χαμηλό υπόλοιπο → ειδοποίηση (μία φορά ανά συνδρομή) |
| `RetryMyDataTransmissions` | Κάθε 15' | Αποδείξεις `failed` → νέα αποστολή με backoff |
| `PruneAndBackup` | Καθημερινά 03:00 | `spatie/laravel-backup` σε S3-compatible storage, καθαρισμός παλιών logs |

### 8.1 Ειδοποιήσεις

Κάθε ειδοποίηση είναι μια κλάση Laravel Notification που εκπέμπεται από listener domain event. Τα κανάλια προκύπτουν από τις προτιμήσεις του χρήστη (`via()`). Το SMS είναι custom channel πίσω από interface `SmsSender`, ώστε ο πάροχος να αλλάζει χωρίς αλλαγές στο domain.

| Event | Ειδοποίηση | Παραλήπτης | Κανάλια (default) |
| --- | --- | --- | --- |
| `BookingConfirmed` | Επιβεβαίωση κράτησης (+ .ics) | Πελάτης | Email |
| `BookingCancelled` | Επιβεβαίωση ακύρωσης | Πελάτης (+ διαχειριστής, αν είναι ενεργό) | Email |
| `SessionCancelled` | Ακύρωση μαθήματος από επιχείρηση | Όλοι οι κρατημένοι | Email + SMS |
| `WaitlistPromoted` / `WaitlistOffered` | «Βρήκες θέση» / «Αποδέξου έως …» | Πελάτης | Email + SMS |
| `FixedSeatAtRisk` / `FixedSeatRevoked` | Προειδοποίηση / αφαίρεση σταθερής θέσης | Πελάτης | Email |
| `MembershipExpiring` / `LowBalance` | Ανανέωσε τη συνδρομή | Πελάτης | Email |
| `PaymentSucceeded` | Απόδειξη (PDF) | Πελάτης | Email |
| Reminder (job) | Υπενθύμιση ραντεβού | Πελάτης | Email (SMS προαιρετικά) |

Τα κείμενα ζουν σε αρχεία μετάφρασης (`lang/el`, `lang/en`) με Markdown mail templates. Η αποστολή γίνεται πάντα από queue (database driver, χωρίς Redis), ώστε μια αποτυχία SMTP να μην ρίχνει την κράτηση.

## 9. Πληρωμές και αποδείξεις myDATA

Και τα δύο μπαίνουν πίσω από interfaces του module `Billing`: `PaymentGateway` και `ReceiptIssuer`. Έτσι η αλλαγή παρόχου (π.χ. Stripe → Viva Wallet) είναι νέος adapter, όχι αλλαγή στην επιχειρησιακή λογική.

### 9.1 Online πληρωμή (Stripe Checkout)

1. Ο πελάτης επιλέγει πακέτο ή drop-in. Δημιουργείται `payment` σε `pending` και, αν χρειάζεται, membership σε `pending` ή booking σε `pending_payment`.
2. Redirect στο Stripe Checkout με `metadata.payment_public_id` και `Idempotency-Key`. Τα στοιχεία κάρτας δεν περνούν ποτέ από τον server.
3. Ο webhook `checkout.session.completed` (έλεγχος υπογραφής) καλεί το `MarkPaymentSucceeded`. Αυτό ενεργοποιεί τη συνδρομή ή επιβεβαιώνει την κράτηση και εκπέμπει `PaymentSucceeded`.
4. Ο webhook είναι η μόνη πηγή αλήθειας. Η σελίδα «επιστροφή» από το Stripe μόνο δείχνει κατάσταση. Διπλό webhook αγνοείται (πίνακας `processed_webhook_events` με unique event id).
5. Η επιστροφή χρημάτων γίνεται μέσω Stripe Refund API και εκδίδει πιστωτικό. Αν τα credits έχουν ήδη χρησιμοποιηθεί, η απόφαση είναι του διαχειριστή (πλήρης ή αναλογική).

### 9.2 Αποδείξεις myDATA

| Θέμα | Προδιαγραφή |
| --- | --- |
| Πότε | Αμέσως μετά από κάθε είσπραξη (ακούει `PaymentSucceeded`), μέσω queued job |
| Τύπος | ΑΠΥ για ιδιώτες. ΤΠΥ αν ο πελάτης έχει δώσει ΑΦΜ. Πιστωτικό για επιστροφές |
| Αρίθμηση | Σειρές ανά τύπο. Η επόμενη αρίθμηση παίρνεται μέσα σε συναλλαγή με lock, χωρίς κενά |
| Διαβίβαση | Adapter `ReceiptIssuer`. Προτείνεται **πιστοποιημένος πάροχος με API** (αναλαμβάνει συμμόρφωση, QR, φύλαξη). Εναλλακτικά, απευθείας στο REST API του myDATA (θέλει έλεγχο με λογιστή, βλ. ενότητα 14) |
| Αποθήκευση | MARK, UID, QR URL, ποσά καθαρό/ΦΠΑ/σύνολο, PDF σε private storage |
| Αποτυχία | Κατάσταση `failed`, επανάληψη με backoff, ειδοποίηση διαχειριστή μετά την 3η αποτυχία |
| Ακύρωση | Ποτέ delete. Πιστωτικό που συσχετίζεται με το αρχικό MARK |
| Για τον πελάτη | Σελίδα «Αποδείξεις» με τύπο, ημερομηνία, περιγραφή, ποσό, κατάσταση και λήψη PDF |

Στο MVP οι εισπράξεις καταγράφονται χειροκίνητα και οι αποδείξεις εκδίδονται όπως σήμερα (ταμειακή / πάροχος). Η ενσωμάτωση myDATA έρχεται στη Φάση 2, χωρίς αλλαγή στο μοντέλο.

## 10. Clean code, conventions, testing και CI

Οι κανόνες επιβάλλονται από εργαλεία στο CI, όχι από μνήμη. Ένα PR που σπάει κάποιον δεν κάνει merge.

### 10.1 Κανόνες κώδικα

| Κανόνας | Πρακτικά |
| --- | --- |
| Strict types | `declare(strict_types=1);` σε κάθε αρχείο. Τύποι σε όλα τα ορίσματα και returns |
| Τελικές κλάσεις | Actions, DTOs, Value Objects είναι `final` και `readonly` όπου γίνεται |
| Μικρές μονάδες | Μέθοδοι ≤ 20 γραμμές, κλάσεις με μία ευθύνη. Κανένα «Service» 800 γραμμών |
| Ονόματα του τομέα | Ο κώδικας μιλάει τη γλώσσα του γλωσσαρίου (`FixedSeat`, `CreditLedger`, `PromoteFromWaitlist`). Αγγλικά στον κώδικα, ελληνικά στα κείμενα UI |
| Χωρίς μαγικές τιμές | Enums για καταστάσεις, settings για επιχειρησιακούς αριθμούς |
| Value objects | `Credits`, `Money` αντί γυμνών float. Απαγορεύεται float για χρήματα |
| Εξαιρέσεις με νόημα | `BookingRejected::because('booking.full')`. Το UI τις μεταφράζει σε μήνυμα χρήστη |
| Καμία λογική σε models | Τα Eloquent models έχουν relations, casts, scopes και μικρούς domain helpers (`isCancellableAt()`). Οι ροές είναι στα Actions |
| Καμία λογική στο UI | Filament και Livewire καλούν Actions, δεν κάνουν queries με επιχειρησιακούς κανόνες |
| Οι συναλλαγές στα όρια | `DB::transaction` μόνο μέσα σε Actions. Events με `ShouldDispatchAfterCommit` |
| N+1 | `Model::preventLazyLoading()` σε local και test |
| Σχόλια | Εξηγούν το «γιατί», όχι το «τι». Η τεκμηρίωση αρχιτεκτονικών αποφάσεων γίνεται σε ADR (`docs/adr/0001-credits-ledger.md`) |

### 10.2 Εργαλεία

| Εργαλείο | Ρόλος |
| --- | --- |
| Laravel Pint (preset `laravel` + strict rules) | Formatting (PSR-12) |
| Larastan / PHPStan level 8 | Στατική ανάλυση |
| Rector | Αυτόματες αναβαθμίσεις PHP/Laravel |
| Pest (+ arch plugin) | Tests και κανόνες αρχιτεκτονικής |
| Laravel Telescope (μόνο local) | Debugging queries, jobs, mail |
| Composer audit | Ευπάθειες εξαρτήσεων |

### 10.3 Στρατηγική testing

| Είδος | Τι καλύπτει | Στόχος |
| --- | --- | --- |
| Unit | Κάθε `BookingRule`, value objects, transitions enums, `MembershipSelector` | Κάθε κλάδος κανόνα |
| Feature | Κάθε Action με πραγματική MySQL (όχι SQLite: διαφέρει στα locks) | Αποδοχή και απόρριψη ανά ροή |
| Σενάρια χρόνου | Ορια ακύρωσης (179' / 180' / 181'), λήξεις, αλλαγή θερινής ώρας | `travelTo()` |
| Ταυτοχρονισμός | Δύο κρατήσεις για την τελευταία θέση → μία επιτυχία | Test με δύο connections |
| Ledger invariant | Τυχαίες ακολουθίες κράτηση/ακύρωση → υπόλοιπο ≥ 0 και = SUM | Property-style test |
| Livewire / Filament | Βασικές οθόνες φορτώνουν, κουμπιά καλούν το σωστό Action | Smoke tests |
| Εξωτερικά | Stripe, myDATA, SMS με fakes. Ένα contract test ανά adapter σε sandbox, χειροκίνητα |  |

Στόχος coverage: ≥ 90% στα modules `Booking` και `Membership`. Στο UI δεν υπάρχει στόχος.

### 10.4 Git και CI (GitHub Actions)

- Trunk-based με μικρά PRs. Conventional Commits (`feat(booking): …`). CHANGELOG αυτόματα.
- Pipeline: `composer install` → Pint (check) → PHPStan → Pest (με MySQL service) → build assets → deploy στο main.
- Migrations μόνο προς τα εμπρός (expand/contract). Καμία αλλαγή σε migration που έχει ήδη τρέξει.
- Seeders με ρεαλιστικά demo δεδομένα (3 υπηρεσίες, 1 εβδομαδιαίο πρόγραμμα, 20 πελάτες) για local και staging.

## 11. Μη λειτουργικές απαιτήσεις

| Περιοχή | Απαίτηση |
| --- | --- |
| **Ασφάλεια** | HTTPS παντού (HSTS). Bcrypt/Argon2 κωδικοί. CSRF (default Laravel). Rate limiting σε login (5/λεπτό), εγγραφή και κρατήσεις. Policies σε κάθε πόρο. Κλειδιά μόνο σε `.env`, ποτέ στο git. 2FA για προσωπικό. Security headers (CSP, X-Frame-Options) |
| **IDOR** | Καμία εκτεθειμένη αύξουσα τιμή: `public_id` (ULID) σε URLs. Το check-in token είναι τυχαίο και επανεκδόσιμο |
| **GDPR** | Καταγραφή συναίνεσης με έκδοση και ώρα. Εξαγωγή δεδομένων χρήστη (JSON/ZIP). Διαγραφή = ανωνυμοποίηση, με διατήρηση φορολογικών στοιχείων όσο ορίζει ο νόμος. Πολιτική απορρήτου και όροι γραμμένοι για τη δική σου επιχείρηση. Τα ευαίσθητα πεδία ερωτηματολογίων (υγεία) κρυπτογραφούνται (`encrypted` cast) |
| **Απόδοση** | Σελίδα κράτησης < 500 ms server time. Στόχος: 500 πελάτες, 100 sessions/εβδομάδα, αιχμή 50 ταυτόχρονων χρηστών όταν ανοίγουν οι κρατήσεις. Indexes από την ενότητα 6. Cache για settings και κατάλογο. OPcache |
| **Διαθεσιμότητα** | Στόχος 99,5% μηνιαίως. Uptime monitor (δωρεάν UptimeRobot ή Better Stack). Zero-downtime deploys (symlink releases) |
| **Backups** | Καθημερινό dump βάσης και αρχείων σε εξωτερικό S3-compatible storage. Διατήρηση 30 ημερήσιων + 12 μηνιαίων. **Δοκιμή επαναφοράς μία φορά το τρίμηνο** |
| **Observability** | Error tracking (Sentry free tier ή Flare). Structured logs. Ειδοποίηση για failed jobs |
| **Προσβασιμότητα** | WCAG 2.1 AA στην περιοχή πελάτη: αντίθεση, πλοήγηση με πληκτρολόγιο, labels. Οι καταστάσεις δεν δηλώνονται μόνο με χρώμα (π.χ. «Γεμάτο» ως κείμενο) |
| **i18n** | Ελληνικά (default) και Αγγλικά. Πολυγλωσσικά πεδία καταλόγου με `spatie/laravel-translatable` |
| **PWA / mobile** | Responsive από 360 px. Manifest + service worker για εγκατάσταση. Web push στη Φάση 3 |

## 12. Deployment και hosting

Ένας μικρός VPS αρκεί για χρόνια. Το κόστος είναι σταθερό, περίπου 8–10€/μήνα με τα backups. Το shared hosting είναι εφικτό, αλλά χωρίς μόνιμο queue worker οι ειδοποιήσεις θα φεύγουν με καθυστέρηση έως 1 λεπτό (`queue:work --stop-when-empty` από cron).

| Στοιχείο | Επιλογή | Περίπου κόστος |
| --- | --- | --- |
| Server | VPS 2 vCPU / 4 GB (π.χ. Hetzner CX23, Γερμανία/Φινλανδία) | \~6€/μήνα + ΦΠΑ |
| Στοίβα | Ubuntu LTS, Nginx, PHP 8.3-FPM, MySQL 8, Supervisor (queue worker), cron | – |
| Provisioning | Χειροκίνητα με script ή Laravel Forge για ευκολία | Forge με συνδρομή (προαιρετικό) |
| Deploy | GitHub Actions → SSH → Deployer (zero-downtime releases, `migrate --force`, `optimize`) | Δωρεάν |
| HTTPS | Let's Encrypt (certbot) | Δωρεάν |
| Email | Brevo ή Amazon SES (SPF, DKIM, DMARC στο domain) | Δωρεάν έως μικρά αποστολές / ελάχιστα λεπτά |
| SMS | Ελληνικός πάροχος με αποστολέα με όνομα | Ανά SMS |
| Backups | S3-compatible object storage | Λίγα cents/μήνα |
| Domain | .gr ή .com | \~10–15€/χρόνο |

### Περιβάλλοντα

| Περιβάλλον | Που | Σημειώσεις |
| --- | --- | --- |
| Local | Laravel Sail (Docker) ή Herd | Mailpit για email, Stripe CLI για webhooks |
| Staging | Άλλη βάση και subdomain στον ίδιο VPS | Stripe test mode, myDATA dev, ανωνυμοποιημένα δεδομένα |
| Production | VPS | Deploy μόνο από main με πράσινο CI |

## 13. Roadmap

&#91;embedded content: roadmap · 3 φάσεις, 2 πύλες\]

Κάθε φάση παραδίδει κάτι που χρησιμοποιείται μόνο του. Η αντιστοίχιση με τις απαιτήσεις είναι στη στήλη «Φάση» της ενότητας 3. Η σειρά μέσα στο MVP: Identity → Catalog/Scheduling → Membership (ledger) → Booking → Waitlist → ειδοποιήσεις → admin dashboard. Το ledger προηγείται της κράτησης, γιατί η κράτηση το χρειάζεται για να χρεώσει.

## 14. Ανοιχτά ερωτήματα και αποφάσεις

Αυτές οι αποφάσεις αλλάζουν το εύρος ή το μοντέλο. Καλό είναι να κλείσουν πριν την Φάση 2.

- [ ] **myDATA:** πάροχος ηλεκτρονικής τιμολόγησης ή απευθείας διαβίβαση από δικό σου λογισμικό; Επιβεβαίωσε με λογιστή τι ισχύει σήμερα για ΑΠΥ και για τη σύνδεση POS–ταμειακής
- [ ] **Πάροχος πληρωμών:** Stripe ή ελληνικός (Viva Wallet), με βάση προμήθειες και σύνδεση με POS
- [ ] **Λίστα αναμονής:** `auto_book` ή `offer` ως default;
- [ ] **No-show:** θα γίνεται check-in στην είσοδο; Αν όχι, το `MarkNoShows` μένει ανενεργό και οι κρατήσεις κλείνουν ως `attended`
- [ ] **Μεταφορά δεδομένων:** παίρνεις export πελατών και υπολοίπων από το σημερινό σύστημα; Αν ναι, χρειάζεται import command στην Φάση 1
- [ ] **Απεριόριστα πακέτα:** χρειάζονται; (μηνιαία με όριο ανά εβδομάδα)
- [ ] **Ατομικά ραντεβού (personal):** μέσα στο εύρος ή όλα ως ομαδικά με χωρητικότητα 1;
- [ ] **Κώδικας και κείμενα:** όροι χρήσης και πολιτική απορρήτου γράφονται για τη δική σου επιχείρηση (όχι αντιγραφή από το Bookup)
- [ ] **Ονομασία και domain** της εφαρμογής

### Αποφάσεις που έχουν παρθεί

| Απόφαση | Εναλλακτική που απορρίφθηκε | Λόγος |
| --- | --- | --- |
| Laravel + Filament + MySQL | Java/Spring, έτοιμο script SaaSAppoint | Χαμηλό hosting, έτοιμο admin, σύγχρονος κώδικας |
| Modular monolith | Microservices | Μία επιχείρηση, ένας developer |
| PWA | Native app | Χωρίς App Store, ένας κώδικας |
| Credits ledger | Στήλη υπολοίπου | Ιχνηλασιμότητα και διώρθωση λαθών |
| Database queue | Redis | Λιγότερη υποδομή. Αλλαγή σε Redis με μία ρύθμιση αν χρειαστεί |
