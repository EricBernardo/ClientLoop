<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportTemplateController extends Controller
{
    public function __invoke(string $type): StreamedResponse
    {
        $template = match ($type) {
            'customers' => [
                'filename' => 'modelo-importacao-responsaveis.csv',
                'headers' => ['responsible_name', 'responsible_phone', 'pet_name', 'last_activity_at', 'next_return_at', 'opted_out'],
                'example' => ['Maria da Silva', '(11) 99999-9999', 'Thor', '2026-03-15 14:00', '2026-09-15 14:00', 'false'],
            ],
            default => abort(404),
        };

        return response()->streamDownload(function () use ($template): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $template['headers']);
            fputcsv($output, $template['example']);
            fclose($output);
        }, $template['filename'], ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
