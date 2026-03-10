<?php

declare(strict_types=1);

namespace App\Alonti\Auth\Cake;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class CakeHasher implements HasherContract
{
    private $cakeHasher = null;

    public function info($hashedValue) {}

    public function make($value, array $options = [])
    {
        return $this->getCakeHasher()->hash($value);
    }

    public function check($value, $hashedValue, array $options = [])
    {
        return $this->getCakeHasher()->check($value, $hashedValue);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }

    public function getCakeHasher()
    {
        if (!$this->cakeHasher) {
            $this->cakeHasher = new DefaultPasswordHasher();
        }

        return $this->cakeHasher;
    }
}
