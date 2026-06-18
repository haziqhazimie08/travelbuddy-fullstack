<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Preference;

class AIService
{
    protected string $apiKey;
    protected string $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function generateItinerary(Preference $preference, int $numDays = 3, string $startDate = '', string $endDate = '')
    {
        $destination = $preference->interests ?: 'Malaysia';
        $dateRange   = ($startDate && $endDate)
            ? "from {$startDate} to {$endDate} ({$numDays} day" . ($numDays > 1 ? 's' : '') . ")"
            : "{$numDays} days";

        $prompt  = "You are a Malaysian travel guide expert. Generate a detailed {$numDays}-day travel itinerary for a trip to {$destination}, Malaysia, {$dateRange}.\n";
        $prompt .= "Traveler preferences:\n";
        $prompt .= "- Budget: " . $preference->budget . "\n";
        $prompt .= "- Travel Style: " . $preference->travel_style . "\n";
        $prompt .= "- Destination: " . $destination . "\n\n";
        $prompt .= "Rules:\n";
        $prompt .= "- Create exactly {$numDays} day(s) of activities.\n";
        $prompt .= "- Each day should have 3–5 activities/places to visit.\n";
        $prompt .= "- Each place must be a real, well-known location in {$destination}, Malaysia.\n";
        $prompt .= "- Use local Malaysian place names and include the specific area/district.\n\n";
        $prompt .= "Return ONLY a valid JSON object (no markdown, no explanation) in this exact format:\n";
        $prompt .= '{"itinerary": [{"name": "Place Name", "location": "Area, ' . $destination . '", "description": "Brief description", "time": "Day 1 - 09:00 AM"}]}';

        $response = Http::timeout(60)->post($this->url . '?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 4096,
            ]
        ]);

        if ($response->successful()) {
            $data    = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($content) {
                // Strip markdown code fences if present
                $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
                $content = preg_replace('/```\s*$/m', '', $content);
                $decoded = json_decode(trim($content), true);
                return $decoded;
            }
        }

        return null;
    }
}
