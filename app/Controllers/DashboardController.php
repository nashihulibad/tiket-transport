<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $roleCode = session()->get('role_code');

        if ($roleCode === 'customer') {
            return redirect()->to('/tickets');
        }

        return redirect()->to('/tickets');
    }
}
