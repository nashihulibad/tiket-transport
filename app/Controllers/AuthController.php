<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('is_logged_in')) {
            return redirect()->to('/');
        }

        return view('auth/login', [
            'title' => 'Login'
        ]);
    }

    public function loginProcess()
    {
        $email    = trim((string)$this->request->getPost('email'));
        $password = (string)$this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->with('error', 'Email dan password wajib diisi.');
        }

        $db = \Config\Database::connect();

        $user = $db->table('users u')
            ->select('u.*, r.code AS role_code')
            ->join('roles r', 'r.id = u.role_id')
            ->where('u.deleted_at', null)
            ->where('u.email', $email)
            ->get()
            ->getRowArray();

        if (!$user) {
            return redirect()->back()->with('error', 'Email tidak ditemukan.');
        }

        if ((int)$user['is_active'] !== 1) {
            return redirect()->back()->with('error', 'User tidak aktif.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Password salah.');
        }

        session()->set([
            'is_logged_in' => true,
            'user_id'      => (int)$user['id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'role_code'    => $user['role_code'],
            'region_id'    => $user['region_id'] ? (int)$user['region_id'] : null,
        ]);

        // redirect sesuai role
        if ($user['role_code'] === 'customer') {
            return redirect()->to('/orders')->with('success', 'Login berhasil.');
        }

        return redirect()->to('/tickets')->with('success', 'Login berhasil.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logout berhasil.');
    }
}
