<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateNotificationSettingsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'emailEnabled' => ['sometimes', 'boolean'],
            'mailMailer' => ['sometimes', 'nullable', 'string', 'max:64'],
            'mailHost' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mailPort' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'mailUsername' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mailPassword' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'mailEncryption' => ['sometimes', 'nullable', 'string', 'max:32'],
            'mailFromAddress' => ['sometimes', 'nullable', 'email', 'max:255'],
            'mailFromName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mailReplyToAddress' => ['sometimes', 'nullable', 'email', 'max:255'],
            'mailReplyToName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'smsEnabled' => ['sometimes', 'boolean'],
            'twilioAccountSid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'twilioAuthToken' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'twilioFromNumber' => ['sometimes', 'nullable', 'string', 'max:64'],
            'pushEnabled' => ['sometimes', 'boolean'],
            'fcmServerKey' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'fcmSenderId' => ['sometimes', 'nullable', 'string', 'max:128'],
            'fcmClientKey' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'fcmTopic' => ['sometimes', 'nullable', 'string', 'max:128'],
        ];
    }
}
