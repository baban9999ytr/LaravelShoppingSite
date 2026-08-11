<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class DeliveryCalculatorService
{
  
    private static function getCoordinates(string $city): ?array
    {
        $cacheKey = 'city_coords_' . md5(strtolower(trim($city)));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($city) {
            $response = Http::withHeaders([
                'User-Agent' => 'LaravelAppDeliveryCalculator/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $city,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat' => (float) $data['lat'],
                    'lon' => (float) $data['lon'],
                ];
            }

            return null;
        });
    }

    /**

     * @param string $originCity
     * @param string $destinationCity
     * @param Carbon|string $orderedAt
     * @param int $handlingHours
     * @return Carbon
     */
    public static function calculateEstimatedArrival(
        string $originCity,
        string $destinationCity,
        Carbon|string $orderedAt,
        int $handlingHours = 4
    ): Carbon {
        $orderTime = Carbon::parse($orderedAt);

        if (strtolower(trim($originCity)) === strtolower(trim($destinationCity))) {
            return $orderTime->copy()->addHours(12);
        }

        try {
            $origin = self::getCoordinates($originCity);
            $destination = self::getCoordinates($destinationCity);

            if (!$origin || !$destination) {
                return $orderTime->copy()->addHours(48);
            }

            $cacheKey = "route_duration_{$origin['lat']}_{$origin['lon']}_to_{$destination['lat']}_{$destination['lon']}";

            $travelSeconds = Cache::remember($cacheKey, now()->addDays(30), function () use ($origin, $destination) {
                $url = sprintf(
                    'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=false',
                    $origin['lon'], $origin['lat'],
                    $destination['lon'], $destination['lat']
                );

                $response = Http::get($url);

                if ($response->successful() && isset($response->json()['routes'][0]['duration'])) {
                    return (float) $response->json()['routes'][0]['duration'];
                }

                return null;
            });

            if (!$travelSeconds) {
                return $orderTime->copy()->addHours(48);
            }

            $travelMinutes = ceil($travelSeconds / 60);

            return $orderTime->copy()
                ->addMinutes($travelMinutes)
                ->addHours($handlingHours);

        } catch (Exception $e) {
            return $orderTime->copy()->addHours(48);
        }
    }
}