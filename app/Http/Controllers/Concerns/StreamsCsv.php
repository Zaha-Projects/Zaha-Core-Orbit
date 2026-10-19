<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streamed CSV downloads (extracted from EnterpriseReportsController::csvResponse).
 * Rows are written as they are iterated, so a LazyCollection/cursor never loads the report into memory.
 */
trait StreamsCsv
{
    /**
     * @param  iterable  $rows  each row an array of scalar values
     * @param  bool  $withBom  prepend a UTF-8 BOM so Excel opens Arabic text correctly
     */
    protected function csvResponse(string $filename, array $headers, iterable $rows, bool $withBom = false): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $withBom) {
            $handle = fopen('php://output', 'wb');
            if ($withBom) {
                fwrite($handle, "\xEF\xBB\xBF");
            }
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
