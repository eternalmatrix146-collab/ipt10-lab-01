<?php
namespace App\Factory;

use App\Exporter\TextFileExporter;

class TextExporterCreator extends ExporterCreator
{
    public function createExporter()
    {
        return new TextFileExporter();
    }
}
