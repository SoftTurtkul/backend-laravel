<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    use HasFactory;

    protected $fillable = [
        'customer_id', 'driver_id', 'partner_id', 'total_price',
        'item_count', 'address', 'longitude', 'latitude', 'status'
    ];

    public function customer() {
        return $this->belongsTo(Customer::class);
    }

//    public function getClientAttribute() {
//        return $this->customer;
//    }
}
