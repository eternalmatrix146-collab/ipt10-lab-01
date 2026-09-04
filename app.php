<?php
require __DIR__ . '/vendor/autoload.php';

use App\Config\AppSettings;
use App\Factory\TextExporterCreator;
use App\Factory\JsonExporterCreator;

$records = array(
    array('id' => 101, 'service' => 'Laptop check', 'status' => 'Done'),
    array('id' => 102, 'service' => 'Printer setup', 'status' => 'Done'),
    array('id' => 103, 'service' => 'Account reset', 'status' => 'Done')
);

$format = isset($argv[1]) ? strtolower($argv[1]) : 'txt';

if ($format == 'txt') {
    $creator = new TextExporterCreator();
} elseif ($format == 'json') {
    $creator = new JsonExporterCreator();
} else {
    echo 'Use txt or json.' . PHP_EOL;
    exit;
}

$settings = AppSettings::getInstance();
$path = $creator->save($records, 'daily_report');

echo $settings->getApplicationName() . PHP_EOL;
echo 'Report saved to: ' . $path . PHP_EOL;
