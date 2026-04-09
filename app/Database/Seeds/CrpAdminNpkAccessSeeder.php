<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class CrpAdminNpkAccessSeeder extends Seeder
{
    public function run()
    {
        // Timestamp seragam untuk create/update data seed.
        $now = date('Y-m-d H:i:s');
        // Daftar NPK admin CRP awal (whitelist).
        $npks = ['2526', '0485', '1440'];
        // Gunakan DB group yang sama dengan pengecekan login.
        $db = Database::connect('sparepart_price');

        foreach ($npks as $npk) {
            // Cek apakah NPK sudah ada agar seeder aman dijalankan berulang.
            $existing = $db->table('crp_admin_npk_access')
                ->select('id')
                ->where('npk', $npk)
                ->get()
                ->getFirstRow('array');

            if ($existing !== null) {
                // Jika sudah ada, aktifkan kembali dan perbarui waktu update.
                $db->table('crp_admin_npk_access')
                    ->where('id', $existing['id'])
                    ->update([
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            // Jika belum ada, masukkan NPK baru ke whitelist admin CRP.
            $db->table('crp_admin_npk_access')->insert([
                'npk' => $npk,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
