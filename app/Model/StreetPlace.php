<?php

namespace App\Model;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\Traits\SearchTrait;
use Carbon\Carbon;
use DB;

class StreetPlace extends Model
{
    use SoftDeletes, SearchTrait;


    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'id', 'place_id', 'transaction_id', 'street_name'
    ];

    /**
     * Relationship belongsTo with reservation
     *
     * @return array
    */
    public function place()
    {
        return $this->belongsTo(place::class, 'place_id', 'id');
    }
}
