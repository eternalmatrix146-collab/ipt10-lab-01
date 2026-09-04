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
        if (self::$instance === null) {
            self::$instance = new AppSettings();
        }

        return self::$instance;
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
