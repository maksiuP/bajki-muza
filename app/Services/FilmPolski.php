<?php

namespace App\Services;

use App\Values\FilmPolski\Artist;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Regex\Regex;
use Symfony\Component\DomCrawler\Crawler;

class FilmPolski
{
    public function search(string $search): Collection
    {
        $source = Http::get(
            'http://www.filmpolski.pl/fp/index.php',
            ['szukaj' => $search],
        )->body();

        try {
            $crawler = (new Crawler($source))
                ->filter('.wynikiszukania');

            if ($crawler->count() === 0) {
                return collect();
            }

            $crawler = $crawler->first()->children();

            $max = $crawler->count() <= 10 ? $crawler->count() : 10;

            $people = collect();

            for ($i = 0; $i < $max; $i++) {
                $people->push([
                    'id' => Str::afterLast($crawler->eq($i)->children()->filter('a')->last()->attr('href'), '/'),
                    'name' => $crawler->eq($i)->children()->filter('a')->last()->text(),
                ]);
            }

            return $people->unique('id')->mapInto(Artist::class);
        } catch (InvalidArgumentException) {
            return collect();
        }
    }

    public function url(int $id): string
    {
        return "http://www.filmpolski.pl/fp/index.php?osoba={$id}";
    }

    public function photos(int $id): array
    {
        return Cache::remember(
            "filmpolski-{$id}-photos",
            CarbonInterval::week(),
            function () use ($id) {
                $photos = [];

                $source = $this->getPersonSource($id);

                $mainPhoto = $this->getMainPhoto($source);

                if ($mainPhoto !== null) {
                    $photos = [
                        'main' => [
                            'year' => null,
                            'photos' => [$mainPhoto],
                        ],
                    ];
                }

                $galleryId = $this->getGalleryId($source);

                if ($galleryId === null) {
                    return $photos;
                }

                $gallerySource = $this->getGallerySource($galleryId);

                return array_merge($photos, $this->getPhotosFromGallery($gallerySource));
            },
        );
    }

    protected function getPersonSource(int $id): string
    {
        return Http::get(
            'http://www.filmpolski.pl/fp/index.php',
            ['osoba' => $id],
        )->body();
    }

    protected function getGallerySource(int $galleryId): string
    {
        return Http::get(
            'http://www.filmpolski.pl/fp/index.php',
            ['galeria_osoby' => $galleryId],
        )->body();
    }

    protected function getMainPhoto(string $source): ?string
    {
        try {
            $crawler = (new Crawler($source))
                ->filter('.zdjecie')
                ->filter('img');

            if ($crawler->count() === 0) {
                return null;
            }

            return $crawler->attr('src');
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    protected function getGalleryId(string $source): ?int
    {
        try {
            $crawler = (new Crawler($source))
                ->filter('.galeria_mala')
                ->children()->last();

            return (int) Str::after($crawler->attr('href'), '/');
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    protected function getPhotosFromGallery(string $gallerySource): array
    {
        $photos = [];

        try {
            $crawler = (new Crawler($gallerySource))
                ->filter('#galeria_osoby')
                ->children();

            $count = $crawler->count();
        } catch (InvalidArgumentException) {
            return [];
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                $nodeCrawler = $crawler->eq($i);

                if ($nodeCrawler->nodeName() === 'h2') {
                    $title = $nodeCrawler->text();

                    continue;
                }

                if ($nodeCrawler->attr('class') === 'opzdj') {
                    $photos[$title ?? '?']['year'] = $nodeCrawler->text();

                    continue;
                }

                if ($nodeCrawler->attr('class') !== 'galeria_osoby_zdjecie') {
                    continue;
                }

                $photo = $nodeCrawler
                    ->children()->children()
                    ->attr('src');

                $photo = Regex::replace('/\\/([0-9]+)i\\//', '/$1z/', $photo)->result();

                $photos[$title ?? '?']['photos'][] = $photo;
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $photos;
    }

    public function refreshCache(int $id): void
    {
        $this->forget($id);

        $this->photos($id);
    }

    public function forget(int $id): bool
    {
        return Cache::forget("filmpolski-{$id}-photos");
    }
}
