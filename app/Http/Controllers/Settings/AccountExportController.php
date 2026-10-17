<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AccountExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountExportController extends Controller
{
    /**
     * Download every piece of data stored about the user (RGPD).
     */
    public function __invoke(Request $request, AccountExporter $exporter): JsonResponse
    {
        $filename = 'tarmac-export-'.now()->format('Y-m-d').'.json';

        return response()
            ->json($exporter->export($request->user(), $request), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
