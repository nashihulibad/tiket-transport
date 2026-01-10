<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    protected OrderModel $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    private function onlyCustomer()
    {
        if (session()->get('role_code') !== 'customer') {
            return redirect()->to('/')->with('error', 'Menu ini hanya untuk customer.');
        }
        return null;
    }

    public function index()
    {
        if ($resp = $this->onlyCustomer()) return $resp;

        return view('orders/index', [
            'title' => 'Order Tiket'
        ]);
    }

    /**
     * list order customer (AJAX)
     */
    public function list()
    {
        if (session()->get('role_code') !== 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $customerId = (int) session()->get('user_id');
        $db = \Config\Database::connect();

        $orders = $db->table('orders o')
            ->select('
                o.id, o.order_code, o.qty, o.price, o.subtotal, o.status, o.created_at,
                t.code AS ticket_code, t.origin, t.destination, t.class, t.date_keberangkatan, t.jam_keberangkatan
            ')
            ->join('tickets t', 't.id = o.ticket_id', 'left')
            ->where('o.deleted_at', null)
            ->where('o.customer_id', $customerId)
            ->orderBy('o.id', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'success' => true,
            'data' => $orders
        ]);
    }

   
    public function store()
    {
        if (session()->get('role_code') !== 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])
                ->setStatusCode(403);
        }

        $customerId = (int) session()->get('user_id');

        $ticketId = (int) ($this->request->getPost('ticket_id') ?? 0);
        $qtyAdd   = (int) ($this->request->getPost('qty') ?? 1);
        if ($qtyAdd <= 0) $qtyAdd = 1;

        if ($ticketId <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ticket invalid']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $ticket = $db->query("
            SELECT * FROM tickets
            WHERE id = ? AND deleted_at IS NULL
        ", [$ticketId])->getRowArray();

        if (!$ticket) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Ticket tidak ditemukan']);
        }

        if ((int)$ticket['stock'] < $qtyAdd) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Stok tidak cukup']);
        }

        $existing = $db->query("
            SELECT * FROM orders
            WHERE deleted_at IS NULL
            AND customer_id = ?
            AND ticket_id = ?
            AND status = 'paid'
            LIMIT 1
        ", [$customerId, $ticketId])->getRowArray();

        $price = (float)$ticket['price'];

        if ($existing) {
            $newQty = ((int)$existing['qty']) + $qtyAdd;
            $newSubtotal = $price * $newQty;

            $db->table('orders')
                ->where('id', (int)$existing['id'])
                ->update([
                    'qty' => $newQty,
                    'price' => $price,
                    'subtotal' => $newSubtotal,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $customerId,
                ]);
        } else {
            // 3B) insert order baru
            $orderCode = 'ORD' . date('YmdHis') . rand(100, 999);
            $subtotal = $price * $qtyAdd;

            $this->orderModel->insert([
                'order_code' => $orderCode,
                'customer_id' => $customerId,
                'ticket_id' => $ticketId,
                'qty' => $qtyAdd,
                'price' => $price,
                'subtotal' => $subtotal,
                'status' => 'paid',
                'created_by' => $customerId,
            ]);
        }

        // 4) kurangi stock ticket
        $db->table('tickets')
            ->where('id', $ticketId)
            ->set('stock', 'stock - ' . $qtyAdd, false)
            ->update();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal membuat order']);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => $existing ? 'Qty order berhasil ditambah' : 'Order berhasil dibuat'
        ]);
    }

    /**
     * cancel order: hapus order + balikin stock
     */
    public function cancel($id)
    {
        if (session()->get('role_code') !== 'customer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $customerId = (int) session()->get('user_id');
        $orderId = (int) $id;

        $db = \Config\Database::connect();
        $db->transStart();

        $order = $db->query("
            SELECT * FROM orders
            WHERE id = ? AND deleted_at IS NULL AND customer_id = ?
        ", [$orderId, $customerId])->getRowArray();

        if (!$order) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Order tidak ditemukan']);
        }

        $db->table('tickets')
            ->where('id', (int)$order['ticket_id'])
            ->set('stock', 'stock + ' . (int)$order['qty'], false)
            ->update();

        $this->orderModel->delete($orderId);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal cancel order']);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Order berhasil dicancel']);
    }
}
