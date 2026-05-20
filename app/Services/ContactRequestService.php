<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\ContactRequestAcceptedNotification;
use App\Notifications\ContactRequestReceivedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactRequestService
{
    public function create(User $from, User $to, ?string $requestMessage): ContactRequest
    {
        if ((int) $from->id === (int) $to->id) {
            throw ValidationException::withMessages([
                'candidateUuid' => ['You cannot request your own contact number.'],
            ]);
        }

        if (!$to->hasRole('candidate')) {
            throw ValidationException::withMessages([
                'candidateUuid' => ['The selected user is not a candidate.'],
            ]);
        }

        $pendingExists = ContactRequest::query()
            ->where('from_user_id', $from->id)
            ->where('to_user_id', $to->id)
            ->where('request_status', 'pending')
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'candidateUuid' => ['A pending contact request already exists for this candidate.'],
            ]);
        }

        return DB::transaction(function () use ($from, $to, $requestMessage): ContactRequest {
            /** @var ContactRequest $row */
            $row = ContactRequest::query()->create([
                'from_user_id' => $from->id,
                'to_user_id' => $to->id,
                'request_message' => $requestMessage,
                'request_status' => 'pending',
            ]);

            $to->notify(new ContactRequestReceivedNotification($row));

            return $row;
        });
    }

    public function respond(ContactRequest $request, string $decision, ?string $responseMessage): ContactRequest
    {
        if ($request->request_status !== 'pending') {
            throw ValidationException::withMessages([
                'decision' => ['This contact request is no longer pending.'],
            ]);
        }

        return DB::transaction(function () use ($request, $decision, $responseMessage): ContactRequest {
            $request->request_status = $decision;
            $request->responded_at = now();
            $request->response_message = $responseMessage;
            $request->save();

            if ($decision === 'accepted') {
                $request->loadMissing('fromUser');
                $from = $request->fromUser;

                if ($from instanceof User) {
                    $from->notify(new ContactRequestAcceptedNotification($request));
                }
            }

            return $request->fresh() ?? $request;
        });
    }
}
