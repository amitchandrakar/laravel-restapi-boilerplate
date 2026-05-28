<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdminSettingsType;
use App\Models\NotificationSetting;
use App\Services\Concerns\AbstractSingletonSettingsService;
use Illuminate\Database\Eloquent\Model;

class NotificationSettingsService extends AbstractSingletonSettingsService
{
    protected function modelClass(): string
    {
        return NotificationSetting::class;
    }

    protected function settingsType(): AdminSettingsType
    {
        return AdminSettingsType::Notification;
    }

    /**
     * @return array<string, string>
     */
    protected function columnMap(): array
    {
        return [
            'emailEnabled' => 'email_enabled',
            'mailMailer' => 'mail_mailer',
            'mailHost' => 'mail_host',
            'mailPort' => 'mail_port',
            'mailUsername' => 'mail_username',
            'mailPassword' => 'mail_password',
            'mailEncryption' => 'mail_encryption',
            'mailFromAddress' => 'mail_from_address',
            'mailFromName' => 'mail_from_name',
            'mailReplyToAddress' => 'mail_reply_to_address',
            'mailReplyToName' => 'mail_reply_to_name',
            'smsEnabled' => 'sms_enabled',
            'twilioAccountSid' => 'twilio_account_sid',
            'twilioAuthToken' => 'twilio_auth_token',
            'twilioFromNumber' => 'twilio_from_number',
            'pushEnabled' => 'push_enabled',
            'fcmServerKey' => 'fcm_server_key',
            'fcmSenderId' => 'fcm_sender_id',
            'fcmClientKey' => 'fcm_client_key',
            'fcmTopic' => 'fcm_topic',
        ];
    }

    /**
     * @return list<string>
     */
    protected function secretColumns(): array
    {
        return ['mail_password', 'twilio_auth_token', 'fcm_server_key', 'fcm_client_key'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toApiArray(Model $record, bool $maskSecrets): array
    {
        /** @var NotificationSetting $record */
        return array_merge(
            [
                'emailEnabled' => $record->email_enabled,
                'mailMailer' => $record->mail_mailer,
                'mailHost' => $record->mail_host,
                'mailPort' => $record->mail_port,
                'mailUsername' => $record->mail_username,
                'mailEncryption' => $record->mail_encryption,
                'mailFromAddress' => $record->mail_from_address,
                'mailFromName' => $record->mail_from_name,
                'mailReplyToAddress' => $record->mail_reply_to_address,
                'mailReplyToName' => $record->mail_reply_to_name,
                'smsEnabled' => $record->sms_enabled,
                'twilioAccountSid' => $record->twilio_account_sid,
                'twilioFromNumber' => $record->twilio_from_number,
                'pushEnabled' => $record->push_enabled,
                'fcmSenderId' => $record->fcm_sender_id,
                'fcmTopic' => $record->fcm_topic,
            ],
            $maskSecrets
                ? $this->secretFlags($record)
                : [
                    'mailPassword' => $record->mail_password,
                    'twilioAuthToken' => $record->twilio_auth_token,
                    'fcmServerKey' => $record->fcm_server_key,
                    'fcmClientKey' => $record->fcm_client_key,
                ]
        );
    }
}
