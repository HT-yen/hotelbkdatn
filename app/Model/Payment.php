<?php

namespace App\Model;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\Traits\SearchTrait;
use Carbon\Carbon;
use DB;

class Payment extends Model
{
    use SoftDeletes, SearchTrait;


    /**
     * The attributes that are mass assignable.
     *
     * @var array $fillable
     */
    protected $fillable = [
        'id', 'reservation_id', 'transaction_id', 'payment_gross'
    ];

    /**
     * Relationship belongsTo with reservation
     *
     * @return array
    */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'id');
    }
}
