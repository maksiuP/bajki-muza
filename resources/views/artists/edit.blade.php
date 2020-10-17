@extends('layouts.app')

@section('content')

    <div class="text-center">
        <h2 class="text-2xl font-medium">
            <a href="{{ route('artists.show', $artist) }}">
                @foreach (explode(' ', $artist->name) as $word)
                    <span class="shadow-title">{{ $word }}</span>
                @endforeach
            </a>
        </h2>
    </div>

    @php

        $data = [
            'discogs' => old('discogs', $artist->discogs),
            'filmpolski' => old('filmpolski', $artist->filmpolski),
            'wikipedia' => old('wikipedia', $artist->wikipedia),

            'photoSource' => old('photo_source', $artist->photo_source),
        ];

    @endphp

    <form
        method="post" action="{{ route('artists.update', $artist) }}"
        enctype="multipart/form-data"
        class="flex flex-col space-y-5"
        x-data="artistFormData(@encodedjson($data))"
        x-init="
            $watch('photo.file', value => {
                setPhotoPreview($refs.photo.files);
                if (value === '') return;
                photo.uri = '';
                photo.remove = false;
                photo.source = '';
            });
        ">
        @method('put')
        @csrf

        @if ($errors->any())
            <ul class="text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <div class="flex flex-col">
            <label for="name" class="w-full font-medium pb-1 text-gray-700 dark:text-gray-400">Imię i nazwisko</label>
            <input type="text" name="name" value="{{ old('name', $artist->name) }}"
                class="w-full form-input">
        </div>

        <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-5">

            <div class="w-full sm:w-1/2 flex space-x-5">

                <div class="w-1/2 items-stretch flex flex-col">
                    <label for="year" class="w-full font-medium pb-1 text-gray-700 dark:text-gray-400">Discogs</label>
                    <div class="relative w-full">
                        <input
                            type="text" class="w-full form-input" autocomplete="off"
                            x-model="discogs.value" name="discogs"
                            value="{{ old('discogs', $artist->discogs) }}"
                            x-on:keydown.arrow-up="arrow('discogs', 'up')" x-on:keydown.arrow-down="arrow('discogs', 'down')"
                            x-on:keydown.enter.prevent="enter('discogs')" x-on:input.debounce="findPeople('discogs')"
                            x-on:focus="discogs.isOpen = discogs.shouldCloseOnBlur = true" x-on:blur="closeDropdown('discogs')">
                        <template x-if="discogs.isOpen && discogs.value.length >= 5">
                            <ul class="absolute mt-2 z-50 py-1 w-full text-gray-800 bg-white rounded-md shadow-md border border-gray-300"
                                x-on:mousedown="discogs.shouldCloseOnBlur = false">
                                <template x-if="discogs.people.length === 0">
                                    <li class="w-full px-3 py-1 text-gray-600">
                                        Brak wyników
                                    </li>
                                </template>
                                <template x-for="(person, index) in discogs.people" x-key="person.id">
                                    <li
                                        x-on:mouseover="discogs.hovered = index" x-on:click="select('discogs', person)"
                                        class="select-none flex w-full px-3 py-1 text-gray-800 text-left justify-between"
                                        :class="{ 'bg-cool-gray-100': discogs.hovered === index }">
                                        <span x-text="person.name"></span>
                                        <span class="text-gray-400" x-text="discogs.value === person.id ? '✓ ' : ''"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </div>

                <div class="w-1/2 items-stretch flex flex-col">
                    <label for="year" class="w-full font-medium pb-1 text-gray-700 dark:text-gray-400">Film Polski</label>
                    <div class="relative w-full">
                        <input
                            type="text" class="w-full form-input" autocomplete="off"
                            x-model="filmPolski.value" name="filmpolski"
                            value="{{ old('filmpolski', $artist->filmpolski) }}"
                            x-on:keydown.arrow-up="arrow('filmPolski', 'up')" x-on:keydown.arrow-down="arrow('filmPolski', 'down')"
                            x-on:keydown.enter.prevent="enter('filmPolski')" x-on:input.debounce="findPeople('filmPolski')"
                            x-on:focus="filmPolski.isOpen = filmPolski.shouldCloseOnBlur = true" x-on:blur="closeDropdown('filmPolski')">
                        <template x-if="filmPolski.isOpen && filmPolski.value.length >= 5">
                            <ul class="absolute mt-2 z-50 py-1 w-full text-gray-800 bg-white rounded-md shadow-md border border-gray-300"
                                x-on:mousedown="filmPolski.shouldCloseOnBlur = false">
                                <template x-if="filmPolski.people.length === 0">
                                    <li class="w-full px-3 py-1 text-gray-600">
                                        Brak wyników
                                    </li>
                                </template>
                                <template x-for="(person, index) in filmPolski.people" x-key="person.id">
                                    <li
                                        x-on:mouseover="filmPolski.hovered = index" x-on:click="select('filmPolski', person)"
                                        class="select-none flex w-full px-3 py-1 text-gray-800 text-left justify-between"
                                        :class="{ 'bg-cool-gray-100': filmPolski.hovered === index }">
                                        <span x-text="person.name"></span>
                                        <span class="text-gray-400" x-text="filmPolski.value === person.id ? '✓ ' : ''"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </div>

            </div>

            <div class="w-full sm:w-1/2 flex flex-col">
                <label for="title" class="w-full font-medium pb-1 text-gray-700 dark:text-gray-400">Wikipedia</label>
                <div class="relative w-full">
                    <input
                        type="text" class="w-full form-input" autocomplete="off"
                        x-model="wikipedia.value" name="wikipedia"
                        value="{{ old('wikipedia', $artist->wikipedia) }}"
                        x-on:keydown.arrow-up="arrow('wikipedia', 'up')" x-on:keydown.arrow-down="arrow('wikipedia', 'down')"
                        x-on:keydown.enter.prevent="enter('wikipedia')" x-on:input.debounce="findPeople('wikipedia')"
                        x-on:focus="wikipedia.isOpen = wikipedia.shouldCloseOnBlur = true" x-on:blur="closeDropdown('wikipedia')">
                    <template x-if="wikipedia.isOpen && wikipedia.value.length >= 5">
                        <ul class="absolute mt-2 z-50 py-1 w-full text-gray-800 bg-white rounded-md shadow-md border border-gray-300"
                            x-on:mousedown="wikipedia.shouldCloseOnBlur = false">
                            <template x-if="wikipedia.people.length === 0">
                                <li class="w-full px-3 py-1 text-gray-600">
                                    Brak wyników
                                </li>
                            </template>
                            <template x-for="(person, index) in wikipedia.people" x-key="person.id">
                                <li
                                    x-on:mouseover="wikipedia.hovered = index" x-on:click="select('wikipedia', person)"
                                    class="select-none flex w-full px-3 py-1 text-gray-800 text-left justify-between"
                                    :class="{ 'bg-cool-gray-100': wikipedia.hovered === index }">
                                    <span x-text="person.name"></span>
                                    <span class="text-gray-400" x-text="wikipedia.value === person.id ? '✓ ' : ''"></span>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>

        </div>

        <div class="flex flex-col space-y-3">

            <div class="flex flex-col">
                <span for="photo" class="w-full font-medium pb-1 text-gray-700 dark:text-gray-400">Zdjęcie</span>
                <input type="hidden" name="remove_photo" :value="photo.remove ? 1 : 0">
                <input type="hidden" name="photo_uri" :value="photo.uri">
                <div class="flex space-x-5">
                    <label class="flex-grow h-10 flex items-center bg-white rounded-md border overflow-hidden cursor-pointer dark:border-gray-900 dark:bg-gray-800">
                        <div class="flex-none bg-placeholder-artist w-10 h-10">
                            @if ($artist->photo())
                                <img src="{{ $artist->photo('84') }}"
                                    class="w-10 h-10 object-cover"
                                    x-show="photo.file === '' && photo.uri === '' && photo.remove == false">
                            @endif
                            <template x-if="photo.file !== ''">
                                <img :src="photo.preview"
                                    class="w-10 h-10 object-cover">
                            </template>
                        </div>
                        <span class="px-3 py-2">
                            <span
                                x-text="photoLabelText()">
                                Wybierz plik
                            </span>
                            <small class="pl-1 text-xs font-medium"
                                x-text="photo.file !== '' ? fileSize($refs.photo.files[0].size) : ''"></small>
                        </span>
                        <template x-if="photo.file !== ''">
                            <button type="button" x-on:click="photo.file = ''" class="flex-none"></button>
                        </template>
                        <input type="file" name="photo" class="hidden"
                            x-ref="photo" x-model="photo.file">
                    </label>
                    <template x-if="!photo.remove">
                        <button type="button" x-on:click="photo.remove = true; photo.file = photo.uri = photo.source = ''"
                                class="flex-none px-3 py-2 bg-white rounded-md border font-medium text-sm
                                hover:bg-red-100 hover:text-red-700
                                active:bg-red-600 active:cover-red-600 active:text-red-100
                                dark:bg-gray-800 dark:text-gray-100 dark:border-gray-900
                                dark:hover:bg-red-800 dark:hover:text-red-100
                                transition-colors duration-150">
                            Usuń
                        </button>
                    </template>
                    <template x-if="photo.remove">
                        <button type="button" x-on:click="photo.remove = false; photo.source = photo.initialSource"
                                class="flex-none px-3 py-2 bg-red-600 text-red-100 rounded-md border-red-600 font-medium text-sm
                                hover:bg-red-500 hover:border-red-500 hover:text-white
                                active:bg-white active:text-black
                                dark:active:bg-gray-800 dark:active:text-gray-100 dark:border-gray-900
                                transition-colors duration-150">
                            Nie usuwaj
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex flex-row items-center space-x-3">
                <label for="photo_source" class="flex-none text-sm font-medium text-gray-700 dark:text-gray-400">Źródło</label>
                <input type="text" value="{{ old('photo_source', $artist->photo_source) }}"
                    name="photo_source" x-model="photo.source"
                    class="w-full text-sm px-2 py-1 form-input">
            </div>

        </div>

        <button type="submit"
            class="self-center px-4 py-2 bg-white dark:bg-gray-800 text-sm font-medium rounded-full shadow-md">
            Zapisz
        </button>

        <div class="space-y-3 flex flex-col items-center">

            <div class="py-3 flex items-center flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-5">
                <a href="https://www.google.com/search?q={{ urlencode($artist->name) }}&tbm=isch" target="_blank"
                    class="text-sm font-medium">
                    <span class="shadow-link">Google</span> →
                </a>
                <a href="http://fototeka.fn.org.pl/pl/strona/wyszukiwarka.html?key={{
                            urlencode(Str::afterLast($artist->name, ' ').' '.Str::beforeLast($artist->name, ' '))
                        }}&search_type_in=osoba&filter[charakter][]=portret" target="_blank"
                    class="text-sm font-medium">
                    <span class="shadow-link">Fototeka</span> →
                </a>
            </div>

            <div class="w-full flex flex-wrap justify-around">
                @foreach ($artist->discogsPhotos() as $photo)
                    @php $ref = 'discogs_'.$loop->iteration @endphp
                    <button class="group relative m-1.5 shadow-lg rounded-lg overflow-hidden focus:outline-none"
                        type="button" x-on:click="setPhotoUri('{{ $photo['uri'] }}')">
                        <img class="h-40" src="{{ $photo['uri'] }}" x-ref="{{ $ref }}"
                            x-on:load="dimensions.{{ $ref }} = $event.target.naturalWidth + '×' + $event.target.naturalHeight">
                        <div class="absolute top-0 right-0 pl-8 pb-2
                            opacity-0 group-hover:opacity-100 transition-all duration-300"
                            style="background-image: radial-gradient(ellipse farthest-side at top right, rgba(0, 0, 0, .4), transparent);">
                            <span class="text-2xs text-white px-2"
                                x-text="$refs.{{ $ref }}.complete
                                            ? $refs.{{ $ref }}.naturalWidth + '×' + $refs.{{ $ref }}.naturalHeight
                                            : dimensions.{{ $ref }}">
                            </span>
                        </div>
                        <div class="absolute inset-0 rounded-lg group-focus:inset-shadow-light
                            transition-all duration-300"
                            :class="{ 'inset-shadow-hard opacity-100': photo.uri === '{{ $photo['uri'] }}' }">
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="w-full flex flex-wrap justify-around">
                @foreach ($artist->filmPolskiPhotos() as $title => $movie)
                    @foreach ($movie['photos'] as $photo)
                        @php $ref = 'filmpolski_'.$loop->parent->iteration.'_'.$loop->iteration @endphp
                        <button class="group relative m-1.5 shadow-lg rounded-lg overflow-hidden focus:outline-none"
                            type="button" x-on:click="setPhotoUri('https://filmpolski.pl{{ $photo }}')">
                            <img class="h-40" src="https://filmpolski.pl{{ $photo }}" x-ref="{{ $ref }}"
                                x-on:load="dimensions.{{ $ref }} = $event.target.naturalWidth + '×' + $event.target.naturalHeight">
                            @if ($title !== 'main' && $title !== '')
                                <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent
                                    opacity-0 group-hover:opacity-75 transition-all duration-300"></div>
                            @endif
                            <div class="absolute bottom-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <div class="flex flex-col p-2 text-white">
                                    <small class="text-xs">{{ $movie['year'] }}</small>
                                    <span class="text-sm font-medium leading-tight">{{ $title !== 'main' ? Str::title($title) : '' }}</span>
                                </div>
                            </div>
                            <div class="absolute top-0 right-0 pl-8 pb-2
                                opacity-0 group-hover:opacity-100 transition-all duration-300"
                                style="background-image: radial-gradient(ellipse farthest-side at top right, rgba(0,0,0,.4), transparent);">
                                <span class="text-2xs text-white px-2"
                                    x-text="$refs.{{ $ref }}.complete
                                                ? $refs.{{ $ref }}.naturalWidth + '×' + $refs.{{ $ref }}.naturalHeight
                                                : dimensions.{{ $ref }}">
                                </span>
                            </div>
                            <div class="absolute inset-0 rounded-lg group-focus:inset-shadow-light
                                transition-all duration-300"
                                :class="{ 'inset-shadow-hard opacity-100': photo.uri === 'https://filmpolski.pl{{ $photo }}' }">
                            </div>
                        </button>
                    @endforeach
                @endforeach
            </div>

        </div>

    </form>

@endsection
