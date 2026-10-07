<?php

declare(strict_types=1);

use App\Models\NotificationSetting;
use App\Notifications\ForgotPasswordNotification;
use App\Notifications\WelcomeEmailNotification;
use App\Services\Settings\NotificationConfigResolver;
use App\Support\MailDelivery;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

it('uses env mail only when APP_ENV is local', function (): void {
    $this->app['env'] = 'local';
    expect(MailDelivery::usesEnvMail())->toBeTrue();
    expect(MailDelivery::shouldSendMail())->toBeTrue();

    $this->app['env'] = 'staging';
    expect(MailDelivery::usesEnvMail())->toBeFalse();

    $this->app['env'] = 'production';
    expect(MailDelivery::usesEnvMail())->toBeFalse();
});

it('skips mail channel in non-local when email is disabled', function (): void {
    $this->app['env'] = 'production';

    NotificationSetting::instance()->update([
        'email_enabled' => false,
        'mail_host' => 'smtp.example.com',
    ]);

    expect(MailDelivery::shouldSendMail())->toBeFalse();

    Notification::fake();
    $user = $this->createUserWithRole('candidate', 'mail-disabled@example.com');
    $user->notify(new WelcomeEmailNotification());

    Notification::assertSentTo($user, WelcomeEmailNotification::class, function ($notification, array $channels): bool {
        return in_array('database', $channels, true) && !in_array('mail', $channels, true);
    });
});

it('sends mail in local even when email_enabled is false', function (): void {
    $this->app['env'] = 'local';

    NotificationSetting::instance()->update([
        'email_enabled' => false,
        'mail_host' => null,
    ]);

    expect(MailDelivery::shouldSendMail())->toBeTrue();

    Notification::fake();
    $user = $this->createUserWithRole('candidate', 'mail-local@example.com');
    $user->notify(new ForgotPasswordNotification('reset-hash-test'));

    Notification::assertSentTo($user, ForgotPasswordNotification::class, function (
        $notification,
        array $channels
    ): bool {
        return in_array('mail', $channels, true) && in_array('database', $channels, true);
    });
});

it('sends mail in staging when email enabled and host is set', function (): void {
    $this->app['env'] = 'staging';

    NotificationSetting::instance()->update([
        'email_enabled' => true,
        'mail_host' => 'smtp.staging.example.com',
        'mail_port' => 587,
        'mail_mailer' => 'smtp',
        'mail_username' => 'staging-user',
        'mail_from_address' => 'noreply@staging.example.com',
        'mail_from_name' => 'Staging',
    ]);

    expect(MailDelivery::shouldSendMail())->toBeTrue();

    Notification::fake();
    $user = $this->createUserWithRole('candidate', 'mail-staging@example.com');
    $user->notify(new WelcomeEmailNotification());

    Notification::assertSentTo($user, WelcomeEmailNotification::class, function ($notification, array $channels): bool {
        return in_array('mail', $channels, true);
    });
});

it('skips mail when enabled but SMTP host is empty', function (): void {
    $this->app['env'] = 'production';

    NotificationSetting::instance()->update([
        'email_enabled' => true,
        'mail_host' => null,
    ]);

    expect(MailDelivery::shouldSendMail())->toBeFalse();

    Notification::fake();
    $user = $this->createUserWithRole('candidate', 'mail-incomplete@example.com');
    $user->notify(new WelcomeEmailNotification());

    Notification::assertSentTo($user, WelcomeEmailNotification::class, function ($notification, array $channels): bool {
        return !in_array('mail', $channels, true);
    });
});

it('applies DB smtp config in non-local when email is enabled', function (): void {
    $this->app['env'] = 'production';

    Config::set('mail.default', 'log');
    Config::set('mail.mailers.smtp.host', 'from-env.example.com');

    NotificationSetting::instance()->update([
        'email_enabled' => true,
        'mail_mailer' => 'smtp',
        'mail_host' => 'db-smtp.example.com',
        'mail_port' => 465,
        'mail_username' => 'db-user',
        'mail_encryption' => 'ssl',
        'mail_from_address' => 'from@db.example.com',
        'mail_from_name' => 'DB Mail',
    ]);

    app(NotificationConfigResolver::class)->apply();

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('db-smtp.example.com');
    expect((int) config('mail.mailers.smtp.port'))->toBe(465);
    expect(config('mail.from.address'))->toBe('from@db.example.com');
});

it('forces array mailer in non-local when email is disabled so env smtp is unused', function (): void {
    $this->app['env'] = 'staging';

    Config::set('mail.default', 'smtp');
    Config::set('mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io');

    NotificationSetting::instance()->update([
        'email_enabled' => false,
        'mail_host' => 'should-not-apply.example.com',
    ]);

    app(NotificationConfigResolver::class)->apply();

    expect(config('mail.default'))->toBe('array');
    expect(config('mail.mailers.smtp.host'))->toBe('sandbox.smtp.mailtrap.io');
});

it('does not override env mail config when APP_ENV is local', function (): void {
    $this->app['env'] = 'local';

    Config::set('mail.default', 'smtp');
    Config::set('mail.mailers.smtp.host', 'sandbox.smtp.mailtrap.io');

    NotificationSetting::instance()->update([
        'email_enabled' => true,
        'mail_host' => 'db-should-not-win.example.com',
    ]);

    app(NotificationConfigResolver::class)->apply();

    expect(config('mail.mailers.smtp.host'))->toBe('sandbox.smtp.mailtrap.io');
});
