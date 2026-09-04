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

        $path = $directory . '/' . $baseName . '.'
            . $exporter->getExtension();

        // TODO 9: Export the records and save them to $path.
        file_put_contents($path, ___________________________);

        return $path;
    }
}
