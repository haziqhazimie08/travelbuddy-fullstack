<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = ['itinerary_id', 'name', 'location', 'description', 'time'];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
