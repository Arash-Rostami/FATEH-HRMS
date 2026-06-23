@extends('errors.layout')

@section('title', 'صفحه پیدا نشد')

@section('icon')
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-slate-400">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zm-7.518-.267A8.25 8.25 0 1120.25 10.5M8.288 14.212A5.25 5.25 0 1117.25 10.5" />
</svg>
@endsection

@section('heading', 'مسیر نامشخص')

@section('message', 'صفحه‌ای که به دنبال آن هستید پیدا نشد. ممکن است آدرس تغییر کرده یا حذف شده باشد.')
