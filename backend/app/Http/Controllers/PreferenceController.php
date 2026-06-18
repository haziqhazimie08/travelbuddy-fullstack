<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Preference;

class PreferenceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget' => 'string|nullable',
            'travel_style' => 'string|nullable',
            'interests' => 'string|nullable'
        ]);

        $preference = Preference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json($preference);
    }

    public function show(Request $request)
    {
        return response()->json($request->user()->preference);
    }
}
