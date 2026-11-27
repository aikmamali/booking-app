<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Monitoring\AlertOnFailedJob;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

describe('security headers', function (): void {
    it('sends them on customer and admin pages', function (string $url): void {
        $this->get($url)
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security')
            ->assertHeader('Content-Security-Policy');
    })->with(['/app/login', '/admin/login']);

    it('adds HSTS only in production over HTTPS', function (): void {
        app()->detectEnvironment(fn (): string => 'production');

        $this->get('https://localhost/app/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    });
});

describe('PWA', function (): void {
    it('ships a manifest whose icons exist', function (): void {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        expect($manifest['start_url'])->toBe('/app')
            ->and($manifest['display'])->toBe('standalone')
            ->and($manifest['lang'])->toBe('el');

        foreach ($manifest['icons'] as $icon) {
            expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
            [$w, $h] = getimagesize(public_path(ltrim($icon['src'], '/'))) ?: [0, 0];
            expect("{$w}x{$h}")->toBe($icon['sizes']);
        }
    });

    it('registers the service worker and links the manifest on customer pages', function (): void {
        $this->get('/app/login')
            ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
            ->assertSee("navigator.serviceWorker.register('/sw.js')", false);

        expect(public_path('sw.js'))->toBeFile()
            ->and((string) file_get_contents(public_path('sw.js')))->toContain('/offline.html')
            ->and(public_path('offline.html'))->toBeFile();
    });
});

describe('failed jobs', function (): void {
    function failedJob(): JobFailed
    {
        $job = Mockery::mock(Job::class);
        $job->allows('resolveName')->andReturn('App\\Jobs\\SendSomething');

        return new JobFailed('database', $job, new RuntimeException('SMTP down'));
    }

    it('mails the alert to ALERT_EMAIL', function (): void {
        config(['monitoring.alert_email' => 'ops@studio.gr', 'mail.default' => 'array']);

        app(AlertOnFailedJob::class)->handle(failedJob());

        $sent = app('mailer')->getSymfonyTransport()->messages();
        expect($sent)->toHaveCount(1)
            ->and($sent->first()->getEnvelope()->getRecipients()[0]->getAddress())->toBe('ops@studio.gr')
            ->and($sent->first()->getOriginalMessage()->getSubject())->toContain('Failed job: SendSomething');
    });

    it('stays quiet without ALERT_EMAIL but still logs', function (): void {
        Log::spy();
        config(['monitoring.alert_email' => null, 'mail.default' => 'array']);

        app(AlertOnFailedJob::class)->handle(failedJob());

        Log::shouldHaveReceived('critical')->once();
        expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(0);
    });

    it('is wired to the queue', function (): void {
        expect(app('events')->hasListeners(JobFailed::class))->toBeTrue();
    });
});

it('backs up every night and keeps 30 daily and 12 monthly copies', function (): void {
    $events = collect(app(Schedule::class)->events())->keyBy(fn ($e): string => (string) $e->description);

    expect($events['backup-clean']->expression)->toBe('0 3 * * *')
        ->and($events['backup-run']->expression)->toBe('10 3 * * *')
        ->and($events['backup-run']->timezone)->toBe('Europe/Athens')
        ->and($events['backup-monitor']->expression)->toBe('0 9 * * *')
        ->and(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(30)
        ->and(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(12)
        ->and(config('backup.backup.source.files.include'))->toBe([storage_path('app/public')]);
});

it('renders the customer area without horizontal scrolling markup issues', function (): void {
    $this->actingAs(User::factory()->customer()->create())->get('/app')
        ->assertOk()
        ->assertSee('width=device-width, initial-scale=1', false);
});

it('draws staff avatars locally instead of sending names to a third party', function (): void {
    $admin = User::factory()->admin()->create(['first_name' => 'Ελένη', 'last_name' => 'Κώστα']);

    $url = Filament::getUserAvatarUrl($admin);

    expect($url)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($url, strlen('data:image/svg+xml;base64,')), true))->toContain('ΕΚ');

    $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('ui-avatars.com');
});
