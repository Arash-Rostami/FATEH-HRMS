@extends('errors.layout')

@section('error_code', 'ERR_405')

@section('title', 'متد مجاز نیست')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-orange-500">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection

@section('heading', 'متد درخواست پشتیبانی نمی‌شود')

@section('message', 'این آدرس، متد HTTP استفاده‌شده (مانند GET یا POST) را نمی‌پذیرد. لطفاً از مسیر صحیح وارد شوید و دوباره تلاش کنید.')