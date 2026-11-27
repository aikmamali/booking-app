<?php

declare(strict_types=1);

namespace Deployer;

/*
 * Zero-downtime deploys with symlinked releases (spec section 12).
 *
 *   vendor/bin/dep deploy production
 *
 * Host, user and path come from the environment (GitHub Actions secrets or your shell):
 * DEPLOY_HOST, DEPLOY_USER, DEPLOY_PATH. The server keeps `.env` and `storage/` in
 * {{deploy_path}}/shared; see docs/DEPLOYMENT.md.
 */

require 'recipe/laravel.php';

set('application', 'booking-app');
set('repository', getenv('DEPLOY_REPOSITORY') ?: 'git@github.com:aikmamali/booking-app.git');
set('branch', 'main');
set('keep_releases', 5);
set('writable_mode', 'chmod');

host('production')
    ->setHostname((string) getenv('DEPLOY_HOST'))
    ->setRemoteUser(getenv('DEPLOY_USER') ?: 'deploy')
    ->setDeployPath(getenv('DEPLOY_PATH') ?: '/var/www/booking-app');

desc('Build front-end assets on the server');
task('deploy:assets', function (): void {
    run('cd {{release_path}} && npm ci --no-audit --no-fund && npm run build && rm -rf node_modules');
});

desc('Cache Filament components and icons');
task('artisan:filament:optimize', artisan('filament:optimize'));

desc('Let the queue worker pick up the new code');
task('artisan:queue:restart', artisan('queue:restart'));

// recipe/laravel runs: vendors, storage:link, config/route/view/event cache, migrate.
after('deploy:vendors', 'deploy:assets');
after('artisan:migrate', 'artisan:filament:optimize');
after('deploy:symlink', 'artisan:queue:restart');
after('deploy:failed', 'deploy:unlock');
