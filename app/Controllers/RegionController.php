<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class RegionController extends BaseController
{
    public function list()
    {
        $db = \Config\Database::connect();

        // jika role nya regional admin, maka hanya muncul sesuai region nya saja

        $roleCode = session()->get('role_code');
        $userRegionCode = session()->get('region_code');
        if ($roleCode === 'regional_admin' && $userRegionCode) {
            $regions = $db->table('regions')
                ->select('id, code, name')
                ->where('code', $userRegionCode)
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        } else {    

        $regions = $db->table('regions')
            ->select('id, code, name')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $regions
        ]);
    }
}
