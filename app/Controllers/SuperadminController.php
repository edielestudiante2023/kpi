<?php namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RolesModel;
use App\Models\AreaModel;
use App\Models\AccesosrolModel;
use CodeIgniter\Controller;

class SuperadminController extends BaseController
{
    protected $userModel;
    protected $RolesModel;
    protected $areaModel;
    protected $accesosModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url']);
        $this->userModel = new UserModel();
        $this->RolesModel = new RolesModel();
        $this->areaModel = new AreaModel();
        $this->areaModel = new AccesosrolModel();
    }

    /**
     * Muestra el dashboard principal de Superadmin
     * 
     * 
     */
    public function superadmindashboard()
{
    $this->accesosModel = new \App\Models\AccesosrolModel();

    $accesos = $this->accesosModel
        ->select('accesos_rol.*, roles.nombre_rol')
        ->join('roles', 'roles.id_roles = accesos_rol.id_roles')
        ->orderBy('roles.nombre_rol', 'ASC')
        ->findAll();

    $data = [
        'total_usuarios' => $this->userModel->countAll(),
        'total_roles'    => $this->RolesModel->countAll(),
        'total_areas'    => $this->areaModel->countAll(),
        'accesos'        => $accesos
    ];

    return view('superadmin/superadmindashboard', $data);
}


    
}
