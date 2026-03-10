<?php

declare(strict_types=1);


namespace App\Models;

use App\Mailer\ProspectMailer;

class MxProspect extends BaseModel
{
    //
    protected static $unguarded = true;

    const UPDATED_AT = 'modified';

    public function mailer()
    {
        return new ProspectMailer($this);
    }
}
