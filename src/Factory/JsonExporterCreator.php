<?php
namespace App\Factory;

use App\Exporter\JsonFileExporter;

class JsonExporterCreator extends ExporterCreator
{
    public function createExporter()
    {
        return new JsonFileExporter();
    }
}
