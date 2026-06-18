<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'budget', 'travel_style', 'interests'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
