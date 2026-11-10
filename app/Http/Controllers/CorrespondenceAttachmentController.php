<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard\Correspondence\Actions\DownloadAttachmentAction;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CorrespondenceAttachmentController extends Controller
{
    public function __invoke(Request $request, DownloadAttachmentAction $action, string $id, string $index): Response
    {
        abort_if(tenantOff('correspondence'), 404);

        try {
            $response = $action->execute($request->user(), (int) $id, (int) $index);
        } catch (RuntimeException) {
            abort(404);
        }

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
