<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username'     => 'sa',
        'password'     => 'Doni170904',
        'database'     => 'production_control_v2_dev',
        'DBDriver'     => 'SQLSRV',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => 'SQL_Latin1_General_CP1_CI_AS',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => '',
    ];

/*
    |--------------------------------------------------------------------------
    | SQLSRV
    |--------------------------------------------------------------------------
    */
    public array $sqlsrv = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'production_control_v2_dev',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];


    /*
    |--------------------------------------------------------------------------
    | PROD CONTROL
    |--------------------------------------------------------------------------
    */
    public array $prod_control = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'production_control_v2_dev',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];

    /*
    |--------------------------------------------------------------------------
    | PROD CONTROL SQLSRV
    |--------------------------------------------------------------------------
    */
    public array $prod_control_sqlsrv = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'production_control_v2_dev',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];

    /*
    |--------------------------------------------------------------------------
    | PROD CONTROL INFOR
    |--------------------------------------------------------------------------
    */
    public array $prod_control_infor = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'production_control_v2_dev',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];

    /*
    |--------------------------------------------------------------------------
    | HENKATEN
    |--------------------------------------------------------------------------
    */
    public array $henkaten = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'henkaten_assy_dev',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];


    /*
    |--------------------------------------------------------------------------
    | DATA PRICE SPAREPART
    |--------------------------------------------------------------------------
    */
    public array $sparepart_price = [
        'DSN'      => '',
        'hostname' => 'LAPTOP-ANM932EJ\SQLEXPRESS',
        'username' => 'sa',
        'password' => 'Doni170904',
        'database' => 'sparepart_price',
        'DBDriver' => 'SQLSRV',
        'DBDebug'  => true,
    ];

    //    /**
    //     * Sample database connection for SQLite3.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'database'    => 'database.db',
    //        'DBDriver'    => 'SQLite3',
    //        'DBPrefix'    => '',
    //        'DBDebug'     => true,
    //        'swapPre'     => '',
    //        'failover'    => [],
    //        'foreignKeys' => true,
    //        'busyTimeout' => 1000,
    //        'synchronous' => null,
    //        'dateFormat'  => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for Postgre.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'public',
    //        'DBDriver'   => 'Postgre',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'port'       => 5432,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for SQLSRV.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'dbo',
    //        'DBDriver'   => 'SQLSRV',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'encrypt'    => false,
    //        'failover'   => [],
    //        'port'       => 1433,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for OCI8.
    //     *
    //     * You may need the following environment variables:
    //     *   NLS_LANG                = 'AMERICAN_AMERICA.UTF8'
    //     *   NLS_DATE_FORMAT         = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_FORMAT    = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS'
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => 'localhost:1521/XEPDB1',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'DBDriver'   => 'OCI8',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'AL32UTF8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'synchronous' => null,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
