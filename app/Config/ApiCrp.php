<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class ApiCrp extends BaseConfig
{
    /**
     * URL dasar API CRP
     * Di .env: api.baseURL = https://...
     */
    public string $baseURL = '';

    /**
     * Bearer token untuk autentikasi API
     * Di .env: api.token = <token>
     */
    public string $token = '';

    /**
     * Timeout request dalam detik
     * Di .env: api.timeout = 10
     */
    public int $timeout = 10;

    public function __construct()
    {
        parent::__construct();

        // Baca langsung dari .env menggunakan env() helper CI4
        $this->baseURL = env('api.baseURL', $this->baseURL);
        $this->token   = env('api.token',   $this->token);
        $this->timeout = (int) env('api.timeout', $this->timeout);
    }
}
