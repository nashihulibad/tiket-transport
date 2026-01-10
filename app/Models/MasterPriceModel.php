<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterPriceModel extends Model
{
    protected $table            = 'master_prices';
    protected $primaryKey       = 'id';

    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'region_id',
        'origin',
        'destination',
        'class',
        'price',
        'is_active',
        'created_by',
        'updated_by',
    ];
}
