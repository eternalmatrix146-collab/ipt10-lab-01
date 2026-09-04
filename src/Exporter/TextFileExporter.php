<?php
namespace App\Exporter;

use App\Contract\FileExporter;

class TextFileExporter implements FileExporter
{
    public function export($records)
    {
        $lines = array();

        foreach ($records as $record) {
            // TODO 4: Add one readable line to $lines.
            // Format : ID | Service | Status
            $lines[] = ______________________________;
        }

        // TODO 5: Join the lines using PHP_EOL.
        return ______________________________;
    }

    public function getExtension()
    {
        // TODO 6: Return the text-file extension.
        return __________;
    }
}
