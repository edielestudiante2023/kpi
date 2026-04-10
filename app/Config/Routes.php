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
    $routes->post('verificar-titulo', 'ActividadController::verificarTituloAjax');

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

// ====================================
// MÓDULO BITÁCORA (PWA)
// ====================================
$routes->group('bitacora', ['namespace' => 'App\Controllers', 'filter' => 'auth'], function($routes) {
    $routes->get('/', 'BitacoraController::index');
    $routes->post('iniciar', 'BitacoraController::iniciarActividad');
    $routes->post('terminar/(:num)', 'BitacoraController::terminarActividad/$1');
    $routes->post('descartar/(:num)', 'BitacoraController::descartarActividad/$1');
    $routes->get('actividad-activa', 'BitacoraController::actividadActiva');
    $routes->get('actividades-hoy', 'BitacoraController::actividadesHoy');
    $routes->get('equipo-en-progreso', 'BitacoraController::equipoEnProgreso');
    $routes->get('historial', 'BitacoraController::historial');
    $routes->get('historial/(:segment)', 'BitacoraController::historial/$1');
    $routes->get('resumen', 'BitacoraController::resumen');
    $routes->get('resumen/(:num)/(:num)', 'BitacoraController::resumen/$1/$2');
    $routes->get('analisis', 'BitacoraController::analisis');
    $routes->get('analisis/(:num)/(:num)', 'BitacoraController::analisis/$1/$2');
    $routes->get('equipo', 'BitacoraController::equipo');
    $routes->get('equipo/(:num)/(:num)', 'BitacoraController::equipo/$1/$2');
    $routes->get('equipo/detalle/(:num)/(:num)/(:num)', 'BitacoraController::equipoDetalle/$1/$2/$3');
    $routes->get('centros-costo', 'BitacoraController::centrosCosto');
    $routes->post('centros-costo/guardar', 'BitacoraController::guardarCentroCosto');
    $routes->post('centros-costo/eliminar/(:num)', 'BitacoraController::eliminarCentroCosto/$1');
    $routes->post('centros-costo/verificar-duplicado', 'BitacoraController::verificarDuplicadoCC');
    // Push notifications
    $routes->post('push/subscribe', 'BitacoraController::guardarPushSubscription');
    $routes->get('push/vapid-key', 'BitacoraController::vapidPublicKey');
    // Liquidación quincenal
    $routes->get('liquidacion', 'BitacoraController::liquidacion');
    $routes->post('liquidacion/ejecutar', 'BitacoraController::ejecutarLiquidacion');
    $routes->get('liquidacion/detalle/(:num)', 'BitacoraController::detalleLiquidacion/$1');
    // Dias Habiles — configuracion manual
    $routes->get('dias-habiles', 'BitacoraController::diasHabiles');
    $routes->get('dias-habiles/(:num)', 'BitacoraController::diasHabiles/$1');
    $routes->post('dias-habiles/guardar', 'BitacoraController::guardarDiasHabiles');
    // Festivos
    $routes->get('festivos', 'BitacoraController::festivos');
    $routes->get('festivos/(:num)', 'BitacoraController::festivos/$1');
    $routes->post('festivos/guardar', 'BitacoraController::guardarFestivo');
    $routes->post('festivos/eliminar/(:num)', 'BitacoraController::eliminarFestivo/$1');
    // Novedades de Tiempo — Colectivas
    $routes->get('novedades-colectivas', 'BitacoraController::novedadesColectivas');
    $routes->get('novedades-colectivas/(:num)', 'BitacoraController::novedadesColectivas/$1');
    $routes->post('novedades-colectivas/guardar', 'BitacoraController::guardarNovedadColectiva');
    $routes->post('novedades-colectivas/eliminar/(:num)', 'BitacoraController::eliminarNovedadColectiva/$1');
    // Novedades de Tiempo — Individuales
    $routes->get('novedades-individuales', 'BitacoraController::novedadesIndividuales');
    $routes->post('novedades-individuales/guardar', 'BitacoraController::guardarNovedadIndividual');
    $routes->post('novedades-individuales/eliminar/(:num)', 'BitacoraController::eliminarNovedadIndividual/$1');
    // Correcciones (autenticado)
    $routes->post('correccion/solicitar', 'BitacoraController::solicitarCorreccion');
});
// Correcciones bitácora — rutas públicas (token, sin login)
$routes->get('bitacora-correccion/(:segment)', 'BitacoraController::verCorreccion/$1');
$routes->post('bitacora-correccion/aprobar/(:segment)', 'BitacoraController::aprobarCorreccion/$1');
$routes->post('bitacora-correccion/rechazar/(:segment)', 'BitacoraController::rechazarCorreccion/$1');

// ====================================
// MÓDULO CONCILIACIONES
// ====================================
$routes->group('conciliaciones', ['namespace' => 'App\Controllers'], function($routes) {
    // Portafolios
    $routes->get('portafolios', 'PortafolioController::listPortafolio');
    $routes->get('portafolios/add', 'PortafolioController::addPortafolio');
    $routes->post('portafolios/add', 'PortafolioController::addPortafolioPost');
    $routes->get('portafolios/edit/(:num)', 'PortafolioController::editPortafolio/$1');
    $routes->post('portafolios/edit/(:num)', 'PortafolioController::editPortafolioPost/$1');
    $routes->get('portafolios/delete/(:num)', 'PortafolioController::deletePortafolio/$1');

    // Facturación — carga e historial
    $routes->get('facturacion', 'FacturacionController::listFacturacion');
    $routes->get('facturacion/upload', 'FacturacionController::uploadForm');
    $routes->post('facturacion/upload', 'FacturacionController::uploadPost');
    $routes->get('facturacion/truncar', 'FacturacionController::truncar');
    $routes->get('facturacion/exportar', 'FacturacionController::exportarCsv');

    // Centros de Costo
    $routes->get('centros-costo', 'CentroCostoController::listCentroCosto');
    $routes->get('centros-costo/add', 'CentroCostoController::addCentroCosto');
    $routes->post('centros-costo/add', 'CentroCostoController::addCentroCostoPost');
    $routes->get('centros-costo/edit/(:num)', 'CentroCostoController::editCentroCosto/$1');
    $routes->post('centros-costo/edit/(:num)', 'CentroCostoController::editCentroCostoPost/$1');
    $routes->get('centros-costo/delete/(:num)', 'CentroCostoController::deleteCentroCosto/$1');

    // Dashboard Financiero
    $routes->get('dashboard', 'DashboardFinancieroController::index');

    // Clasificación de Costos
    $routes->get('clasificacion', 'ClasificacionCostosController::listClasificacion');
    $routes->get('clasificacion/add', 'ClasificacionCostosController::addClasificacion');
    $routes->post('clasificacion/add', 'ClasificacionCostosController::addClasificacionPost');
    $routes->get('clasificacion/edit/(:num)', 'ClasificacionCostosController::editClasificacion/$1');
    $routes->post('clasificacion/edit/(:num)', 'ClasificacionCostosController::editClasificacionPost/$1');
    $routes->get('clasificacion/delete/(:num)', 'ClasificacionCostosController::deleteClasificacion/$1');

    // Deudas / Obligaciones
    $routes->get('deudas', 'DeudaController::listDeudas');
    $routes->get('deudas/add', 'DeudaController::addDeuda');
    $routes->post('deudas/add', 'DeudaController::addDeudaPost');
    $routes->get('deudas/edit/(:num)', 'DeudaController::editDeuda/$1');
    $routes->post('deudas/edit/(:num)', 'DeudaController::editDeudaPost/$1');
    $routes->get('deudas/delete/(:num)', 'DeudaController::deleteDeuda/$1');
    $routes->get('deudas/ver/(:num)', 'DeudaController::viewDeuda/$1');
    $routes->post('deudas/abono/(:num)', 'DeudaController::addAbonoPost/$1');
    $routes->get('deudas/abono/delete/(:num)/(:num)', 'DeudaController::deleteAbono/$1/$2');

    // Carga Cruda — Facturación (CSV)
    $routes->get('cruda/facturacion', 'CargaCrudaController::uploadFacturacion');
    $routes->post('cruda/facturacion', 'CargaCrudaController::uploadFacturacionPost');
    $routes->get('cruda/facturacion/list', 'CargaCrudaController::listFacturacionCruda');
    $routes->get('cruda/facturacion/truncar', 'CargaCrudaController::truncarFacturacion');

    // Carga Cruda — Movimiento Bancario (CSV)
    $routes->get('cruda/bancario', 'CargaCrudaController::uploadBancario');
    $routes->post('cruda/bancario', 'CargaCrudaController::uploadBancarioPost');
    $routes->get('cruda/bancario/list', 'CargaCrudaController::listBancarioCrudo');
    $routes->get('cruda/bancario/truncar/(:num)', 'CargaCrudaController::truncarBancario/$1');

    // Cuentas de Banco
    $routes->get('cuentas-banco', 'CuentaBancoController::listCuentaBanco');
    $routes->get('cuentas-banco/add', 'CuentaBancoController::addCuentaBanco');
    $routes->post('cuentas-banco/add', 'CuentaBancoController::addCuentaBancoPost');
    $routes->get('cuentas-banco/edit/(:num)', 'CuentaBancoController::editCuentaBanco/$1');
    $routes->post('cuentas-banco/edit/(:num)', 'CuentaBancoController::editCuentaBancoPost/$1');
    $routes->get('cuentas-banco/delete/(:num)', 'CuentaBancoController::deleteCuentaBanco/$1');

    // Conciliación Bancaria — carga e historial
    $routes->get('bancaria', 'ConciliacionBancariaController::listConciliacion');
    $routes->get('bancaria/upload', 'ConciliacionBancariaController::uploadForm');
    $routes->post('bancaria/upload', 'ConciliacionBancariaController::uploadPost');
    $routes->get('bancaria/truncar/(:num)', 'ConciliacionBancariaController::truncar/$1');
    $routes->get('bancaria/exportar', 'ConciliacionBancariaController::exportarCsv');
});

// Cron bitácora: usar CLI → php spark bitacora:resumen-diario
