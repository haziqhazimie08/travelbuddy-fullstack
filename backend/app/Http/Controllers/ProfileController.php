<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's full profile with saved itineraries.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['profile', 'preference', 'savedItineraries.itinerary.places']);

        return response()->json([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->avatar,
            'profile' => $user->profile,
            'preference' => $user->preference,
            'saved_itineraries' => $user->savedItineraries,
        ]);
    }

    /**
     * Update the authenticated user's name/email.
     */
    public function updateUser(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $request->user()->update($validated);

        return response()->json($request->user());
    }

    /**
     * Update or create the profile (bio, location, preferences, picture).
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'bio'                 => 'nullable|string|max:500',
            'location'            => 'nullable|string|max:255',
            'travel_preferences'  => 'nullable|string|max:255',
            'eco_friendly'        => 'nullable|boolean',
            'family_friendly'     => 'nullable|boolean',
        ]);

        $profile = Profile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json($profile);
    }

    /**
     * Upload a profile picture and return its URL.
     */
    public function uploadPicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $profile = Profile::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['profile_picture' => $path]
        );

        return response()->json([
            'profile_picture'     => $path,
            'profile_picture_url' => asset('storage/' . $path),
        ]);
    }
}
