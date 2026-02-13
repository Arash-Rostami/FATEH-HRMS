@extends('layouts.app')
@section('content')

    <div class="flex flex-col h-screen overflow-hidden bg-[#FFFEFB]">
        <x-dashboard.header.main/>
        <x-dashboard.navbar.top/>
        <x-dashboard.navbar.left/>
        <x-dashboard.tab.main/>
        <x-dashboard.navbar.right/>
        <x-dashboard.navbar.mobile/>
    </div>
@endsection
