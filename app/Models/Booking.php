<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model {
    protected $guarded = [];
    protected $casts = ['order_details' => 'array', 'event_date' => 'datetime'];
}