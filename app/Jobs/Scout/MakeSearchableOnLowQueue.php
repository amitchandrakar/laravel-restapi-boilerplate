<?php

declare(strict_types=1);

namespace App\Jobs\Scout;

use App\Support\QueuePriority;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Jobs\MakeSearchable;

class MakeSearchableOnLowQueue extends MakeSearchable
{
    /**
     * @param  Collection<int, Model>  $models
     */
    public function __construct(Collection $models)
    {
        parent::__construct($models);
        $this->onQueue(QueuePriority::low());
    }
}
