@extends('errors.layout')
@section('code', '403')
@section('title', 'This court is reserved')
@section('message')
    @if (auth()->user()?->organizer_status === 'pending')
        Organizer tools unlock on your account once a PickleX admin approves your club. You can keep playing in the meantime.
    @elseif (auth()->check())
        Your account doesn't have access to this page. Switch modes from the menu, or head back to your PickleX home.
    @else
        Log in to see this page.
    @endif
@endsection
