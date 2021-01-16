<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTale;
use App\Images\Cover;
use App\Models\Artist;
use App\Models\Tale;
use App\Values\CreditData;

class TaleController extends Controller
{
    public function create()
    {
        return view('tales.create');
    }

    public function store(StoreTale $request)
    {
        $tale = new Tale();

        $tale->fill(
            $data = $request->validated(),
        )->save();

        if ($request->file('cover')) {
            $tale->cover()->associate(
                Cover::store($request->file('cover')),
            )->save();
        }

        $this->saveRelationships($tale, $data);

        return redirect()->route('tales.show', $tale);
    }

    public function show(Tale $tale)
    {
        $tale->load([
            'credits' => fn ($query) => $query->countAppearances(),
            'actors' => fn ($query) => $query->countAppearances(),
        ]);

        return view('tales.show', ['tale' => $tale]);
    }

    public function edit(Tale $tale)
    {
        return view('tales.edit', ['tale' => $tale]);
    }

    public function update(StoreTale $request, Tale $tale)
    {
        $tale->fill(
            $data = $request->validated(),
        )->save();

        if ($request->boolean('remove_cover')) {
            $tale->cover()->disassociate()->save();
        } elseif ($request->file('cover')) {
            $tale->cover()->associate(
                Cover::store($request->file('cover')),
            )->save();
        }

        $this->saveRelationships($tale, $data);

        return redirect()->route('tales.show', $tale);
    }

    protected function saveRelationships(Tale $tale, array $data): void
    {
        $credits = collect($data['credits'] ?? [])
            ->groupBy(fn ($credit) => Artist::findBySlugOrNew($credit['artist'])->id)
            ->map->map(fn ($credit) => new CreditData([
                'type' => $credit['type'],
                'as' => $credit['as'],
                'nr' => (int) $credit['nr'],
            ]));

        $tale->syncCredits($credits);

        $actors = collect($data['actors'] ?? [])
            ->keyBy(fn ($credit) => Artist::findBySlugOrNew($credit['artist'])->id)
            ->map(fn ($credit) => [
                'characters' => $credit['characters'],
                'credit_nr' => $credit['credit_nr'],
            ]);

        $tale->actors()->sync($actors);
    }
}
