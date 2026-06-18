<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Itinerary;
use App\Models\Place;
use App\Models\SavedItinerary;
use App\Models\Preference;
use App\Services\AIService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ItineraryController extends Controller
{
    /**
     * Generate an AI itinerary using user preferences.
     */
    public function generate(Request $request, AIService $aiService)
    {
        $user = $request->user();
        $preference = $user->preference;

        if (!$preference) {
            return response()->json(['message' => 'Please set your preferences before generating.'], 400);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate   = Carbon::parse($validated['end_date']);
        $numDays   = $startDate->diffInDays($endDate) + 1;

        $generatedData = $aiService->generateItinerary($preference, $numDays, $startDate->toDateString(), $endDate->toDateString());

        if (!$generatedData || !isset($generatedData['itinerary'])) {
            return response()->json(['message' => 'AI generation failed. Please try again.'], 500);
        }

        $places = $generatedData['itinerary'];

        $itinerary = Itinerary::create([
            'user_id'     => $user->id,
            'title'       => 'Trip to ' . ($preference->interests ?: 'Malaysia') . ' (' . $startDate->format('d M') . ' – ' . $endDate->format('d M Y') . ')',
            'start_date'  => $startDate->toDateString(),
            'end_date'    => $endDate->toDateString(),
            'permissions' => 'private',
            'share_token' => Str::uuid(),
        ]);


        // Helper: ensure AI fields are always strings, not nested arrays
        $str = fn($v) => is_array($v) ? implode(', ', array_filter(array_map('strval', $v))) : ($v ?? null);

        foreach ($places as $item) {
            Place::create([
                'itinerary_id' => $itinerary->id,
                'name'         => $str($item['name'] ?? 'Unknown Place'),
                'location'     => $str($item['location'] ?? null),
                'description'  => $str($item['description'] ?? null),
                'time'         => $str($item['time'] ?? null),
            ]);
        }

        return response()->json($itinerary->load('places'), 201);
    }

    /**
     * List all itineraries for authenticated user.
     */
    public function index(Request $request)
    {
        $itineraries = $request->user()
            ->itineraries()
            ->with('places')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($itineraries);
    }

    /**
     * Show a single itinerary (also accessible by share token).
     */
    public function show(Request $request, $id)
    {
        $itinerary = Itinerary::with('places')->findOrFail($id);
        return response()->json($itinerary);
    }

    /**
     * Show an itinerary by its unique share token (public).
     */
    public function showByToken($token)
    {
        $itinerary = Itinerary::with('places')->where('share_token', $token)->firstOrFail();
        return response()->json($itinerary);
    }

    /**
     * Update itinerary title/dates/permissions.
     */
    public function update(Request $request, $id)
    {
        $itinerary = Itinerary::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'start_date'  => 'sometimes|date',
            'end_date'    => 'sometimes|date|after_or_equal:start_date',
            'permissions' => 'sometimes|string|in:private,public',
        ]);

        $itinerary->update($validated);
        return response()->json($itinerary->load('places'));
    }

    /**
     * Delete an itinerary owned by the user.
     */
    public function destroy(Request $request, $id)
    {
        $itinerary = Itinerary::where('user_id', $request->user()->id)->findOrFail($id);
        $itinerary->delete();
        return response()->json(['message' => 'Itinerary deleted successfully.']);
    }

    /**
     * Add a place to an existing itinerary.
     */
    public function addPlace(Request $request, $id)
    {
        $itinerary = Itinerary::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'time'        => 'nullable|string|max:100',
        ]);

        $place = $itinerary->places()->create($validated);
        return response()->json($place, 201);
    }

    /**
     * Delete a place from an itinerary.
     */
    public function deletePlace(Request $request, $id, $placeId)
    {
        $itinerary = Itinerary::where('user_id', $request->user()->id)->findOrFail($id);
        $place = $itinerary->places()->findOrFail($placeId);
        $place->delete();
        return response()->json(['message' => 'Activity removed.']);
    }

    /**
     * Generate / retrieve the unique shareable link for an itinerary.
     */
    public function getShareLink(Request $request, $id)
    {
        $itinerary = Itinerary::where('user_id', $request->user()->id)->findOrFail($id);

        if (!$itinerary->share_token) {
            $itinerary->share_token = Str::uuid();
            $itinerary->save();
        }

        return response()->json([
            'share_token' => $itinerary->share_token,
            'share_url'   => url('/api/shared/' . $itinerary->share_token),
        ]);
    }

    /**
     * Save another user's itinerary to own saved list.
     */
    public function save(Request $request, $id)
    {
        $existing = SavedItinerary::where('user_id', $request->user()->id)
            ->where('itinerary_id', $id)->first();

        if ($existing) {
            return response()->json(['message' => 'Already saved.'], 409);
        }

        SavedItinerary::create([
            'user_id'      => $request->user()->id,
            'itinerary_id' => $id,
        ]);

        return response()->json(['message' => 'Itinerary saved to your collection.']);
    }

    /**
     * Remove from saved list.
     */
    public function unsave(Request $request, $id)
    {
        SavedItinerary::where('user_id', $request->user()->id)
            ->where('itinerary_id', $id)
            ->delete();

        return response()->json(['message' => 'Removed from saved itineraries.']);
    }

    /**
     * Share an itinerary with another user by user ID.
     */
    public function share(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);

        $validated = $request->validate([
            'shared_with_user_id' => 'required|exists:users,id',
            'permissions'         => 'string|in:view,edit',
        ]);

        $itinerary->shares()->create($validated);
        return response()->json(['message' => 'Itinerary shared successfully.']);
    }
}
