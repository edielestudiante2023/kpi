<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ====================================
// AUTENTICACIÓN
// ====================================
$routes->get('/', 'AuthController::index');
$routes->get('login', 'AuthController::index');
$routes->post('login', 'AuthController::login');
$routes->get('logout', 'AuthController::logout');

$routes->get('cambiarclave', 'AuthController::formPrimerLogin');
$routes->post('cambiarclave', 'AuthController::procesarPrimerLogin');

$routes->get('recuperar', 'AuthController::formRecuperar');
$routes->post('recuperar', 'AuthController::procesarRecuperar');

$routes->get('resetear/(:segment)', 'AuthController::formResetear/$1');
$routes->post('resetear/(:segment)', 'AuthController::procesarResetear/$1');

// ====================================
// DASHBOARDS
// ====================================
$routes->get('superadmin/superadmindashboard', 'SuperadminController::superadmindashboard');
$routes->get('admin/admindashboard', 'AdminController::admindashboard');
$routes->get('jefatura/jefaturadashboard', 'JefaturaController::jefaturadashboard');
$routes->get('trabajador/trabajadordashboard', 'TrabajadorController::trabajadordashboard');

// ====================================
// CRUD USUARIOS
// ====================================
$routes->group('users', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'UserController::listUser');
    $routes->get('add', 'UserController::addUser');
    $routes->post('add', 'UserController::addUserPost');
    $routes->get('edit/(:num)', 'UserController::editUser/$1');
    $routes->post('edit/(:num)', 'UserController::editUserPost/$1');
    $routes->get('delete/(:num)', 'UserController::deleteUser/$1');
    $routes->get('completar/(:num)', 'UserController::completarUsuario/$1');
    $routes->post('completar/(:num)', 'UserController::completarUsuarioPost/$1');
});

// ====================================
// CRUD PERFILES DE CARGO
// ====================================
$routes->group('perfiles', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'PerfilController::listPerfil');
    $routes->get('add', 'PerfilController::addPerfil');
    $routes->post('add', 'PerfilController::addPerfilPost');
    $routes->get('edit/(:num)', 'PerfilController::editPerfil/$1');
    $routes->post('edit/(:num)', 'PerfilController::editPerfilPost/$1');
    $routes->get('delete/(:num)', 'PerfilController::deletePerfil/$1');
});

// ====================================
// CRUD ROLES
// ====================================
$routes->group('roles', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'RolesController::listRol');
    $routes->get('add', 'RolesController::addRol');
    $routes->post('add', 'RolesController::addRolPost');
    $routes->get('edit/(:num)', 'RolesController::editRol/$1');
    $routes->post('edit/(:num)', 'RolesController::editRolPost/$1');
    $routes->get('delete/(:num)', 'RolesController::deleteRol/$1');
});

// ====================================
// CRUD EQUIPOS
// ====================================
$routes->group('equipos', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'EquipoController::listEquipo');
    $routes->get('add', 'EquipoController::addEquipo');
    $routes->post('add', 'EquipoController::addEquipopost');
    $routes->get('edit/(:num)', 'EquipoController::editEquipo/$1');
    $routes->post('edit/(:num)', 'EquipoController::editEquipopost/$1');
    $routes->get('delete/(:num)', 'EquipoController::deleteEquipo/$1');
});

// ====================================
// CRUD ÁREAS
// ====================================
$routes->group('areas', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'AreasController::listAreas');
    $routes->get('add', 'AreasController::addAreas');
    $routes->post('add', 'AreasController::addAreasPost');
    $routes->get('edit/(:num)', 'AreasController::editAreas/$1');
    $routes->post('edit/(:num)', 'AreasController::editAreasPost/$1');
    $routes->get('delete/(:num)', 'AreasController::deleteAreas/$1');
});

// ====================================
// CRUD INDICADORES
// ====================================
$routes->group('indicadores', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'IndicadorController::listIndicador');
    $routes->get('add', 'IndicadorController::addIndicador');
    $routes->post('add', 'IndicadorController::addIndicadorPost');
    $routes->get('edit/(:num)', 'IndicadorController::editIndicador/$1');
    $routes->post('edit/(:num)', 'IndicadorController::editIndicadorPost/$1');
    $routes->get('delete/(:num)', 'IndicadorController::deleteIndicador/$1');
    $routes->get('fill/(:num)', 'IndicadorController::fillIndicador/$1');
    $routes->post('fill/(:num)', 'IndicadorController::fillIndicadorPost/$1');
});

// ====================================
// CRUD INDICADORES POR PERFIL
// ====================================
$routes->group('indicadores_perfil', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'IndicadorPerfilController::listIndicadorPerfil');
    $routes->get('add', 'IndicadorPerfilController::addIndicadorPerfil');
    $routes->post('add', 'IndicadorPerfilController::addIndicadorPerfilPost');
    $routes->get('edit/(:num)', 'IndicadorPerfilController::editIndicadorPerfil/$1');
    $routes->post('edit/(:num)', 'IndicadorPerfilController::editIndicadorPerfilPost/$1');
    $routes->get('delete/(:num)', 'IndicadorPerfilController::deleteIndicadorPerfil/$1');
});

// Rutas alternativas para indicadores-perfil
$routes->get('indicadores-perfil', 'IndicadorPerfilController::listPorPerfil');
$routes->get('indicadores-perfil/(:num)', 'IndicadorPerfilController::listPorPerfil/$1');

// ====================================
// CRUD HISTORIAL INDICADORES
// ====================================
$routes->group('historial_indicador', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'HistorialIndicadorController::listHistorialIndicador');
    $routes->get('add', 'HistorialIndicadorController::addHistorialIndicador');
    $routes->post('add', 'HistorialIndicadorController::addHistorialIndicadorPost');
    $routes->get('edit/(:num)', 'HistorialIndicadorController::editHistorialIndicador/$1');
    $routes->post('edit/(:num)', 'HistorialIndicadorController::editHistorialIndicadorPost/$1');
    $routes->get('delete/(:num)', 'HistorialIndicadorController::deleteHistorialIndicador/$1');
});

// ====================================
// CRUD ACCESOS ROL
// ====================================
$routes->group('accesosrol', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'AccesosrolController::listAccesosrol');
    $routes->get('add', 'AccesosrolController::addAccesosrol');
    $routes->post('add', 'AccesosrolController::addAccesosrolpost');
    $routes->get('edit/(:num)', 'AccesosrolController::editAccesosrol/$1');
    $routes->post('edit/(:num)', 'AccesosrolController::editAccesosrolpost/$1');
    $routes->get('delete/(:num)', 'AccesosrolController::deleteAccesosrol/$1');
});

// ====================================
// PARTES FÓRMULA
// ====================================
$routes->group('partesformula', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('list', 'PartesFormulaController::listPartesFormulaModel');
    $routes->get('add', 'PartesFormulaController::addPartesFormulaModel');
    $routes->post('addpost', 'PartesFormulaController::addPartesFormulaModelPost');
    $routes->get('edit/(:num)', 'PartesFormulaController::editPartesFormulaModel/$1');
    $routes->post('editpost/(:num)', 'PartesFormulaController::editPartesFormulaModelPost/$1');
    $routes->get('delete/(:num)', 'PartesFormulaController::deletePartesFormulaModel/$1');
    $routes->get('upload', 'PartesFormulaController::uploadCSVForm');
    $routes->post('upload', 'PartesFormulaController::uploadCSVPost');
    $routes->get('nextorden/(:num)', 'PartesFormulaController::getNextOrden/$1');
});

// ====================================
// TRABAJADOR
// ====================================
$routes->group('trabajador', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('dashboard', 'TrabajadorController::trabajadordashboard');
    $routes->get('mis_indicadores', 'TrabajadorController::misIndicadores');
    $routes->post('mis_indicadores', 'TrabajadorController::saveIndicadores');
    $routes->post('saveIndicadores', 'TrabajadorController::saveIndicadores');
    $routes->get('historial_resultados', 'TrabajadorController::historialResultados');
    $routes->get('historialResultados', 'TrabajadorController::historialResultados');
    $routes->get('formula/(:num)', 'IndicadorController::diligenciarFormulaTrabajador/$1');
    $routes->post('formula/evaluar/(:num)', 'IndicadorController::evaluarFormulaTrabajadorPost/$1');
    $routes->post('formula/guardar/(:num)', 'TrabajadorController::guardarFormula/$1');
});

// ====================================
// JEFATURA
// ====================================
$routes->group('jefatura', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('dashboard', 'JefaturaController::jefaturadashboard');
    $routes->get('misindicadorescomojefe', 'JefaturaController::misIndicadoresComoJefe');
    $routes->get('misIndicadoresComoJefe', 'JefaturaController::misIndicadoresComoJefe');
    $routes->post('saveIndicadoresComoJefe', 'JefaturaController::saveIndicadoresComoJefe');
    $routes->get('historialmisindicadoresfeje', 'JefaturaController::historialMisIndicadoresFeje');
    $routes->get('losindicadoresdemiequipo', 'JefaturaController::losIndicadoresDeMiEquipo');
    $routes->post('guardarIndicadoresDeEquipo', 'JefaturaController::guardarIndicadoresDeEquipo');
    $routes->get('historiallosindicadoresdemiequipo', 'JefaturaController::historialLosIndicadoresDeMiEquipo');
    $routes->post('guardarEquipoIndicador/(:num)', 'JefaturaController::guardarEquipoIndicador/$1');
    $routes->post('editarPeriodoEquipo', 'JefaturaController::editarPeriodoEquipo');
    $routes->post('editarCumpleEquipo', 'JefaturaController::editarCumpleEquipo');
    $routes->get('formula/(:num)', 'IndicadorController::diligenciarFormulaJefe/$1');
    $routes->post('formula/evaluar/(:num)', 'IndicadorController::evaluarFormulaJefePost/$1');
    $routes->post('formula/guardar/(:num)', 'JefaturaController::guardarFormula/$1');
});

// ====================================
// JERARQUÍA
// ====================================
$routes->group('jerarquia', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('historialjerarquico', 'JerarquiaController::historialIndicadoresJerarquicos');
    $routes->get('equipoextendido', 'JerarquiaController::verEquipoExtendido');
});

// ====================================
// AUDITORÍA
// ====================================
$routes->get('auditoria', 'AuditoriaController::index');
$routes->get('auditoria-indicadores', 'AuditoriaIndicadorController::index');
$routes->get('edicion-indicadores', 'EdicionIndicadoresController::index');
$routes->post('edicion-indicadores/guardar', 'EdicionIndicadoresController::guardar');

// ====================================
// MÓDULO DE ACTIVIDADES / TICKETS
// ====================================
$routes->group('actividades', ['namespace' => 'App\Controllers'], function($routes) {
    // Tableros
    $routes->get('tablero', 'ActividadController::tableroEstado');
    $routes->get('responsable', 'ActividadController::tableroResponsable');
    $routes->get('dashboard', 'ActividadController::dashboard');
    $routes->get('mis-actividades', 'ActividadController::misActividades');

    // Lista tradicional
    $routes->get('lista', 'ActividadController::listActividades');

    // CRUD
    $routes->get('nueva', 'ActividadController::addActividad');
    $routes->post('nueva', 'ActividadController::addActividadPost');
    $routes->get('ver/(:num)', 'ActividadController::viewActividad/$1');
    $routes->get('editar/(:num)', 'ActividadController::editActividad/$1');
    $routes->post('editar/(:num)', 'ActividadController::editActividadPost/$1');
    $routes->get('eliminar/(:num)', 'ActividadController::deleteActividad/$1');

    // AJAX
    $routes->post('cambiar-estado', 'ActividadController::cambiarEstadoAjax');
    $routes->post('comentario', 'ActividadController::agregarComentarioAjax');

    // Archivos
    $routes->post('archivo/subir/(:num)', 'ActividadController::subirArchivo/$1');
    $routes->get('archivo/descargar/(:num)', 'ActividadController::descargarArchivo/$1');
    $routes->post('archivo/eliminar/(:num)', 'ActividadController::eliminarArchivo/$1');
});

// ====================================
// PREFERENCIAS DE NOTIFICACIÓN
// ====================================
$routes->group('preferencias', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('notificaciones', 'PreferenciaNotificacionController::index');
    $routes->post('notificaciones/guardar', 'PreferenciaNotificacionController::guardar');
});

// ====================================
// SESIONES / TIEMPO DE USO
// ====================================
$routes->group('sesiones', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('dashboard', 'SesionController::dashboard');
    $routes->get('activas', 'SesionController::activas');
    $routes->get('usuario/(:num)', 'SesionController::usuario/$1');
    $routes->post('cerrar/(:num)', 'SesionController::cerrar/$1');
    $routes->get('exportar', 'SesionController::exportar');
});
$routes->post('sesion/heartbeat', 'SesionController::heartbeat');

// ====================================
// CATEGORÍAS DE ACTIVIDADES
// ====================================
$routes->group('categorias-actividad', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('/', 'CategoriaActividadController::index');
    $routes->get('nueva', 'CategoriaActividadController::create');
    $routes->post('guardar', 'CategoriaActividadController::store');
    $routes->get('editar/(:num)', 'CategoriaActividadController::edit/$1');
    $routes->post('actualizar/(:num)', 'CategoriaActividadController::update/$1');
    $routes->post('eliminar/(:num)', 'CategoriaActividadController::delete/$1');
    $routes->post('toggle-estado/(:num)', 'CategoriaActividadController::toggleEstado/$1');
});

// ====================================
// INTELIGENCIA ARTIFICIAL (OpenAI)
// ====================================
$routes->group('ia', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->post('generar-indicador', 'OpenAIController::generarIndicador');
    $routes->post('generar-actividad', 'OpenAIController::generarActividad');
    $routes->get('status', 'OpenAIController::status');
});
