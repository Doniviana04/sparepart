<?php

namespace App\Controllers;

use App\Models\M_Login;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    private const KODE_JABATAN_ALLOWED = [1, 2, 3, 4, 5, 6, 7];
    private const KODE_JABATAN_EDIT_ACCESS = [1, 2, 3, 4, 5, 6];
    private const KODE_JABATAN_MONITOR_ACCESS = [1, 2, 3, 4, 5, 6, 7];

    protected M_Login $loginModel;

    public function __construct()
    {
        $this->loginModel = new M_Login();
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(base_url('home'));
        }

        return view('auth/login');
    }

    public function doLogin()
    {
        $username = trim((string) ($this->request->getPost('username') ?? ''));
        $password = (string) ($this->request->getPost('password') ?? '');

        if ($username === '' || $password === '') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Username dan password wajib diisi.');
        }

        $authResult = $this->loginModel->authenticatePortalUser($username, $password);

        if (!($authResult['success'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->with('error', (string) ($authResult['message'] ?? 'Username atau password tidak valid.'));
        }

        $kodeJabatanRaw = $authResult['kode_jabatan'] ?? null;
        if (!is_numeric($kodeJabatanRaw)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kode jabatan tidak ditemukan. Hubungi administrator.');
        }

        $kodeJabatan = (int) $kodeJabatanRaw;

        if (!in_array($kodeJabatan, self::KODE_JABATAN_ALLOWED, true)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Kode jabatan akun tidak diizinkan untuk mengakses aplikasi ini.');
        }

        $apiUsername = trim((string) ($authResult['username'] ?? $username));
        $name = trim((string) ($authResult['name'] ?? ''));

        if ($name === '') {
            $name = $apiUsername;
        }

        $canEditCrp = in_array($kodeJabatan, self::KODE_JABATAN_EDIT_ACCESS, true);
        $canAccessMonitorUser = in_array($kodeJabatan, self::KODE_JABATAN_MONITOR_ACCESS, true);
        $jabatan = trim((string) ($authResult['jabatan'] ?? ''));
        if ($jabatan === '') {
            $jabatan = 'Pengguna';
        }
        $sessionToken = $this->createSessionToken([
            'npk'            => $authResult['npk'] ?? null,
            'nama'           => $name,
            'jabatan'        => $jabatan,
            'kode_jabatan'   => $kodeJabatan,
            'username'       => $apiUsername,
            'departement'    => $authResult['departement'] ?? null,
            'id_departement' => $authResult['id_departement'] ?? null,
            'is_login'       => $authResult['is_login'] ?? true,
            'level'          => $kodeJabatan,
        ]);

        if (!($sessionToken['success'] ?? false)) {
            return redirect()->back()
                ->withInput()
                ->with('error', (string) ($sessionToken['message'] ?? 'Gagal membuat session token.'));
        }

        session()->regenerate();
        session()->set([
            'logged_in'      => true,
            'is_login'       => true,
            'session_token'  => $sessionToken['token'] ?? null,
            'jwt_enabled'    => (bool) ($sessionToken['enabled'] ?? false),
            'username'       => $apiUsername,
            'name'           => $name,
            'nama'           => $name,
            'jabatan'        => $jabatan,
            'level'          => $kodeJabatan,
            'role'           => $canEditCrp ? 'level_1_6' : 'level_7',
            'can_access_crp' => $canEditCrp,
            'can_edit_crp'   => $canEditCrp,
            'can_access_monitor_user' => $canAccessMonitorUser,
            'npk'            => $authResult['npk'] ?? null,
            'kode_jabatan'   => $kodeJabatan,
            'departement'    => $authResult['departement'] ?? null,
            'id_departement' => $authResult['id_departement'] ?? null,
            'api_is_login'   => $authResult['is_login'] ?? null,
        ]);

        return redirect()->to(base_url('home'));
    }

    public function loginProcess()
    {
        return $this->doLogin();
    }

    private function createSessionToken(array $claims): array
    {
        $secretKey = trim((string) env('JWT_SECRET_KEY', ''));

        if ($secretKey === '') {
            log_message('warning', 'JWT_SECRET_KEY kosong. Login dilanjutkan tanpa session_token JWT.');

            return [
                'success' => true,
                'token'   => null,
                'enabled' => false,
            ];
        }

        $payload = [
            'npk'            => $claims['npk'] ?? null,
            'nama'           => $claims['nama'] ?? null,
            'jabatan'        => $claims['jabatan'] ?? null,
            'kode_jabatan'   => $claims['kode_jabatan'] ?? null,
            'username'       => $claims['username'] ?? null,
            'departement'    => $claims['departement'] ?? null,
            'id_departement' => $claims['id_departement'] ?? null,
            'is_login'       => (bool) ($claims['is_login'] ?? true),
            'level'          => $claims['level'] ?? null,
            'iat'            => time(),
            'exp'            => time() + 86400,
        ];

        try {
            $token = JWT::encode($payload, $secretKey, 'HS256');

            return [
                'success' => true,
                'token'   => $token,
                'enabled' => true,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'JWT encode error: {message}', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Gagal membuat JWT token sesi.',
            ];
        }
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(base_url('login'))
            ->with('info', 'Anda telah berhasil logout.');
    }
}
