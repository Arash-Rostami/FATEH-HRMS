@extends('errors.layout')

@section('error_code', 'ERR_419')

@section('title', 'نشست منقضی شده')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-amber-500">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection

@section('heading', 'پایان زمان اتصال')

@section('message', 'به دلیل عدم فعالیت، نشست شما پایان یافته است. لطفاً صفحه را بارگذاری مجدد کرده و دوباره تلاش کنید.')
