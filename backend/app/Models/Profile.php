<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bio', 'profile_picture', 'location', 'travel_preferences', 'eco_friendly', 'family_friendly'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
