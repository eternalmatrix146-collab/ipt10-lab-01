<?php
namespace App\Exporter;

use App\Contract\FileExporter;

class JsonFileExporter implements FileExporter
{
    public function export($records)
    {
        return json_encode($records, JSON_PRETTY_PRINT);
    }

    public function getExtension()
    {
        return 'json';
    }
}
