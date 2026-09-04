<?php
namespace App\Exporter;

use App\Contract\FileExporter;

class JsonFileExporter implements FileExporter
{
    public function export($records)
    {
        // TODO 7: Convert the records to readable JSON.
        return json_encode(________________, JSON_PRETTY_PRINT);
    }

    public function getExtension()
    {
        // TODO 8: Return the JSON file extension.
        return __________;
    }
}
