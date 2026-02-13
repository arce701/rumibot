<?php

test('backup destination disk is s3', function () {
    expect(config('backup.backup.destination.disks'))->toContain('s3');
});

test('backup source includes pgsql database', function () {
    expect(config('backup.backup.source.databases'))->toContain('pgsql');
});

test('backup source includes .env file', function () {
    expect(config('backup.backup.source.files.include'))->toContain(base_path('.env'));
});

test('backup source excludes vendor, node_modules, and storage', function () {
    $excludes = config('backup.backup.source.files.exclude');

    expect($excludes)->toContain(base_path('vendor'));
    expect($excludes)->toContain(base_path('node_modules'));
    expect($excludes)->toContain(base_path('storage'));
});

test('backup cleanup keeps all backups for 7 days', function () {
    expect(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(7);
});

test('backup cleanup keeps daily backups for 30 days', function () {
    expect(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(30);
});

test('backup cleanup keeps weekly backups for 8 weeks', function () {
    expect(config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'))->toBe(8);
});

test('backup cleanup keeps monthly backups for 4 months', function () {
    expect(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(4);
});

test('backup schedule commands are registered', function () {
    $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();

    $commands = collect($events)->map(fn ($event) => $event->command ?? '')->implode(' ');

    expect($commands)->toContain('backup:clean');
    expect($commands)->toContain('backup:run');
});
