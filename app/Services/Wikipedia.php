<?php

namespace App\Services;

use App\Values\Wikipedia\ArtistCollection;
use Carbon\CarbonInterval;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Wikipedia
{
    public function search(string $search): ArtistCollection
    {
        $response = Http::get('https://pl.wikipedia.org/w/api.php', [
            'action' => 'opensearch',
            'search' => $search,
            'limit' => 10,
            'redirects' => 'resolve',
        ])->json();

        $artists = collect($response[3])
            ->map(fn ($uri, $key) => [
                'uri' => $uri,
                'name' => $response[1][$key],
            ])->all();

        return ArtistCollection::fromArray($artists);
    }

    public function url(string $title): string
    {
        return "https://pl.wikipedia.org/wiki/$title";
    }

    public function extract(string $title): ?string
    {
        $titleHash = md5($title);

        return Cache::remember(
            "wikipedia-$titleHash-extract",
            CarbonInterval::week(),
            function () use ($title) {
                $response = Http::get('https://pl.wikipedia.org/w/api.php', [
                    'action' => 'query',
                    'titles' => $title,
                    'prop' => 'extracts',
                    'exintro' => 1,
                    'redirects' => 1,
                    'format' => 'json',
                ]);

                return Arr::first($response['query']['pages'])['extract'] ?? null;
            }
        );
    }

    public function forget(string $title): bool
    {
        $titleHash = md5($title);

        return Cache::forget("wikipedia-$titleHash-extract");
    }
}
