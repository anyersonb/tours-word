@extends('errors.layout', ['code' => 429])

@section('extra')
    <x-ui.button variant="secondary" :href="config('brand.contact.whatsapp_url')" target="_blank" rel="noopener">{{ __('errors.whatsapp') }}</x-ui.button>
@endsection
