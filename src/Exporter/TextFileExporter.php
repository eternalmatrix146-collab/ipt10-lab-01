<?php
namespace App\Exporter;

use App\Contract\FileExporter;

class TextFileExporter implements FileExporter
{
    public function export($records)
    {
        $lines = array();

        foreach ($records as $record) {
            $lines[] = $record['id'] . ' | ' . $record['service'] . ' | ' . $record['status'];
        }

        return implode(PHP_EOL, $lines);
    }

    public function getExtension()
    {
        return 'txt';
    }
}
