<?php

namespace App\Controllers;

use App\Models\M_Login;
use Config\Database;
use Firebase\JWT\JWT;

class AuthController extends BaseController
{
    private const KODE_JABATAN_ALLOWED = [1, 2, 3, 4, 5, 6, 7];
    private const KODE_JABATAN_EDIT_ACCESS = [1, 2, 3, 4, 5, 6];
    private const KODE_JABATAN_MONITOR_ACCESS = [1, 2, 3, 4, 5, 6, 7];
    // Tabel whitelist NPK untuk akses admin CRP.
    private const CRP_ACCESS_DB_GROUP = 'sparepart_price';
    private const CRP_ACCESS_TABLE = 'crp_admin_npk_access';

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
                ->with('error', 'Login gagal. Periksa kembali username atau password Anda.');
        }

        $kodeJabatanRaw = $authResult['kode_jabatan'] ?? null;
        if (!is_numeric($kodeJabatanRaw)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Login gagal. Akun Anda belum memiliki akses ke aplikasi ini.');
        }

        $kodeJabatan = (int) $kodeJabatanRaw;

        if (!in_array($kodeJabatan, self::KODE_JABATAN_ALLOWED, true)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Login gagal. Akun Anda belum memiliki akses ke aplikasi ini.');
        }

        $apiUsername = trim((string) ($authResult['username'] ?? $username));
        $name = trim((string) ($authResult['name'] ?? ''));

        if ($name === '') {
            $name = $apiUsername;
        }

        // Akses admin CRP wajib lolos kombinasi jabatan + NPK whitelist.
        $npk = $this->resolveSessionNpk($authResult, $apiUsername);
        $isWhitelistedNpk = $this->isCrpNpkAllowed($npk);
        $canEditCrp = in_array($kodeJabatan, self::KODE_JABATAN_EDIT_ACCESS, true) && $isWhitelistedNpk;
        $canAccessMonitorUser = in_array($kodeJabatan, self::KODE_JABATAN_MONITOR_ACCESS, true);
        $jabatan = trim((string) ($authResult['jabatan'] ?? ''));
        if ($jabatan === '') {
            $jabatan = 'Pengguna';
        }
        $sessionToken = $this->createSessionToken([
            'npk'            => $npk !== '' ? $npk : null,
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
            'npk'            => $npk !== '' ? $npk : null,
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

    private function isCrpNpkAllowed(string $npk): bool
    {
        // Jika NPK kosong maka otomatis tidak punya akses admin CRP.
        if ($npk === '') {
            return false;
        }

        try {
            $db = Database::connect(self::CRP_ACCESS_DB_GROUP);
            $row = $db->table(self::CRP_ACCESS_TABLE)
                ->select('npk')
                ->where('is_active', 1)
                ->get()
                ->getResultArray();

            if ($row === []) {
                return false;
            }

            $candidate = $this->canonicalizeNpk($npk);
            foreach ($row as $item) {
                $allowedNpk = $this->canonicalizeNpk((string) ($item['npk'] ?? ''));
                if ($allowedNpk !== '' && $allowedNpk === $candidate) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            log_message('error', 'Gagal cek akses CRP by NPK ({npk}): {message}', [
                'npk' => $npk,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizeNpk(string $npk): string
    {
        return strtoupper(trim($npk));
    }

    private function resolveSessionNpk(array $authResult, string $fallbackUsername): string
    {
        // Prioritas NPK dari API login.
        $npk = $this->normalizeNpk((string) ($authResult['npk'] ?? ''));

        if ($npk !== '') {
            return $npk;
        }

        // Fallback: gunakan username jika formatnya numerik.
        $fallbackUsername = $this->normalizeNpk($fallbackUsername);
        if ($fallbackUsername !== '' && ctype_digit($fallbackUsername)) {
            return $fallbackUsername;
        }

        return '';
    }

    private function canonicalizeNpk(string $npk): string
    {
        // Samakan format NPK (contoh 0485 dan 485 dianggap sama).
        $normalized = $this->normalizeNpk($npk);
        if ($normalized === '') {
            return '';
        }

        if (!ctype_digit($normalized)) {
            return $normalized;
        }

        $trimmed = ltrim($normalized, '0');

        return $trimmed === '' ? '0' : $trimmed;
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to(base_url('login'))
            ->with('info', 'Anda telah berhasil logout.');
    }
}
