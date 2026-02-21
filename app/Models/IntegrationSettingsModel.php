<?php

namespace App\Models;

use CodeIgniter\Model;

class IntegrationSettingsModel extends Model
{
    protected $table = 'integration_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['last_fetch_time'];

    public function getLastFetchTime()
    {
        return $this->first()['last_fetch_time'] ?? null;
    }

    public function updateLastFetchTime($time)
    {
        return $this->update(1, ['last_fetch_time' => $time]);
    }
}