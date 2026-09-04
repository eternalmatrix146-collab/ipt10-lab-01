<?php
namespace App\Contract;

interface FileExporter
{
    public function export($records);
    public function getExtension();
}
