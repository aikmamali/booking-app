# Εγκατάσταση σε παραγωγή

Οδηγός για τον πρώτο server και για κάθε επόμενη έκδοση (SPEC ενότητα 12). Ένας μικρός VPS αρκεί: 2 vCPU, 4 GB RAM (π.χ. Hetzner CX23), Ubuntu LTS.

## 1. Λογισμικό στον server

```bash
sudo apt update && sudo apt install -y nginx mysql-server supervisor git unzip \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-gd php8.3-bcmath
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash - && sudo apt install -y nodejs
```

Το `mysql-client` (μαζί με το `mysql-server`) χρειάζεται και για τα backups (`mysqldump`).

Δημιούργησε χρήστη `deploy` με πρόσβαση SSH μόνο με κλειδί, βάση δεδομένων και χρήστη MySQL για την εφαρμογή.

## 2. Φάκελοι και `.env`

```bash
sudo mkdir -p /var/www/booking-app/shared/storage && sudo chown -R deploy:www-data /var/www/booking-app
```

Βάλε στο `/var/www/booking-app/shared/.env` ένα αντίγραφο του `.env.example` και άλλαξε τουλάχιστον:

| Μεταβλητή | Τιμή |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://το-domain-σου.gr` |
| `APP_KEY` | `php artisan key:generate --show` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Η βάση του server (`DB_HOST=127.0.0.1`) |
| `MAIL_*` | Στοιχεία SMTP του Brevo ή του Amazon SES (με SPF, DKIM, DMARC στο domain) |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Η διεύθυνση και το όνομα αποστολέα |
| `AWS_*`, `BACKUP_DISK=s3` | Το S3-compatible bucket των backups (π.χ. Hetzner Object Storage, `AWS_ENDPOINT` και `AWS_USE_PATH_STYLE_ENDPOINT=true`) |
| `BACKUP_NOTIFICATION_EMAIL` | Ποιος ειδοποιείται αν αποτύχει backup |
| `SENTRY_LARAVEL_DSN` | Από το δωρεάν πλάνο του Sentry (error tracking) |
| `ALERT_EMAIL` | Ποιος ειδοποιείται όταν αποτύχει job της ουράς (π.χ. αποστολή email) |
| `SESSION_SECURE_COOKIE` | `true` |

Τα κλειδιά μένουν μόνο στο `.env` του server, ποτέ στο git.

## 3. Nginx και HTTPS

Το document root είναι το `/var/www/booking-app/current/public`, με το τυπικό config του Laravel για PHP-FPM. Το HTTPS μπαίνει με Let's Encrypt:

```bash
sudo apt install -y certbot python3-certbot-nginx && sudo certbot --nginx -d το-domain-σου.gr
```

## 4. Ουρά και χρονοπρογραμματισμένες εργασίες

Τα email φεύγουν από ουρά (database driver), οπότε χρειάζεται ένας worker που τρέχει πάντα. Αρχείο `/etc/supervisor/conf.d/booking-worker.conf`:

```ini
[program:booking-worker]
command=php /var/www/booking-app/current/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=deploy
autostart=true
autorestart=true
stopwaitsecs=3600
stdout_logfile=/var/www/booking-app/shared/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

Ένα cron κάθε λεπτό τρέχει όλες τις περιοδικές εργασίες: παραγωγή μαθημάτων, υπενθυμίσεις, λίστα αναμονής, λήξεις, backups. Πρόσθεσέ το με `crontab -e` ως χρήστης `deploy`:

```cron
* * * * * cd /var/www/booking-app/current && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Deploy

Τα deploys γίνονται με το Deployer (`deploy.php`), με zero downtime: κάθε έκδοση πάει σε νέο φάκελο και το `current` αλλάζει στο τέλος. Τρέχουν αυτόματα composer, npm build, migrations και restart του worker.

- **Αυτόματα:** το GitHub Actions (`.github/workflows/deploy.yml`) κάνει deploy όταν περάσει το CI στο `main`. Θέλει τα secrets `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH` και `DEPLOY_SSH_KEY` στο repo, και το public κλειδί στο `~/.ssh/authorized_keys` του χρήστη `deploy`. Χωρίς αυτά, το deploy παραλείπεται.
- **Χειροκίνητα:** `DEPLOY_HOST=... vendor/bin/dep deploy production`.

Μετά το πρώτο deploy:

```bash
cd /var/www/booking-app/current
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder   # μόνο στο staging, ποτέ στην παραγωγή
php artisan identity:create-admin email@το-domain-σου.gr Όνομα Επώνυμο
```

Η δεύτερη εντολή στέλνει πρόσκληση στον πρώτο διαχειριστή για να ορίσει κωδικό.

Στην πρώτη σύνδεση στο `/admin` ο διαχειριστής ενεργοποιεί τον έλεγχο δύο βημάτων (2FA) με μια εφαρμογή στο κινητό (π.χ. Google Authenticator) και φυλάει τους κωδικούς ανάκτησης. Αν χαθούν και το κινητό και οι κωδικοί, ένας άλλος διαχειριστής κάνει «Επαναφορά ελέγχου δύο βημάτων» από τη λίστα χρηστών. Αν δεν υπάρχει άλλος διαχειριστής, στον server:

```bash
php artisan identity:reset-two-factor email@το-domain-σου.gr
```

## 6. Backups

Κάθε βράδυ στις 03:00 (ώρα Ελλάδας) καθαρίζονται τα παλιά backups και στις 03:10 γίνεται νέο: dump της βάσης και οι εικόνες που ανέβηκαν. Κρατιούνται 30 ημερήσια και 12 μηνιαία. Στις 09:00 το `backup:monitor` ελέγχει ότι υπάρχει πρόσφατο backup και, αν όχι, στέλνει email.

**Δοκιμή επαναφοράς μία φορά το τρίμηνο** (SPEC 11): κατέβασε το πιο πρόσφατο zip από το bucket, φόρτωσε το `.sql` σε μια βάση staging και άνοιξε την εφαρμογή. Ένα backup που δεν έχει δοκιμαστεί δεν μετράει.

## 7. Παρακολούθηση

- **Uptime:** στο UptimeRobot ή στο Better Stack, έλεγχος του `https://το-domain-σου.gr/up` κάθε 5 λεπτά (απαντά 200 όταν η εφαρμογή λειτουργεί).
- **Σφάλματα:** το Sentry καταγράφει κάθε exception μόλις οριστεί το `SENTRY_LARAVEL_DSN`.
- **Αποτυχημένα jobs:** γράφονται στο log και στο Sentry, και πάνε με email στο `ALERT_EMAIL`. Τα βλέπεις με `php artisan queue:failed` και τα ξανατρέχεις με `php artisan queue:retry all`.

## 8. Staging

Staging στον ίδιο server, σε subdomain με άλλη βάση και άλλο `deploy_path`. Χρησιμοποίησε ανωνυμοποιημένα δεδομένα και `MAIL_MAILER=log`, ώστε να μη φύγουν email σε πραγματικούς πελάτες.
