<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCrpAdminNpkAccessTable extends Migration
{
    // Pakai DB group yang sama dengan pengecekan akses CRP di login.
    protected $DBGroup = 'sparepart_price';

    public function up()
    {
        // Definisi kolom tabel whitelist NPK admin CRP.
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'npk' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        // Primary key dan unique key agar NPK tidak duplikat.
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('npk', 'uniq_crp_admin_npk');
        // Buat tabel jika belum ada.
        $this->forge->createTable('crp_admin_npk_access', true);
    }

    public function down()
    {
        // Rollback: hapus tabel whitelist NPK admin CRP.
        $this->forge->dropTable('crp_admin_npk_access', true);
    }
}
