<?php

use CodeIgniter\Router\RouteCollection;


$routes->get('login', 'AuthController::loginForm');
$routes->post('login', 'AuthController::loginProcess');
$routes->get('logout', 'AuthController::logout');


$routes->group('', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'DashboardController::index');

    $routes->get('tickets', 'TicketController::index');
    $routes->get('tickets/datatables', 'TicketController::datatables');
    $routes->post('tickets/store', 'TicketController::store');
    $routes->post('tickets/update/(:num)', 'TicketController::update/$1');
    $routes->get('tickets/show/(:num)', 'TicketController::show/$1');
    $routes->post('tickets/delete/(:num)', 'TicketController::delete/$1');
    $routes->get('tickets/master-price', 'TicketController::getMasterPrice');
    $routes->get('tickets/public-list', 'TicketController::publicList', ['filter' => 'auth']);


    $routes->get('regions/list', 'RegionController::list');

    $routes->get('master-prices', 'MasterPriceController::index');
    $routes->get('master-prices/datatables', 'MasterPriceController::datatables');
    $routes->get('master-prices/show/(:num)', 'MasterPriceController::show/$1');
    $routes->post('master-prices/store', 'MasterPriceController::store');
    $routes->post('master-prices/update/(:num)', 'MasterPriceController::update/$1');
    $routes->post('master-prices/delete/(:num)', 'MasterPriceController::delete/$1');

    $routes->get('orders', 'OrderController::index');
    $routes->get('orders/list', 'OrderController::list');
    $routes->post('orders/store', 'OrderController::store');
    $routes->post('orders/cancel/(:num)', 'OrderController::cancel/$1');


    $routes->get('orders', 'OrderController::index'); 
});

