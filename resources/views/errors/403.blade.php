@extends('errors.layout')

@section('error_code', 'ERR_403')

@section('minimal_layout', true)

@section('title', 'دسترسی غیرمجاز')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-rose-500">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
</svg>
@endsection

@section('heading', 'دسترسی محدود شده')

@section('message', 'متاسفانه شما سطح دسترسی لازم برای مشاهده این صفحه را ندارید. در صورت نیاز با پشتیبانی تماس بگیرید.')
