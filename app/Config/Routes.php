<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::index');

$routes->get('/login', 'AuthController::index');
$routes->post('/login', 'AuthController::login');
$routes->get('/logout', 'AuthController::logout');

$routes->get('/superadmin/superadmindashboard', 'SuperadminController::superadmindashboard');
$routes->get('/admin/admindashboard', 'AdminController::admindashboard');
$routes->get('/jefatura/jefaturadashboard', 'JefaturaController::jefaturadashboard');
$routes->get('/trabajador/trabajadordashboard', 'TrabajadorController::trabajadordashboard');



// CRUD Usuarios
$routes->get  ('/users',               'UserController::listUser');
$routes->get  ('/users/add',           'UserController::addUser');
$routes->post ('/users/add',           'UserController::addUserPost');
$routes->get  ('/users/edit/(:num)',   'UserController::editUser/$1');
$routes->post ('/users/edit/(:num)',   'UserController::editUserPost/$1');
$routes->get  ('/users/delete/(:num)', 'UserController::deleteUser/$1');

// CRUD de perfiles de cargo
$routes->get  ('/perfiles',            'PerfilController::listPerfil');
$routes->get  ('/perfiles/add',        'PerfilController::addPerfil');
$routes->post ('/perfiles/add',        'PerfilController::addPerfilPost');
$routes->get  ('/perfiles/edit/(:num)','PerfilController::editPerfil/$1');
$routes->post ('/perfiles/edit/(:num)','PerfilController::editPerfilPost/$1');
$routes->get  ('/perfiles/delete/(:num)','PerfilController::deletePerfil/$1');

$routes->get  ('/roles',              'RolesController::listRol');
$routes->get  ('/roles/add',          'RolesController::addRol');
$routes->post ('/roles/add',          'RolesController::addRolPost');
$routes->get  ('/roles/edit/(:num)',  'RolesController::editRol/$1');
$routes->post ('/roles/edit/(:num)',  'RolesController::editRolPost/$1');
$routes->get  ('/roles/delete/(:num)','RolesController::deleteRol/$1');

// Rutas en app/Config/Routes.php
// -----------------------------------
$routes->get  ('/equipos',              'EquipoController::listEquipo');
$routes->get  ('/equipos/add',          'EquipoController::addEquipo');
$routes->post ('/equipos/add',          'EquipoController::addEquipopost');
$routes->get  ('/equipos/edit/(:num)',  'EquipoController::editEquipo/$1');
$routes->post ('/equipos/edit/(:num)',  'EquipoController::editEquipopost/$1');
$routes->get  ('/equipos/delete/(:num)', 'EquipoController::deleteEquipo/$1');


// Rutas en app/Config/Routes.php
$routes->get  ('/areas',              'AreasController::listAreas');
$routes->get  ('/areas/add',          'AreasController::addAreas');
$routes->post ('/areas/add',          'AreasController::addAreasPost');
$routes->get  ('/areas/edit/(:num)',  'AreasController::editAreas/$1');
$routes->post ('/areas/edit/(:num)',  'AreasController::editAreasPost/$1');
$routes->get  ('/areas/delete/(:num)','AreasController::deleteAreas/$1');

// RUTAS (app/Config/Routes.php)
$routes->get( '/indicadores',                'IndicadorController::listIndicador');
$routes->get( '/indicadores/add',            'IndicadorController::addIndicador');
$routes->post('/indicadores/add',            'IndicadorController::addIndicadorPost');
$routes->get( '/indicadores/edit/(:num)',    'IndicadorController::editIndicador/$1');
$routes->post('/indicadores/edit/(:num)',    'IndicadorController::editIndicadorPost/$1');
$routes->get( '/indicadores/delete/(:num)',  'IndicadorController::deleteIndicador/$1');

// Rutas en app/Config/Routes.php
// Listar indicadores por perfil (requiere ID del perfil)
$routes->get('/indicadores-perfil',         'IndicadorPerfilController::listPorPerfil');
$routes->get('/indicadores-perfil/(:num)',  'IndicadorPerfilController::listPorPerfil/$1');


$routes->get( '/indicadores_perfil',                'IndicadorPerfilController::listIndicadorPerfil');
$routes->get( '/indicadores_perfil/add',            'IndicadorPerfilController::addIndicadorPerfil');
$routes->post('/indicadores_perfil/add',            'IndicadorPerfilController::addIndicadorPerfilPost');
$routes->get( '/indicadores_perfil/edit/(:num)',    'IndicadorPerfilController::editIndicadorPerfil/$1');
$routes->post('/indicadores_perfil/edit/(:num)',    'IndicadorPerfilController::editIndicadorPerfilPost/$1');
$routes->get( '/indicadores_perfil/delete/(:num)',  'IndicadorPerfilController::deleteIndicadorPerfil/$1');



// Rutas en app/Config/Routes.php
$routes->get( '/historial_indicador',                'HistorialIndicadorController::listHistorialIndicador');
$routes->get( '/historial_indicador/add',            'HistorialIndicadorController::addHistorialIndicador');
$routes->post('/historial_indicador/add',            'HistorialIndicadorController::addHistorialIndicadorPost');
$routes->get( '/historial_indicador/edit/(:num)',    'HistorialIndicadorController::editHistorialIndicador/$1');
$routes->post('/historial_indicador/edit/(:num)',    'HistorialIndicadorController::editHistorialIndicadorPost/$1');
$routes->get( '/historial_indicador/delete/(:num)',  'HistorialIndicadorController::deleteHistorialIndicador/$1');


// RUTAS en app/Config/Routes.php
// Dashboard del trabajador
$routes->get('/trabajador/dashboard', 'TrabajadorController::dashboard');

// Mis indicadores
$routes->get('/trabajador/mis_indicadores', 'TrabajadorController::misIndicadores');
$routes->post('/trabajador/mis_indicadores', 'TrabajadorController::saveIndicadores');
$routes->post('/trabajador/saveIndicadores', 'TrabajadorController::saveIndicadores');
$routes->get('trabajador/historial_resultados', 'TrabajadorController::historialResultados');



$routes->get( '/accesosrol',                 'AccesosrolController::listAccesosrol');
$routes->get( '/accesosrol/add',             'AccesosrolController::addAccesosrol');
$routes->post('/accesosrol/add',             'AccesosrolController::addAccesosrolpost');
$routes->get( '/accesosrol/edit/(:num)',     'AccesosrolController::editAccesosrol/$1');
$routes->post('/accesosrol/edit/(:num)',     'AccesosrolController::editAccesosrolpost/$1');
$routes->get( '/accesosrol/delete/(:num)',   'AccesosrolController::deleteAccesosrol/$1');


$routes->group('jefatura', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('dashboard', 'JefaturaController::jefaturadashboard');
    $routes->get('misindicadorescomojefe', 'JefaturaController::misIndicadoresComoJefe');
    $routes->get('historialmisindicadoresfeje', 'JefaturaController::historialMisIndicadoresFeje');
    $routes->get('losindicadoresdemiequipo', 'JefaturaController::losIndicadoresDeMiEquipo');
    $routes->get('historiallosindicadoresdemiequipo', 'JefaturaController::historialLosIndicadoresDeMiEquipo');
});

$routes->post('jefatura/guardarEquipoIndicador/(:num)', 'JefaturaController::guardarEquipoIndicador/$1');
/* $routes->get('auditoria', 'AuditoriaController::listAuditoria'); */
$routes->post('jefatura/saveIndicadoresComoJefe', 'JefaturaController::saveIndicadoresComoJefe');

$routes->get('admin/admindashboard', 'AdminController::admindashboard');


$routes->get('partesformula/list', 'PartesFormulaController::listPartesFormulaModel');
$routes->get('partesformula/add', 'PartesFormulaController::addPartesFormulaModel');
$routes->post('partesformula/addpost', 'PartesFormulaController::addPartesFormulaModelPost');
$routes->get('partesformula/edit/(:num)', 'PartesFormulaController::editPartesFormulaModel/$1');
$routes->post('partesformula/editpost/(:num)', 'PartesFormulaController::editPartesFormulaModelPost/$1');
$routes->get('partesformula/delete/(:num)', 'PartesFormulaController::deletePartesFormulaModel/$1');

$routes->get('partesformula/upload', 'PartesFormulaController::uploadCSVForm');
$routes->post('partesformula/upload', 'PartesFormulaController::uploadCSVPost');

// Mostrar formulario para diligenciar
$routes->get('indicadores/fill/(:num)', 'IndicadorController::fillIndicador/$1');
// Procesar el envío y mostrar el resultado
$routes->post('indicadores/fill/(:num)', 'IndicadorController::fillIndicadorPost/$1');

// app/Config/Routes.php
$routes->group('trabajador', ['namespace' => 'App\Controllers'], function($routes) {
    // Dashboard
    $routes->get('dashboard',             'TrabajadorController::dashboard');
    // Listar indicadores (misIndicadores)
    $routes->get('misIndicadores',        'TrabajadorController::misIndicadores');
    // Guardar indicadores (saveIndicadores)
    $routes->post('saveIndicadores',      'TrabajadorController::saveIndicadores');
    // Historial simple (opcional)
    $routes->get('historial',             'TrabajadorController::historial');
    // Historial detallado
    $routes->get('historialResultados',   'TrabajadorController::historialResultados');
});


$routes->get('partesformula/nextorden/(:num)', 'PartesFormulaController::getNextOrden/$1');


$routes->get('edicion-indicadores', 'EdicionIndicadoresController::index');
$routes->post('edicion-indicadores/guardar', 'EdicionIndicadoresController::guardar');

$routes->get('jefatura/losindicadoresdemiequipo', 'JefaturaController::losIndicadoresDeMiEquipo');
$routes->post('jefatura/losindicadoresdemiequipo', 'JefaturaController::guardarIndicadoresDeEquipo');

$routes->get('jefatura/edicionrapidaequipo', 'JefaturaController::edicionRapidaEquipo');
$routes->get(  'jefatura/losindicadoresdemiequipo', 'JefaturaController::losIndicadoresDeMiEquipo');
$routes->post( 'jefatura/guardarIndicadoresDeEquipo', 'JefaturaController::guardarIndicadoresDeEquipo');

$routes->get('auditoria', 'AuditoriaController::index');
/* $routes->get('auditoria', 'AuditoriaController::listAuditoria'); */

$routes->get('trabajador/formula/(:num)', 'IndicadorController::diligenciarFormulaTrabajador/$1');
$routes->post('trabajador/formula/evaluar/(:num)', 'IndicadorController::evaluarFormulaTrabajadorPost/$1');

$routes->post('trabajador/formula/guardar/(:num)', 'TrabajadorController::guardarFormula/$1');


$routes->get('jefatura/formula/(:num)', 'IndicadorController::diligenciarFormulaJefe/$1');
$routes->post('jefatura/formula/evaluar/(:num)', 'IndicadorController::evaluarFormulaJefePost/$1');
$routes->post('jefatura/formula/guardar/(:num)', 'JefaturaController::guardarFormula/$1');


/* $routes->get ('jefatura/formula/(:num)',          'JefaturaController::fillFormula/$1');
$routes->post('jefatura/formula/(:num)',          'JefaturaController::fillFormulaPost/$1');
$routes->post('jefatura/formula/guardar/(:num)',  'JefaturaController::guardarFormula/$1'); */

// Al lado de tus otras rutas de jefatura:
$routes->get('jefatura/misIndicadoresComoJefe', 'JefaturaController::misIndicadoresComoJefe');



$routes->get('auditoria-indicadores', 'AuditoriaIndicadorController::index');

$routes->get('cambiarclave', 'AuthController::formPrimerLogin');
$routes->post('cambiarclave', 'AuthController::procesarPrimerLogin');

$routes->get('recuperar', 'AuthController::formRecuperar');
$routes->post('recuperar', 'AuthController::procesarRecuperar');

$routes->get('resetear/(:segment)', 'AuthController::formResetear/$1');
$routes->post('resetear', 'AuthController::procesarResetear');
$routes->post('resetear/(:segment)', 'AuthController::procesarResetear/$1');

$routes->post('jefatura/editarPeriodoEquipo', 'JefaturaController::editarPeriodoEquipo');

$routes->post('jefatura/editarCumpleEquipo', 'JefaturaController::editarCumpleEquipo');

$routes->get('users/completar/(:num)', 'UserController::completarUsuario/$1');
$routes->post('users/completar/(:num)', 'UserController::completarUsuarioPost/$1');

// File: app/Config/Routes.php

$routes->get('jerarquia/historialjerarquico', 'JerarquiaController::historialIndicadoresJerarquicos');

$routes->get('jerarquia/equipoextendido', 'JerarquiaController::verEquipoExtendido');






