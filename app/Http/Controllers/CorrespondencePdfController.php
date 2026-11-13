<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Support\CorrespondenceAccessPolicy;
use App\Services\Correspondence\CorrespondencePdf;
use App\Support\FileNamer;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CorrespondencePdfController extends Controller
{
    public function __invoke(Request $request, string $id): Response
    {
        abort_if(tenantOff('correspondence'), 404);

        $letter = Correspondence::query()
            ->with('letterhead')
            ->whereKey((int) $id)
            ->first([...CorrespondencePdf::renderColumns(), 'user_id', 'recipients']);

        if ($letter === null
            || $letter->status !== Correspondence::STATUS_APPROVED
            || !CorrespondenceAccessPolicy::canView($request->user(), $letter)) {
            abort(404);
        }

        try {
            $file = CorrespondencePdf::absolute(CorrespondencePdf::path($letter));
        } catch (RuntimeException) {
            $file = null;
        }

        if ($file === null) {
            return redirect()->route('correspondence', ['open' => $letter->id])->with('correspondence_error', __('resources/correspondence/strings.errors.pdf_download'));
        }

        $response = response()->download($file, FileNamer::download('pdf', $letter->number));
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
