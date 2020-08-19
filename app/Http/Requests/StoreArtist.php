<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtist extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['string', 'max:100'],
            'discogs' => ['integer', 'nullable'],
            'imdb' => ['string', 'max:10', 'nullable'],
            'wikipedia' => ['string', 'max:100', 'nullable'],
        ];
    }
}
