<?php namespace App\Models;

use CodeIgniter\Model;

class RingbaCallModel extends Model {
    protected $table = 'ringba_calls';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'call_id','timestamp','campaign_id','campaign_name',
        'payout','tier','duration','has_recording',
        'sentiment','summary','transcript_url','raw_json'
    ];
}
