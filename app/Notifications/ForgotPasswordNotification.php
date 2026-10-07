<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Support\MailDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $resetHash) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (MailDelivery::shouldSendMail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $baseUrl = (string) config('app.frontend_reset_password_url', '');
        $baseUrl = $baseUrl !== '' ? $baseUrl : (string) config('app.url') . '/reset-password';
        $resetUrl = rtrim($baseUrl, '/') . '?hash=' . urlencode($this->resetHash);

        return (new MailMessage())->subject('Reset your password')->view('emails.forgot_password', [
            'user' => $notifiable,
            'resetUrl' => $resetUrl,
            'hash' => $this->resetHash,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'forgot_password',
            'message' => 'Password reset requested.',
        ];
    }
}
