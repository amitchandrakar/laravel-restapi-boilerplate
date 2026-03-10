<?php

declare(strict_types=1);


namespace App\Models;

use Illuminate\Support\Facades\DB;

class ZipcodeLatlan extends BaseModel
{
    protected $table = 'zipcodelatlon';

    public function getClosest()
    {
        $latitude = $this->Latitude;
        $longitude = $this->Longitude;
        $locationResult = ZipcodeLatlan::with(['zip', 'zip.cafe'])
            ->select([
                'ZipCode',
                DB::raw(
                    "(((acos(sin(($latitude * pi()/180)) * sin((Latitude * pi()/180))+cos(($latitude * pi()/180)) * cos((Latitude * pi()/180)) * cos(((($longitude) - Longitude) * pi()/180)))) * 180/pi()) * 60 * 1.1515) AS distance"
                ),
            ])
            ->whereHas('zip')
            ->having('distance', '<=', 25)
            ->orderBy('distance', 'ASC');

        return $locationResult;
    }

    public function closestCafesForPickup()
    {
        $latitude = $this->Latitude;
        $longitude = $this->Longitude;
        $query = <<<QUERY
select distinct cafe_id from (select
    zc.cafe_id,
    (zc.zipcode),
    ((((acos(sin(($latitude * pi()/180)) * sin((Latitude * pi()/180))+cos(($latitude * pi()/180)) * cos((Latitude * pi()/180)) * cos(((($longitude) - Longitude) * pi()/180)))) * 180/pi()) * 60 * 1.1515)) AS distance
from cafes c
left join zip_codes zc on zc.cafe_id = c.cafenum
left join zipcodelatlon zl on zl.zipcode = zc.zipcode
where zc.status = 1
having distance < 25
order by distance asc) as locations limit 3;
QUERY;
        $locationResult = DB::select($query);

        return collect($locationResult)->pluck('cafe_id');
        // return [4, 49, 56];
    }

    public function zip()
    {
        return $this->hasOne(Zipcode::class, 'zipcode', 'ZipCode');
    }
}
