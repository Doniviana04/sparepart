<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// ── Auth ──
$routes->get( '/',      'AuthController::login',   ['namespace' => 'App\Controllers']);
$routes->get( 'login',  'AuthController::login',   ['namespace' => 'App\Controllers']);
$routes->post('login',  'AuthController::doLogin',  ['namespace' => 'App\Controllers']);
$routes->post('login-process', 'AuthController::loginProcess', ['namespace' => 'App\Controllers']);
$routes->get( 'logout', 'AuthController::logout',   ['namespace' => 'App\Controllers']);

// ── Home (semua role, harus login) ──
$routes->get('home', 'Home::index', ['namespace' => 'App\Controllers', 'filter' => 'auth']);

// ── CRP Dashboard (hanya level 1-6) ──
$routes->group('crp', ['namespace' => 'App\Controllers', 'filter' => 'role:1,2,3,4,5,6'], function ($routes) {
    $routes->get('/',            'CrpController::index');       // GET /crp
    $routes->get('data',         'CrpController::getData');     // GET /crp/data?month=2026-03
    $routes->get('chart-usage',  'CrpController::getUsageChartData');
    $routes->get('export-excel', 'CrpController::exportExcel'); // GET /crp/export-excel
    $routes->post('control',     'CrpController::setControlStatus');
});

// ── Monitor User (level 1-7) ──
$routes->group('monitor-user', ['namespace' => 'App\Controllers', 'filter' => 'role:1,2,3,4,5,6,7'], function ($routes) {
    $routes->get('/',    'MonitorUserController::index');
    $routes->get('data', 'MonitorUserController::getData');
});