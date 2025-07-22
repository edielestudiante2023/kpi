<?php namespace App\Controllers;

use App\Models\AccesosrolModel;
use CodeIgniter\Controller;

class AdminController extends BaseController
{
    protected $accesosModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url']);
        $this->accesosModel = new AccesosrolModel();
    }

    /**
     * Dashboard principal del rol Admin
     */
    public function admindashboard()
    {
        $accesos = $this->accesosModel
            ->select('accesos_rol.*, roles.nombre_rol')
            ->join('roles', 'roles.id_roles = accesos_rol.id_roles')
            ->where('accesos_rol.id_roles', 2)
            ->orderBy('detalle', 'ASC')
            ->findAll();

        return view('admin/admindashboard', ['accesos' => $accesos]);
    }
}
