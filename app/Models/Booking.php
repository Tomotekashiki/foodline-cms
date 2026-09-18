<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model {
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'event_date',
        'address',
        'notes',
        'status',
        'order_type',
        'order_details',
        'total_estimate',
    ];
    protected $casts = ['order_details' => 'array', 'event_date' => 'datetime'];
}