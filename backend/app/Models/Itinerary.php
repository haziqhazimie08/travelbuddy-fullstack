<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'start_date', 'end_date', 'permissions'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function places()
    {
        return $this->hasMany(Place::class);
    }

    public function shares()
    {
        return $this->hasMany(ShareItinerary::class);
    }

    public function saves()
    {
        return $this->hasMany(SavedItinerary::class);
    }
}
