<?php

namespace App\Models;

use App\Images\Photo;
use App\Values\CreditType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

final class Artist extends Model
{
    use HasSlug;
    use HasFactory;

    protected $with = ['photo'];

    public $fillable = [
        'name', 'genetivus',
        'discogs', 'filmpolski', 'wikipedia',
    ];

    protected $casts = [
        'discogs' => 'int',
        'filmpolski' => 'int',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(100);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function findBySlug(string $slug, array $columns = ['*']): ?self
    {
        $query = self::where('slug', $slug);

        return $query->first($columns);
    }

    public static function findBySlugOrNew(string $name): self
    {
        $artist = self::findBySlug(Str::slug($name));

        return $artist ?? self::create(['name' => $name]);
    }

    protected static function booted(): void
    {
        self::updated(function (self $artist) {
            if ($artist->isDirty('discogs', 'filmpolski', 'wikipedia')) {
                $artist->flushCache();
            }
        });
    }

    public function getDiscogsUrlAttribute(): ?string
    {
        return $this->discogs ? app('discogs')->url($this->discogs) : null;
    }

    public function getFilmpolskiUrlAttribute(): ?string
    {
        return $this->filmpolski ? app('filmPolski')->url($this->filmpolski) : null;
    }

    public function getWikipediaUrlAttribute(): ?string
    {
        return $this->wikipedia ? app('wikipedia')->url($this->wikipedia) : null;
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    public function getWikipediaExtractAttribute(): ?string
    {
        if (! $this->wikipedia) {
            return null;
        }

        return app('wikipedia')->extract($this->wikipedia);
    }

    public function discogsPhotos(): Collection
    {
        return $this->discogs ? app('discogs')->photos($this->discogs) : collect();
    }

    public function filmPolskiPhotos(): array
    {
        return $this->filmpolski ? app('filmPolski')->photos($this->filmpolski) : [];
    }

    public function discogsPhoto(string $type = 'normal'): ?string
    {
        return match ($type) {
            'normal' => $this->discogsPhotos()->first()?->uri,
            '150' => $this->discogsPhotos()->first()?->uri150,
            default => throw new InvalidArgumentException(),
        };
    }

    public function asActor(): BelongsToMany
    {
        return $this->belongsToMany(Tale::class, 'tales_actors')
            ->using(Actor::class)->as('credit')
            ->withPivot('characters', 'credit_nr')->withTimestamps()
            ->orderBy('year')->orderBy('title');
    }

    public function credits(): BelongsToMany
    {
        return $this->belongsToMany(Tale::class, 'credits')
            ->using(Credit::class)->as('credit')
            ->withPivot('id', 'type', 'as', 'nr')->withTimestamps()
            ->orderBy('year')->orderBy('title');
    }

    public function creditsFor(CreditType $type): EloquentCollection
    {
        return $this->credits
            ->filter(fn ($tale) => $tale->credit->ofType($type))
            ->values();
    }

    public function orderedCredits(): Collection
    {
        return $this->credits
            ->sortBy(fn ($tale) => $tale->credit->type->order())
            ->groupBy(fn ($tale) => $tale->credit->type->label);
    }

    public function scopeCountAppearances(Builder $query): void
    {
        $query->addSelect(['appearances' => DB::table(
                DB::table('credits')->select('tale_id')
                    ->whereColumn('artist_id', 'artists.id')
                ->union(
                    DB::table('tales_actors')->select('tale_id')
                        ->whereColumn('artist_id', 'artists.id'),
                ),
            )->selectRaw('count(*) as appearances'),
        ])->withCasts(['appearances' => 'int']);
    }

    public function appearances(): int
    {
        return DB::table(
            DB::table('credits')->select('tale_id')
                ->where('artist_id', $this->id)
            ->union(
                DB::table('tales_actors')->select('tale_id')
                    ->where('artist_id', $this->id),
            ),
        )->count();
    }

    public function refreshCache(): void
    {
        if ($this->discogs) {
            app('discogs')->refreshCache($this->discogs);
        }

        if ($this->filmpolski) {
            app('filmPolski')->refreshCache($this->filmpolski);
        }

        if ($this->wikipedia) {
            app('wikipedia')->refreshCache($this->wikipedia);
        }
    }

    public function flushCache(): bool
    {
        return ($this->discogs ? app('discogs')->forget($this->discogs) : true)
            && ($this->filmpolski ? app('filmPolski')->forget($this->filmpolski) : true)
            && ($this->wikipedia ? app('wikipedia')->forget($this->wikipedia) : true);
    }
}
