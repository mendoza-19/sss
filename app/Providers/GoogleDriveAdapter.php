<?php

namespace App\Providers;

class GoogleDriveAdapter extends \Masbug\Flysystem\GoogleDriveAdapter
{
    public function getService()
    {
            return $this->service;
    }
}