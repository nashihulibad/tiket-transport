<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class RegionController extends BaseController
{
    public function list()
    {
        $db = \Config\Database::connect();

        $regions = $db->table('regions')
            ->select('id, code, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $regions
        ]);
    }
}
