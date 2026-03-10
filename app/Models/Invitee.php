<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Invitee extends BaseModel
{
    use SoftDeletes;

    protected $table = 'oj_invitees';

    protected static $unguarded = true;

    public function group()
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    public function cartInvitee()
    {
        return $this->belongsTo(CartInvitee::class, 'invitee_id');
    }
}
