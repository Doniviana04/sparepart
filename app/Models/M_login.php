<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Services;

class M_Login extends Model
{
    private const LOGIN_API_URL = 'https://portal3.incoe.astra.co.id/production_control_v2/public/api/login';
    private const LOGIN_API_TIMEOUT_SECONDS = 5;
    private const LOGIN_API_RETRY = 0;
    protected $db1;
    protected $dbProdControl;
    protected array $fallbackUserConnections = [];

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        try {
            $this->db1 = \Config\Database::connect('henkaten');
        } catch (\Throwable $e) {
            $this->db1 = null;
            log_message('debug', 'DB group henkaten tidak tersedia: {message}', ['message' => $e->getMessage()]);
        }

        try {
            $this->dbProdControl = \Config\Database::connect('prod_control');
        } catch (\Throwable $e) {
            $this->dbProdControl = null;
            log_message('debug', 'DB group prod_control tidak tersedia: {message}', ['message' => $e->getMessage()]);
        }

        $this->bootstrapFallbackUserConnections();
    }

    public function cek_login($username, $password)
    {
        // $query = $this->db->query('SELECT * FROM users WHERE username = \''.$username.'\' AND password = \''.$password.'\'');

        // return $query->getRowArray();
        $sql = 'SELECT * FROM users WHERE username = ? AND password = ?';
        $query = $this->db->query($sql, [$username, $password]);
        return $query->getRowArray();
    }

    public function cek_login_api($username, $password)
    {
        $sql = 'WITH data_karyawan AS (
            SELECT master_data_karyawan.*, users.username, users.password, divisi.divisi, departement.departement, section.section, sub_section.sub_section
            FROM master_data_karyawan
            LEFT JOIN users ON users.npk = master_data_karyawan.npk
            LEFT JOIN divisi ON master_data_karyawan.id_divisi = divisi.id_divisi
            LEFT JOIN departement ON master_data_karyawan.id_departement = departement.id_departement
            LEFT JOIN section ON master_data_karyawan.id_section = section.id_section
            LEFT JOIN sub_section ON master_data_karyawan.id_sub_section = sub_section.id_sub_section
            WHERE master_data_karyawan.status_karyawan = ? or master_data_karyawan.status_karyawan = ?
            UNION
            SELECT master_data_karyawan_subcount.*, users.username, users.password, divisi.divisi, departement.departement, section.section, sub_section.sub_section
            FROM master_data_karyawan_subcount
            LEFT JOIN users ON users.npk = master_data_karyawan_subcount.npk
            LEFT JOIN divisi ON master_data_karyawan_subcount.id_divisi = divisi.id_divisi
            LEFT JOIN departement ON master_data_karyawan_subcount.id_departement = departement.id_departement
            LEFT JOIN section ON master_data_karyawan_subcount.id_section = section.id_section
            LEFT JOIN sub_section ON master_data_karyawan_subcount.id_sub_section = sub_section.id_sub_section
        )
        SELECT * FROM data_karyawan WHERE username = ? AND password = ?';
        $query = $this->db1->query($sql, ['cbi', 'cbi_dev', $username, $password]);
        return $query->getRowArray();
    }

    public function cek_login_npk_api($npk)
    {
        // $query = $this->db1->query('SELECT master_data_karyawan.*, users.username, users.password, divisi.divisi, departement.departement, section.section, sub_section.sub_section
        //                             FROM master_data_karyawan
        //                             LEFT JOIN users ON users.npk = master_data_karyawan.npk
        //                             LEFT JOIN divisi ON master_data_karyawan.id_divisi = divisi.id_divisi
        //                             LEFT JOIN departement ON master_data_karyawan.id_departement = departement.id_departement
        //                             LEFT JOIN section ON master_data_karyawan.id_section = section.id_section
        //                             LEFT JOIN sub_section ON master_data_karyawan.id_sub_section = sub_section.id_sub_section
        //                             WHERE master_data_karyawan.npk = '.$npk.'');

        // return $query->getRowArray();
        $sql = 'SELECT master_data_karyawan.*, users.username, users.password, divisi.divisi, departement.departement, section.section, sub_section.sub_section
            FROM master_data_karyawan
            LEFT JOIN users ON users.npk = master_data_karyawan.npk
            LEFT JOIN divisi ON master_data_karyawan.id_divisi = divisi.id_divisi
            LEFT JOIN departement ON master_data_karyawan.id_departement = departement.id_departement
            LEFT JOIN section ON master_data_karyawan.id_section = section.id_section
            LEFT JOIN sub_section ON master_data_karyawan.id_sub_section = sub_section.id_sub_section
            WHERE master_data_karyawan.npk = ?';
        $query = $this->db1->query($sql, [$npk]);
        return $query->getRowArray();
    }

    public function check_email($email)
    {
        // $query = $this->db1->query('SELECT npk FROM master_data_karyawan
        //                             WHERE email = \'' . $email . '\'
        //                         ');

        // return $query->getResultArray();
        $sql = 'SELECT npk FROM master_data_karyawan WHERE email = ?';
        $query = $this->db1->query($sql, [$email]);
        return $query->getResultArray();
    }

    public function get_data_login($npk)
    {
        // $query = $this->db1->query('SELECT username FROM users
        //                             WHERE npk = \'' . $npk . '\'
        //                         ');

        // return $query->getRowArray();
        $sql = 'SELECT username FROM users WHERE npk = ?';
        $query = $this->db1->query($sql, [$npk]);
        return $query->getRowArray();
    }

    public function update_data($npk, $data)
    {
        $builder = $this->db1->table('users');
        $builder->where('npk', $npk);
        $builder->update($data);
        return;
    }

    public function authenticatePortalUser(string $username, string $password): array
    {
        // OPTIMASI: Cek lokal dulu (< 100ms) sebelum API (~5s timeout)
        $localUser = $this->findLocalUser($username, $password);
        $localLevel = $this->extractLevelFromLocalUser($localUser);

        if ($localLevel !== null && is_array($localUser)) {
            log_message('info', 'Login lokal berhasil untuk username: {username}', ['username' => $username]);

            return [
                'success'         => true,
                'message'         => 'Login berhasil (lokal).',
                'level'           => $localLevel,
                'username'        => (string) ($localUser['username'] ?? $username),
                'name'            => (string) ($localUser['nama'] ?? $username),
                'npk'             => $localUser['npk'] ?? null,
                'jabatan'         => (string) ($localUser['jabatan'] ?? ''),
                'kode_jabatan'    => $localUser['kode_jabatan'] ?? null,
                'departement'     => $localUser['departement'] ?? ($localUser['departemen'] ?? null),
                'id_departement'  => $localUser['id_departement'] ?? null,
                'is_login'        => true,
            ];
        }

        // Fallback ke API hanya jika lokal tidak ketemu
        $apiResult = $this->requestPortalLoginApi($username, $password);
        $isTimeout = (bool) ($apiResult['timeout'] ?? false);
        $payload = $apiResult['payload'] ?? [];

        if ($isTimeout) {
            return [
                'success' => false,
                'message' => 'Login gagal: database lokal tidak aktif dan API server sedang tidak responsif.',
            ];
        }

        if (($apiResult['success'] ?? false) !== true) {
            return [
                'success' => false,
                'message' => (string) ($apiResult['message'] ?? 'Username atau password tidak valid.'),
            ];
        }

        try {
            $statusCode = (int) ($apiResult['statusCode'] ?? 0);

            if (!is_array($payload)) {
                return [
                    'success' => false,
                    'message' => 'Respons API login tidak valid.',
                ];
            }

            if ($statusCode < 200 || $statusCode >= 300 || $this->isApiMarkedAsFailed($payload)) {
                return [
                    'success' => false,
                    'message' => $this->extractApiMessage($payload, 'Username atau password tidak valid.'),
                ];
            }

            $userData = $this->extractUserData($payload);
            $level = $this->extractLevel($userData, $payload);

            if ($level === null) {
                log_message('error', 'Level user tidak ditemukan dari API. Username: {username}', ['username' => $username]);

                return [
                    'success' => false,
                    'message' => 'Level user dari API tidak ditemukan.',
                ];
            }

            return [
                'success'         => true,
                'message'         => 'Login berhasil (API).',
                'level'           => $level,
                'username'        => $this->extractUserName($userData, $username),
                'name'            => $this->extractDisplayName($userData) ?: $username,
                'npk'             => $this->extractScalarValue($userData, $payload, ['npk', 'NPK']) ?? null,
                'jabatan'         => (string) ($this->extractScalarValue($userData, $payload, ['jabatan', 'nama_jabatan', 'job_title', 'position']) ?? ''),
                'kode_jabatan'    => $this->extractScalarValue($userData, $payload, ['kode_jabatan', 'kodeJabatan']) ?? null,
                'departement'     => $this->extractScalarValue($userData, $payload, ['departement', 'department', 'departemen']) ?? null,
                'id_departement'  => $this->extractScalarValue($userData, $payload, ['id_departement', 'id_department']) ?? null,
                'is_login'        => $this->extractScalarValue($userData, $payload, ['is_login', 'isLogin']) ?? true,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Login API model error: {message}', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server login API.',
            ];
        }
    }

    private function requestPortalLoginApi(string $username, string $password): array
    {
        try {
            $client = Services::curlrequest([
                'timeout'         => self::LOGIN_API_TIMEOUT_SECONDS,
                'connect_timeout' => 3,
                'http_errors'     => false,
                'verify'          => false,
            ]);

            $response = $client->post(self::LOGIN_API_URL, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $payload = json_decode((string) $response->getBody(), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            return [
                'success'    => true,
                'timeout'    => false,
                'statusCode' => $response->getStatusCode(),
                'payload'    => $payload,
            ];
        } catch (\Throwable $e) {
            if ($this->isTimeoutException($e)) {
                log_message('warning', 'Login API timeout setelah {timeout}s. Username: {username}', [
                    'timeout'  => self::LOGIN_API_TIMEOUT_SECONDS,
                    'username' => $username,
                ]);

                return [
                    'success' => false,
                    'timeout' => true,
                    'message' => 'Login API timeout.',
                ];
            }

            log_message('error', 'Login API connection error: {message}', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'timeout' => false,
                'message' => 'Gagal terhubung ke server login API.',
            ];
        }
    }

    private function isTimeoutException(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'operation timed out') || str_contains($message, 'connection timed out') || str_contains($message, ' 28 ');
    }

    private function isApiMarkedAsFailed(array $payload): bool
    {
        foreach (['status', 'success'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_bool($value)) {
                return $value === false;
            }

            if (is_numeric($value)) {
                return (int) $value === 0;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['false', 'failed', 'error', '0', 'unauthorized'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractApiMessage(array $payload, string $fallback): string
    {
        foreach (['message', 'msg', 'error', 'errors'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_array($value)) {
                $first = reset($value);
                if (is_string($first) && trim($first) !== '') {
                    return trim($first);
                }
            }
        }

        return $fallback;
    }

    private function extractUserData(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (is_array($data)) {
            if (isset($data['user']) && is_array($data['user'])) {
                if ($this->isListArray($data['user']) && isset($data['user'][0]) && is_array($data['user'][0])) {
                    return $data['user'][0];
                }

                return $data['user'];
            }

            // Jika data berbentuk list: [{...}] ambil elemen pertama.
            if ($this->isListArray($data) && isset($data[0]) && is_array($data[0])) {
                return $data[0];
            }

            return $data;
        }

        if (isset($payload['user']) && is_array($payload['user'])) {
            if ($this->isListArray($payload['user']) && isset($payload['user'][0]) && is_array($payload['user'][0])) {
                return $payload['user'][0];
            }

            return $payload['user'];
        }

        return $payload;
    }

    private function extractLevel(array $userData, array $payload): ?int
    {
        $keys = ['level', 'LEVEL', 'user_level', 'level_user', 'id_level', 'role_level', 'kode_jabatan', 'kodeJabatan', 'line', 'LINE', 'otorisasi'];

        $level = $this->extractNumericScalarValue($userData, $keys);
        if ($level !== null) {
            return $level;
        }

        return $this->extractNumericScalarValue($payload, $keys);
    }

    private function extractDisplayName(array $userData): string
    {
        foreach (['name', 'nama', 'full_name', 'fullname'] as $key) {
            if (array_key_exists($key, $userData) && is_string($userData[$key])) {
                $value = trim($userData[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function extractUserName(array $userData, string $fallback): string
    {
        foreach (['username', 'user_name', 'nik', 'npk'] as $key) {
            if (array_key_exists($key, $userData) && is_string($userData[$key])) {
                $value = trim($userData[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    private function extractScalarValue(array $userData, array $payload, array $keys): mixed
    {
        $value = $this->extractValueByKeysRecursive($userData, $keys);
        if ($value !== null) {
            return $value;
        }

        return $this->extractValueByKeysRecursive($payload, $keys);
    }

    private function extractNumericScalarValue(array $source, array $keys): ?int
    {
        $value = $this->extractValueByKeysRecursive($source, $keys);

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function extractValueByKeysRecursive(array $source, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                return $source[$key];
            }
        }

        foreach ($source as $value) {
            if (!is_array($value)) {
                continue;
            }

            $found = $this->extractValueByKeysRecursive($value, $keys);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function findLocalUser(string $username, string $password): ?array
    {
        // OPTIMASI: Hanya cek DB yang paling mungkin punya data user valid, early return kalau ketemu
        foreach ($this->fallbackUserConnections as $group => $conn) {
            if ($conn === null) {
                continue;
            }

            try {
                // Cek dengan username + password langsung
                $row = $conn->table('users')
                    ->select('*')
                    ->where('username', $username)
                    ->where('password', $password)
                    ->limit(1)
                    ->get()
                    ->getRowArray();

                if (is_array($row) && !empty($row)) {
                    log_message('debug', 'findLocalUser berhasil di DB group: {group}', ['group' => $group]);
                    return $row;
                }
            } catch (\Throwable $e) {
                log_message('debug', 'findLocalUser {group} failed: {message}', [
                    'group'   => $group,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    private function bootstrapFallbackUserConnections(): void
    {
        $this->fallbackUserConnections = [];

        // Prioritas koneksi yang paling mungkin berisi tabel users otorisasi login.


            // Prioritas koneksi Login: default (prod_control_v2_dev) → henkaten → prod_control
            // Tidak check sparepart_price, itu hanya untuk CRP admin NPK whitelist!
        
            if ($this->db !== null) {
                $this->registerFallbackConnection('default', $this->db);
            }

            if ($this->db1 !== null) {
                $this->registerFallbackConnection('henkaten', $this->db1);
            }

            if ($this->dbProdControl !== null) {
                $this->registerFallbackConnection('prod_control', $this->dbProdControl);
            }

            // Fallback tambahan jika ketiga di atas gagal
            foreach (['prod_control_sqlsrv', 'prod_control_infor'] as $group) {
                if (isset($this->fallbackUserConnections[$group])) {
                    continue;
                }

                try {
                    $this->registerFallbackConnection($group, \Config\Database::connect($group));
                } catch (\Throwable $e) {
                    log_message('debug', 'DB group {group} tidak tersedia untuk fallback login: {message}', [
                        'group'   => $group,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
    }

    private function registerFallbackConnection(string $group, $connection): void
    {
        if ($connection === null) {
            return;
        }

        $this->fallbackUserConnections[$group] = $connection;
    }

    private function extractLevelFromLocalUser(?array $localUser): ?int
    {
        if (!is_array($localUser)) {
            return null;
        }

        foreach (['level', 'line', 'otorisasi', 'kode_jabatan', 'LEVEL', 'LINE'] as $key) {
            if (!array_key_exists($key, $localUser)) {
                continue;
            }

            $value = $localUser[$key];
            if (is_string($value)) {
                $value = trim($value);
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function isListArray(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
