<?php

namespace App\Controllers;

use App\Models\SesionUsuarioModel;

class SesionController extends BaseController
{
    protected $sesionModel;

    public function __construct()
    {
        $this->sesionModel = new SesionUsuarioModel();
    }

    /**
     * Endpoint para recibir heartbeat (latido)
     * Se llama via AJAX cada minuto desde el cliente
     */
    public function heartbeat()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Acceso no permitido']);
        }

        $token = session()->get('sesion_token');

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sin sesion activa'
            ]);
        }

        $actualizado = $this->sesionModel->actualizarLatido($token);

        return $this->response->setJSON([
            'success' => $actualizado,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Dashboard de tiempo de uso (solo admin)
     */
    public function dashboard()
    {
        // Solo superadmin puede ver esto
        if (session()->get('id_roles') != 1) {
            return redirect()->to('/')->with('error', 'No tienes permisos para acceder a esta seccion');
        }

        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        // Cerrar sesiones inactivas primero
        $this->sesionModel->cerrarSesionesInactivas(10);

        $data = [
            'titulo'       => 'Tiempo de Uso',
            'estadisticas' => $this->sesionModel->getEstadisticasUso($fechaDesde, $fechaHasta),
            'porUsuario'   => $this->sesionModel->getResumenPorUsuario(),
            'porDia'       => $this->sesionModel->getUsoPorDia($fechaDesde, $fechaHasta),
            'sesiones'     => $this->sesionModel->getSesionesConUsuario([
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]),
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]
        ];

        return view('sesiones/dashboard', $data);
    }

    /**
     * Lista de sesiones activas
     */
    public function activas()
    {
        if (session()->get('id_roles') != 1) {
            return redirect()->to('/')->with('error', 'No tienes permisos para acceder a esta seccion');
        }

        // Cerrar sesiones inactivas primero
        $this->sesionModel->cerrarSesionesInactivas(10);

        $data = [
            'titulo'   => 'Sesiones Activas',
            'sesiones' => $this->sesionModel->getSesionesConUsuario(['activa' => 1])
        ];

        return view('sesiones/activas', $data);
    }

    /**
     * Detalle de sesiones de un usuario especifico
     */
    public function usuario($idUsuario)
    {
        if (session()->get('id_roles') != 1) {
            return redirect()->to('/')->with('error', 'No tienes permisos para acceder a esta seccion');
        }

        $userModel = new \App\Models\UsersModel();
        $usuario = $userModel->find($idUsuario);

        if (!$usuario) {
            return redirect()->to('sesiones/dashboard')->with('error', 'Usuario no encontrado');
        }

        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        $data = [
            'titulo'   => 'Sesiones de ' . $usuario['nombre_completo'],
            'usuario'  => $usuario,
            'sesiones' => $this->sesionModel->getSesionesConUsuario([
                'id_usuario'  => $idUsuario,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]),
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]
        ];

        return view('sesiones/usuario', $data);
    }

    /**
     * Forzar cierre de una sesion (admin)
     */
    public function cerrar($idSesion)
    {
        if (session()->get('id_roles') != 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sin permisos']);
        }

        $sesion = $this->sesionModel->find($idSesion);

        if (!$sesion) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sesion no encontrada']);
        }

        $this->sesionModel->update($idSesion, [
            'activa'    => 0,
            'fecha_fin' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Exportar datos de sesiones a CSV
     */
    public function exportar()
    {
        if (session()->get('id_roles') != 1) {
            return redirect()->to('/')->with('error', 'No tienes permisos');
        }

        $fechaDesde = $this->request->getGet('fecha_desde') ?? date('Y-m-d', strtotime('-30 days'));
        $fechaHasta = $this->request->getGet('fecha_hasta') ?? date('Y-m-d');

        $sesiones = $this->sesionModel->getSesionesConUsuario([
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);

        $filename = 'sesiones_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        // Headers
        fputcsv($output, [
            'Usuario',
            'Correo',
            'Fecha Inicio',
            'Fecha Fin',
            'Duracion (min)',
            'IP',
            'Estado'
        ], ';');

        foreach ($sesiones as $s) {
            $duracionMin = round(($s['duracion_segundos'] ?? 0) / 60, 1);
            fputcsv($output, [
                $s['nombre_completo'],
                $s['correo'],
                $s['fecha_inicio'],
                $s['fecha_fin'] ?? '-',
                $duracionMin,
                $s['ip_address'] ?? '-',
                $s['activa'] ? 'Activa' : 'Cerrada'
            ], ';');
        }

        fclose($output);
        exit;
    }
}
