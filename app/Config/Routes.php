<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');
$routes->get('/', 'CallReport::index'); 
$routes->get('callreport', 'CallReport::index'); 
$routes->get('callreport/index', 'CallReport::index'); 
$routes->get('callreport/fetchRingbaCalls', 'CallReport::fetchRingbaCalls');

// http://localhost/call_reporting/public/callreport/fetchRingbaCalls
