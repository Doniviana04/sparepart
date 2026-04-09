<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Config\Database;

class CrpAdminNpkAccessSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $npks = ['2526', '0485', '1440'];
        $db = Database::connect('sparepart_price');

        foreach ($npks as $npk) {
            $existing = $db->table('crp_admin_npk_access')
                ->select('id')
                ->where('npk', $npk)
                ->get()
                ->getFirstRow('array');

            if ($existing !== null) {
                $db->table('crp_admin_npk_access')
                    ->where('id', $existing['id'])
                    ->update([
                        'is_active' => 1,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $db->table('crp_admin_npk_access')->insert([
                'npk' => $npk,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
