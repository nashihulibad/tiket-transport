<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MasterPriceModel;

class MasterPriceController extends BaseController
{
    protected MasterPriceModel $model;

    public function __construct()
    {
        $this->model = new MasterPriceModel();
    }

    private function onlySuperadmin()
    {
        if (session()->get('role_code') !== 'superadmin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak punya akses'
            ])->setStatusCode(403);
        }
        return null;
    }

    public function index()
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        return view('master_prices/index', [
            'title' => 'Master Price'
        ]);
    }

    public function datatables()
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        $draw   = (int)($this->request->getGet('draw') ?? 1);
        $start  = (int)($this->request->getGet('start') ?? 0);
        $length = (int)($this->request->getGet('length') ?? 10);
        $search = $this->request->getGet('search')['value'] ?? '';

        $db = \Config\Database::connect();

        $builder = $db->table('master_prices mp')
            ->select('
                mp.id, rg.name region_name, rg.code region_code,
                mp.origin, mp.destination, mp.class, mp.price, mp.is_active
            ')
            ->join('regions rg', 'rg.id = mp.region_id', 'left')
            ->where('mp.deleted_at', null);

        if ($search !== '') {
            $builder->groupStart()
                ->like('rg.name', $search)
                ->orLike('rg.code', $search)
                ->orLike('mp.origin', $search)
                ->orLike('mp.destination', $search)
                ->orLike('mp.class', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $builder->orderBy('mp.id', 'DESC');
        $builder->limit($length, $start);
        $rows = $builder->get()->getResultArray();

        $recordsTotal = $db->table('master_prices mp')
            ->where('mp.deleted_at', null)
            ->countAllResults();

        $data = [];
        foreach ($rows as $r) {
            $activeBadge = ((int)$r['is_active'] === 1)
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>';

            $data[] = [
                $r['region_code'] . ' - ' . $r['region_name'],
                $r['origin'],
                $r['destination'],
                $r['class'],
                number_format((float)$r['price'], 0, ',', '.'),
                $activeBadge,
                '
                    <button class="btn btn-sm btn-warning btn-edit" data-id="'.$r['id'].'">Edit</button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="'.$r['id'].'">Delete</button>
                '
            ];
        }

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    public function show($id)
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        $row = $this->model->where('deleted_at', null)->find($id);
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $row]);
    }

    public function store()
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        $post = $this->request->getPost();
        $userId = (int)session()->get('user_id');

        $regionId    = (int)($post['region_id'] ?? 0);
        $origin      = strtoupper(trim((string)($post['origin'] ?? '')));
        $destination = strtoupper(trim((string)($post['destination'] ?? '')));
        $class       = trim((string)($post['class'] ?? ''));
        $price       = (float)($post['price'] ?? 0);
        $isActive    = (int)($post['is_active'] ?? 1);

        if ($regionId <= 0 || $origin === '' || $destination === '' || $class === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Input tidak lengkap']);
        }

        if ($origin === $destination) {
            return $this->response->setJSON(['success' => false, 'message' => 'Asal dan tujuan tidak boleh sama']);
        }

        if (!in_array($class, ['ekonomi', 'non-ekonomi', 'luxury'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Class tidak valid']);
        }

        // cek duplikasi
        $exists = $this->model
            ->where('deleted_at', null)
            ->where('region_id', $regionId)
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('class', $class)
            ->first();

        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data master price sudah ada untuk kombinasi tersebut'
            ]);
        }

        $this->model->insert([
            'region_id'    => $regionId,
            'origin'       => $origin,
            'destination'  => $destination,
            'class'        => $class,
            'price'        => $price,
            'is_active'    => $isActive,
            'created_by'   => $userId,
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Master price berhasil ditambahkan']);
    }

    public function update($id)
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        $row = $this->model->where('deleted_at', null)->find($id);
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $post = $this->request->getPost();
        $userId = (int)session()->get('user_id');

        $regionId    = (int)($post['region_id'] ?? 0);
        $origin      = strtoupper(trim((string)($post['origin'] ?? '')));
        $destination = strtoupper(trim((string)($post['destination'] ?? '')));
        $class       = trim((string)($post['class'] ?? ''));
        $price       = (float)($post['price'] ?? 0);
        $isActive    = (int)($post['is_active'] ?? 1);

        if ($regionId <= 0 || $origin === '' || $destination === '' || $class === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Input tidak lengkap']);
        }

        if ($origin === $destination) {
            return $this->response->setJSON(['success' => false, 'message' => 'Asal dan tujuan tidak boleh sama']);
        }

        if (!in_array($class, ['ekonomi', 'non-ekonomi', 'luxury'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Class tidak valid']);
        }

        $exists = $this->model
            ->where('deleted_at', null)
            ->where('region_id', $regionId)
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('class', $class)
            ->where('id !=', $id)
            ->first();

        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Duplikat: kombinasi origin/destination/class sudah ada'
            ]);
        }

        $this->model->update($id, [
            'region_id'    => $regionId,
            'origin'       => $origin,
            'destination'  => $destination,
            'class'        => $class,
            'price'        => $price,
            'is_active'    => $isActive,
            'updated_by'   => $userId,
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Master price berhasil diupdate']);
    }

    public function delete($id)
    {
        if ($resp = $this->onlySuperadmin()) return $resp;

        $row = $this->model->where('deleted_at', null)->find($id);
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan']);
        }

        $this->model->delete($id);

        return $this->response->setJSON(['success' => true, 'message' => 'Master price berhasil dihapus']);
    }
}
