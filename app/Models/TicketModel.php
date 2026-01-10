<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table            = 'tickets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'code',
        'region_id',
        'origin',
        'destination',
        'class',
        'master_price_id',
        'price',
        'stock',
        'no_polisi_kendaraan',
        'date_keberangkatan',
        'jam_keberangkatan',
        'created_by',
        'updated_by',
    ];
}
