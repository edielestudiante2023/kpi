<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HistorialIndicadorModel;
use CodeIgniter\Controller;

class EdicionIndicadoresController extends Controller
{
    protected $userModel;
    protected $histModel;

    public function __construct()
    {
        helper(['url', 'form', 'session']);
        $this->userModel = new UserModel();
        $this->histModel = new HistorialIndicadorModel();
    }

    public function index()
    {
        $session = session();
        $jefeId = $session->get('id_users');
        $periodo = $this->request->getGet('periodo') ?? date('Y-m');

        $subIds = array_column($this->userModel->getSubordinadosDeJefe($jefeId), 'id_users');

        // Si no hay subordinados, es porque no es un jefe:
        if (empty($subIds)) {
            // Puedes cambiar el estilo HTML como prefieras:
            echo '<h2>Acceso denegado</h2>'
                . '<p>Para acceder a este recurso, ingresa con credenciales de un <strong>jefe</strong>.</p>';
            return; // detenemos la ejecución antes de la consulta
        }


        $equipo = $this->histModel
            ->select([
                'historial_indicadores.id_historial',
                'users.nombre_completo',
                'indicadores.nombre AS nombre_indicador',
                'historial_indicadores.resultado_real',
                'historial_indicadores.comentario',
                'historial_indicadores.periodo',
                'historial_indicadores.fecha_registro', // ⬅️ AÑADIDO
            ])
            ->join('indicadores_perfil', 'indicadores_perfil.id_indicador_perfil = historial_indicadores.id_indicador_perfil')
            ->join('indicadores', 'indicadores.id_indicador = indicadores_perfil.id_indicador')
            ->join('users', 'users.id_users = historial_indicadores.id_usuario')
            ->whereIn('historial_indicadores.id_usuario', $subIds)
            ->where('historial_indicadores.periodo', $periodo)
            ->orderBy('users.nombre_completo', 'ASC')
            ->findAll();


        return view('jefatura/edicionrapidaequipo', [
            'equipo'  => $equipo,
            'periodo' => $periodo,
        ]);
    }

    public function guardar()
    {
        $post = $this->request->getPost();
        $cambios = $post['cambios'] ?? [];

        foreach ($cambios as $idHistorial => $datos) {
            $this->histModel->update($idHistorial, [
                'resultado_real' => $datos['resultado_real'],
                'comentario'     => $datos['comentario'],
            ]);
        }

        return redirect()->to('/edicion-indicadores')->with('success', 'Indicadores actualizados correctamente.');
    }
}
