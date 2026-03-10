<?php

declare(strict_types=1);


namespace App\Models;

class EmailSentLog extends BaseModel
{
    protected $table = 'email_sent_logs';

    public $timestamps = false;

    protected $fillable = ['email_from', 'email_to', 'email_cc', 'email_subject', 'email_message', 'created_at'];
}
