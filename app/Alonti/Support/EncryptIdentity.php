<?php

declare(strict_types=1);


namespace App\Alonti\Support;

trait EncryptIdentity
{
    public function getEncryptedIdAttribute()
    {
        return urlencode(app('hashid')->encode($this->id));
    }

    public static function findByEncryptedId($encrypted_id)
    {
        [$id] = app('hashid')->decode(urldecode($encrypted_id));

        return self::find($id);
    }

    public static function decryptId($encrypted_id)
    {
        [$id] = app('hashid')->decode(urldecode($encrypted_id));

        return $id;
    }
}
