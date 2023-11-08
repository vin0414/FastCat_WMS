<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->post('/auth','Auth::check');
$routes->get('/logout','Auth::logout');
//dashboard
$routes->get('total-item','Dashboard::totalItem');
$routes->get('total-stocks','Dashboard::totalStocks');
$routes->get('total-reserved','Dashboard::totalReserved');
$routes->get('total-void','Dashboard::totalVoid');
$routes->get('out-of-stock','Dashboard::outofStock');
$routes->get('list-supplier','Dashboard::listSupplier');
//saving the data
$routes->post('save-industry','Home::saveIndustry');
$routes->get('list-industry','Home::listIndustry');
$routes->get('fetch-industry','Home::fetchIndustry');
$routes->post('save-category','Home::saveCategory');
$routes->get('list-category','Home::listCategory');
$routes->post('save-warehouse','Home::saveWarehouse');
$routes->get('list-warehouse','Home::listWarehouse');
$routes->post('save-supplier','Home::saveSupplier');
$routes->post('update-supplier','Home::updateSupplier');
$routes->post('save-account','Home::saveAccount');
$routes->post('update-account','Home::updateAccount');
$routes->post('add-product','Home::addProduct');
$routes->post('update','Home::update');
$routes->get('assignment','Home::assignment');
$routes->post('change-password','Home::changePassword');
//fetch the product details
$routes->get('product-information','ProductController::productInfo');
$routes->post('save-report','ProductController::saveReport');
$routes->post('submit-report','ProductController::submitReport');
$routes->post('send-report','ProductController::sendReport');
$routes->get('view-report','ProductController::viewReport');
$routes->post('transfer-item','ProductController::transferItem');
$routes->post('receive-report','ProductController::receiveReport');
//standard user reports
$routes->post('send-damage-report','ProductController::damageReport');

$routes->group('',['filter'=>'AuthCheck'],function($routes)
{
    $routes->get('/dashboard','Home::dashboard');
    $routes->get('/stocks','Home::stocks');
    $routes->get('/add','Home::addItem');
    $routes->get('/edit/(:any)','Home::edit/$1');
    $routes->get('/transfer/(:any)','Home::transfer/$1');
    $routes->get('/manage','Home::manageStocks');
    $routes->get('/create-report/(:any)','Home::createReport/$1');
    $routes->get('receiving-item','Home::receiveItem');
    $routes->get('/suppliers','Home::suppliers');
    $routes->get('/add-supplier','Home::addSupplier');
    $routes->get('/edit-supplier/(:any)','Home::editSupplier/$1');
    $routes->get('/configuration','Home::systemConfiguration');
    $routes->get('/edit-account/(:any)','Home::editAccount/$1');
    $routes->get('/add-report','Home::addReport');
    $routes->get('/damage-report','Home::damageReport');
    $routes->get('/profile','Home::profile');
});
$routes->group('',['filter'=>'AlreadyLoggedIn'],function($routes)
{
    $routes->get('/auth','Auth::index');
    $routes->get('/Auth','Auth::index');
    $routes->get('/','Home::index');
});
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
