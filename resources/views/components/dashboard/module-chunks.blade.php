@php
use Illuminate\Support\Facades\Vite;

$chunks = [];
foreach (['dms','ths','taskboard','project','channel','contact','reservation','profile','analytics','energy','tasksheet'] as $module) {
    try {
        $chunks[$module] = Vite::asset("resources/js/components/alpine/modules/{$module}.js");
    } catch (\Throwable $e) {
    }
}
@endphp
<script>window.__moduleChunks = @json($chunks);</script>