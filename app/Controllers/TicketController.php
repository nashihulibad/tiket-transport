<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TicketModel;
use App\Models\MasterPriceModel;

class TicketController extends BaseController
{
    protected TicketModel $ticketModel;
    protected MasterPriceModel $priceModel;

    public function __construct()
    {
        $this->ticketModel = new TicketModel();
        $this->priceModel  = new MasterPriceModel();
    }

    public function index()
    {
        $roleCode = session()->get('role_code');
        if ($roleCode === 'customer') {
            return redirect()->to('/')->with('error', 'Menu ini tidak boleh diakses oleh customer.');
        }

        return view('tickets/index', [
            'title' => 'Master Tiket'
        ]);
    }

    /**
     * Server-side DataTables
     */
    public function datatables()
    {
        $request = $this->request;

        $draw   = (int) $request->getGet('draw');
        $start  = (int) $request->getGet('start');
        $length = (int) $request->getGet('length');
        $search = $request->getGet('search')['value'] ?? '';

        // contoh session user
        $roleCode = session()->get('role_code');
        $regionId = session()->get('region_id');  // nullable

        $db = \Config\Database::connect();

        $builder = $db->table('tickets t')
            ->select("
                t.id, t.code, rg.name region_name,
                t.origin, t.destination, t.class,
                t.price, t.stock, t.no_polisi_kendaraan,
                t.date_keberangkatan, t.jam_keberangkatan
            ")
            ->join('regions rg', 'rg.id = t.region_id', 'left')
            ->where('t.deleted_at', null);

        if ($roleCode === 'regional_admin') {
            $builder->where('t.region_id', $regionId);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('t.code', $search)
                ->orLike('t.origin', $search)
                ->orLike('t.destination', $search)
                ->orLike('t.no_polisi_kendaraan', $search)
                ->groupEnd();
        }

        $recordsFiltered = $builder->countAllResults(false);

        $builder->orderBy('t.id', 'DESC');
        $builder->limit($length, $start);

        $rows = $builder->get()->getResultArray();

        $builderTotal = $db->table('tickets t')->where('t.deleted_at', null);

        if ($roleCode === 'regional_admin') {
            $builderTotal->where('t.region_id', $regionId);
        }

        $recordsTotal = $builderTotal->countAllResults();

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                $r['code'],
                $r['region_name'],
                $r['origin'],
                $r['destination'],
                $r['class'],
                number_format((float)$r['price'], 0, ',', '.'),
                $r['stock'],
                $r['date_keberangkatan'],
                $r['jam_keberangkatan'],
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

    /**
     * master price dropdown berdasarkan region & asal-tujuan-class
     */
    public function getMasterPrice()
    {
        $origin      = $this->request->getGet('origin');
        $destination = $this->request->getGet('destination');
        $class       = $this->request->getGet('class');
        $regionId    = (int) $this->request->getGet('region_id');

        if (!$origin || !$destination || !$class || !$regionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parameter tidak lengkap'
            ]);
        }

        $price = $this->priceModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('region_id', $regionId)
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('class', $class)
            ->first();

        if (!$price) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Master price tidak ditemukan'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'id' => (int)$price['id'],
                'price' => (float)$price['price'],
            ]
        ]);
    }

    public function show($id)
    {
        $roleCode      = session()->get('role_code');
        $adminRegionId = (int) session()->get('region_id');

        if ($roleCode === 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak punya akses']);
        }

        $ticket = $this->ticketModel->where('deleted_at', null)->find($id);
        if (!$ticket) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ticket tidak ditemukan']);
        }

        if ($roleCode === 'regional_admin' && (int)$ticket['region_id'] !== $adminRegionId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak boleh akses tiket daerah lain']);
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $ticket
        ]);
    }

    public function publicList()
    {
        if (session()->get('role_code') !== 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])
                ->setStatusCode(403);
        }

        $db = \Config\Database::connect();

        $tickets = $db->table('tickets t')
            ->select('t.id, t.code, t.origin, t.destination, t.class, t.price, t.stock, t.no_polisi_kendaraan, t.date_keberangkatan, t.jam_keberangkatan')
            ->where('t.deleted_at', null)
            ->orderBy('t.date_keberangkatan', 'ASC')
            ->orderBy('t.jam_keberangkatan', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $tickets
        ]);
    }

    public function store()
    {
        $roleCode = session()->get('role_code');
        $userId   = session()->get('user_id');

        if ($roleCode === 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Tidak punya akses']);
        }

        $post = $this->request->getPost();

        $regionId = (int)($post['region_id'] ?? 0);
        if ($regionId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Asal wajib dipilih']);
        }

        $db = \Config\Database::connect();
        $originRegion = $db->table('regions')
            ->select('id, code')
            ->where('id', $regionId)
            ->get()
            ->getRowArray();

        if ($roleCode === 'regional_admin') {
            $adminRegionId = (int)session()->get('region_id');

            if ($regionId !== $adminRegionId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Admin regional tidak boleh membuat tiket selain daerahnya'
                ]);
            }
        }

        if (!$originRegion) {
            return $this->response->setJSON(['success' => false, 'message' => 'Daerah asal invalid']);
        }

        if ($originRegion['code'] !== $post['origin']) {
            return $this->response->setJSON(['success' => false, 'message' => 'Asal tidak sesuai region']);
        }

        if ($post['origin'] === $post['destination']) {
            return $this->response->setJSON(['success' => false, 'message' => 'Asal dan tujuan tidak boleh sama']);
        }

        $code = 'TKT' . date('YmdHis') . rand(100, 999);

        $price = $this->priceModel->find((int)$post['master_price_id']);
        if (!$price) {
            return $this->response->setJSON(['success' => false, 'message' => 'Master price invalid']);
        }

        $this->ticketModel->insert([
            'code' => $code,
            'region_id' => $regionId,
            'origin' => $post['origin'],
            'destination' => $post['destination'],
            'class' => $post['class'],
            'master_price_id' => (int)$post['master_price_id'],
            'price' => $price['price'],
            'stock' => (int)$post['stock'],
            'no_polisi_kendaraan' => $post['no_polisi_kendaraan'],
            'date_keberangkatan' => $post['date_keberangkatan'],
            'jam_keberangkatan' => $post['jam_keberangkatan'],
            'created_by' => $userId,
        ]);

        return $this->response->setJSON(['success' => true, 'message' => 'Ticket berhasil dibuat']);
    }

    public function update($id)
    {
        $roleCode     = session()->get('role_code');
        $adminRegionId = (int) session()->get('region_id');
        $userId       = (int) session()->get('user_id');

        if ($roleCode === 'customer') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak punya akses'
            ]);
        }

        $ticket = $this->ticketModel->find($id);
        if (!$ticket) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ticket tidak ditemukan'
            ]);
        }

        if ($roleCode === 'regional_admin' && (int)$ticket['region_id'] !== $adminRegionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak boleh edit tiket daerah lain'
            ]);
        }

        $post = $this->request->getPost();

        $origin      = strtoupper(trim((string)($post['origin'] ?? '')));
        $destination = strtoupper(trim((string)($post['destination'] ?? '')));
        $class       = trim((string)($post['class'] ?? ''));

        if ($origin === '' || $destination === '' || $class === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Asal, tujuan dan class wajib diisi'
            ]);
        }

        if ($origin === $destination) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Asal dan tujuan tidak boleh sama'
            ]);
        }

        $db = \Config\Database::connect();
        $originRegion = $db->table('regions')
            ->select('id, code')
            ->where('code', $origin)
            ->get()
            ->getRowArray();

        if (!$originRegion) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Asal tidak valid'
            ]);
        }

        $newRegionId = (int)$originRegion['id'];

        if ($roleCode === 'regional_admin' && $newRegionId !== $adminRegionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Admin regional tidak boleh mengubah tiket ke daerah lain'
            ]);
        }

        $masterPrice = $this->priceModel
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('region_id', $newRegionId)
            ->where('origin', $origin)
            ->where('destination', $destination)
            ->where('class', $class)
            ->first();

        if (!$masterPrice) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Master price belum tersedia untuk route/class tersebut'
            ]);
        }

        $this->ticketModel->update($id, [
            'region_id' => $newRegionId,
            'origin' => $origin,
            'destination' => $destination,
            'class' => $class,

            'master_price_id' => (int)$masterPrice['id'],
            'price' => (float)$masterPrice['price'], // snapshot ikut terbaru master_price

            'stock' => (int)($post['stock'] ?? 0),
            'no_polisi_kendaraan' => trim((string)($post['no_polisi_kendaraan'] ?? '')),
            'date_keberangkatan' => $post['date_keberangkatan'] ?? null,
            'jam_keberangkatan' => $post['jam_keberangkatan'] ?? null,

            'updated_by' => $userId,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Ticket berhasil diupdate'
        ]);
    }

    public function delete($id)
    {
        $roleCode      = session()->get('role_code');
        $adminRegionId = (int) session()->get('region_id');

        if ($roleCode === 'customer') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak punya akses'
            ]);
        }

        $ticket = $this->ticketModel->find($id);
        if (!$ticket) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ticket tidak ditemukan'
            ]);
        }

        if ($roleCode === 'regional_admin' && (int)$ticket['region_id'] !== $adminRegionId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak boleh hapus tiket daerah lain'
            ]);
        }

        $this->ticketModel->delete($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Ticket berhasil dihapus'
        ]);
    }

}
