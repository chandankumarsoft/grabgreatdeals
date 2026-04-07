<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default home (keep for health check)
$routes->get('/', 'Home::index');

// ── API v1 ────────────────────────────────────────────────────────────────────
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], function ($routes) {

    // ── Public routes (no auth required) ─────────────────────────────────────
    $routes->group('', [], function ($routes) {
        // Auth
        $routes->post('auth/register', 'AuthController::register');
        $routes->post('auth/login',    'AuthController::login');

        // Public product listing
        $routes->get('products',          'ProductController::index');
        $routes->get('products/(:segment)', 'ProductController::show/$1');
    });

    // ── Authenticated routes (JWT required) ───────────────────────────────────
    $routes->group('', ['filter' => 'auth'], function ($routes) {
        // Auth
        $routes->post('auth/logout', 'AuthController::logout');

        // User profile
        $routes->get('user/profile',    'UserController::profile');
        $routes->put('user/profile',    'UserController::updateProfile');
        $routes->post('user/addresses', 'UserController::addAddress');
        $routes->get('user/addresses',  'UserController::addresses');
        $routes->delete('user/addresses/(:num)', 'UserController::deleteAddress/$1');

        // Cart
        $routes->get('cart',                   'CartController::index');
        $routes->post('cart',                  'CartController::add');
        $routes->put('cart/(:num)',             'CartController::update/$1');
        $routes->delete('cart/(:num)',          'CartController::remove/$1');
        $routes->delete('cart',                'CartController::clear');

        // Orders
        $routes->post('orders',                'OrderController::checkout');
        $routes->get('orders',                 'OrderController::index');
        $routes->get('orders/(:num)',           'OrderController::show/$1');
    });

    // ── Admin routes (JWT + admin role) ──────────────────────────────────────
    $routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {
        // Products
        $routes->get('products',                          'Admin\ProductController::index');
        $routes->post('products',                         'Admin\ProductController::create');
        $routes->put('products/(:num)',                   'Admin\ProductController::update/$1');
        $routes->delete('products/(:num)',                'Admin\ProductController::delete/$1');

        // Product images
        $routes->post('products/(:num)/images',           'Admin\ProductController::uploadImages/$1');
        $routes->delete('products/(:num)/images/(:num)',  'Admin\ProductController::deleteImage/$1/$2');

        // Orders management
        $routes->get('orders',                            'Admin\OrderController::index');
        $routes->put('orders/(:num)/status',              'Admin\OrderController::updateStatus/$1');

        // Users management
        $routes->get('users',                             'Admin\UserController::index');
    });
});
