<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Translation\TranslationExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationExportController extends Controller
{
    public function __construct(private readonly TranslationExporter $exporter)
    {
    }

    public function __invoke(Request $request, string $locale): StreamedResponse
    {
        $tags = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $request->query('tags', '')),
        )));

        return $this->exporter->export($locale, $tags);
    }
}
