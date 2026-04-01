<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter – periksa role/level user.
 *
 * Contoh:
 *   ['filter' => 'role:1,2,3']      -> hanya level 1,2,3
 *   ['filter' => 'role:admin,user'] -> kompatibel role string
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Kalau belum login, arahkan ke login
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'))
                ->with('info', 'Silakan login terlebih dahulu.');
        }

        // Jika filter dipasang tanpa argumen role, lewatkan
        if (empty($arguments)) {
            return;
        }

        $role = (string) (session()->get('role') ?? '');
        $level = session()->get('level');

        $allowed = array_values(array_filter(
            array_map(static fn($arg) => trim((string) $arg), $arguments),
            static fn($arg) => $arg !== ''
        ));

        if ($allowed === []) {
            return;
        }

        $allowedLevels = [];
        foreach ($allowed as $item) {
            if (ctype_digit($item)) {
                $allowedLevels[] = (int) $item;
            }
        }

        if ($allowedLevels !== [] && $level !== null && $level !== '' && in_array((int) $level, $allowedLevels, true)) {
            return;
        }

        if ($role !== '' && in_array($role, $allowed, true)) {
            return;
        }

        if ($allowedLevels !== [] || $role !== '') {
            return redirect()->to(base_url('home'))
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
     {
        // nothing
    }
}
