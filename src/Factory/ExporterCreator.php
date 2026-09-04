<?php
namespace App\Factory;

use App\Config\AppSettings;

abstract class ExporterCreator
{
    abstract public function createExporter();

    public function save($records, $baseName)
    {
        $exporter = $this->createExporter();
        $settings = AppSettings::getInstance();
        $directory = $settings->getOutputDirectory();

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory . '/' . $baseName . '.' . $exporter->getExtension();

        file_put_contents($path, $exporter->export($records));

        return $path;
    }
}
