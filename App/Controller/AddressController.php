<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Response;

final class AddressController extends BaseController
{
    public function autocomplete(): never
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $apiKey = (string) config('app.google_maps_key', '');

        if (mb_strlen($query) < 4) {
            Response::json(['success' => true, 'data' => []]);
        }

        if ($apiKey === '') {
            Response::json(['success' => true, 'data' => []]);
        }

        $payload = json_encode([
            'input' => $query,
            'includedRegionCodes' => ['fr', 'be', 'es', 'gb', 'it'],
            'languageCode' => 'fr',
        ], JSON_THROW_ON_ERROR);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'X-Goog-Api-Key: ' . $apiKey,
                    'X-Goog-FieldMask: suggestions.placePrediction.placeId,suggestions.placePrediction.text,suggestions.placePrediction.structuredFormat',
                ]),
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(
            'https://places.googleapis.com/v1/places:autocomplete',
            false,
            $context
        );

        if ($response === false) {
            Response::json(['success' => true, 'data' => []]);
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded) || !isset($decoded['suggestions']) || !is_array($decoded['suggestions'])) {
            Response::json(['success' => true, 'data' => []]);
        }

        $items = [];

        foreach ($decoded['suggestions'] as $suggestion) {
            $prediction = $suggestion['placePrediction'] ?? null;

            if (!is_array($prediction)) {
                continue;
            }

            $text = $prediction['text']['text'] ?? null;

            if (!is_string($text) || $text === '') {
                continue;
            }

            $items[] = [
                'label' => $text,
                'address' => $text,
                'postal_code' => '',
                'city' => $prediction['structuredFormat']['secondaryText']['text'] ?? '',
            ];
        }

        Response::json(['success' => true, 'data' => array_slice($items, 0, 5)]);
    }
}
