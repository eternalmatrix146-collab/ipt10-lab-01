<?php
namespace App\Config;

class AppSettings
{
    private static $instance = null;
    private $applicationName;
    private $outputDirectory;

    private function __construct()
    {
        $this->applicationName = 'FileFlow Report Exporter';
        $this->outputDirectory = __DIR__ . '/../../output';
    }

    public static function getInstance()
    {
        // TODO 1: Check whether the instance is still null.
        if (_______________________________) {
            // TODO 2: Create and store one AppSettings object.
            ________________________________;
        }

        // TODO 3: Return the stored object.
        return ____________________________;
    }

    public function getApplicationName()
    {
        return $this->applicationName;
    }

    public function getOutputDirectory()
    {
        return $this->outputDirectory;
    }
}
