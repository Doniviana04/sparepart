<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCrpAdjustmentDailyCacheTable extends Migration
{
    // Gunakan DB group CRP yang sama dengan data dan whitelist akses.
    protected $DBGroup = 'sparepart_price';

    public function up()
    {
        $this->forge->addField([
            'cache_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'cache_year' => [
                'type' => 'SMALLINT',
                'constraint' => 4,
                'unsigned' => false,
            ],
            'cache_month' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'unsigned' => false,
            ],
            'record_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'payload_json' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'fetched_at' => [
                'type' => 'DATETIME',
                'null' => true,
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

        $this->forge->addKey('cache_date', true);
        $this->forge->addKey(['cache_year', 'cache_month']);
        $this->forge->createTable('crp_adjustment_daily_cache', true);
    }

    public function down()
    {
        $this->forge->dropTable('crp_adjustment_daily_cache', true);
    }
}
