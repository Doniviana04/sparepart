<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCrpAdminNpkAccessTable extends Migration
{
    protected $DBGroup = 'sparepart_price';

    public function up()
    {
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('npk', 'uniq_crp_admin_npk');
        $this->forge->createTable('crp_admin_npk_access', true);
    }

    public function down()
    {
        $this->forge->dropTable('crp_admin_npk_access', true);
    }
}
