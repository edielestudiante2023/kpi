<?php

namespace App\Controllers;

use App\Models\CuentaBancoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class CuentaBancoController extends BaseController
{
    protected $cuentaBancoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->cuentaBancoModel = new CuentaBancoModel();
    }

    public function listCuentaBanco()
    {
        $data['cuentas'] = $this->cuentaBancoModel->orderBy('nombre_cuenta', 'ASC')->findAll();
        return view('conciliaciones/list_cuenta_banco', $data);
    }

    public function addCuentaBanco()
    {
        return view('conciliaciones/add_cuenta_banco');
    }

    public function addCuentaBancoPost()
    {
        $rules = [
            'nombre_cuenta' => 'required|is_unique[tbl_cuentas_banco.nombre_cuenta]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->cuentaBancoModel->insert($this->request->getPost());
        return redirect()->to('/conciliaciones/cuentas-banco')->with('success', 'Cuenta bancaria creada.');
    }

    public function editCuentaBanco($id)
    {
        $cuenta = $this->cuentaBancoModel->find($id);
        if (! $cuenta) {
            throw new PageNotFoundException("Cuenta bancaria con ID $id no existe");
        }
        return view('conciliaciones/edit_cuenta_banco', ['cuenta' => $cuenta]);
    }

    public function editCuentaBancoPost($id)
    {
        $rules = [
            'nombre_cuenta' => "required|is_unique[tbl_cuentas_banco.nombre_cuenta,id_cuenta_banco,{$id}]",
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }
        $this->cuentaBancoModel->update($id, $this->request->getPost());
        return redirect()->to('/conciliaciones/cuentas-banco')->with('success', 'Cuenta bancaria actualizada.');
    }

    public function deleteCuentaBanco($id)
    {
        $this->cuentaBancoModel->delete($id);
        return redirect()->to('/conciliaciones/cuentas-banco')->with('success', 'Cuenta bancaria eliminada.');
    }
}
