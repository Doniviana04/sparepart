<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Validasi akses CRP berdasarkan session can_access_crp.
 */
class CrpAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('logged_in')) {
            return redirect()->to(base_url('login'))
                ->with('info', 'Silakan login terlebih dahulu.');
        }

        if (!(bool) (session()->get('can_access_crp') ?? false)) {
            return redirect()->to(base_url('home'))
                ->with('error', 'Akses CRP hanya untuk NPK yang terdaftar.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}
