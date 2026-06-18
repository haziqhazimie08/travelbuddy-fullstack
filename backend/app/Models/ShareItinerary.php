<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareItinerary extends Model
{
    use HasFactory;

    protected $fillable = ['itinerary_id', 'shared_with_user_id', 'shared_at', 'permissions'];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
