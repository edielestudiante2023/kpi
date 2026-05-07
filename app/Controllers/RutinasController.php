<?php

namespace App\Controllers;

use App\Models\RutinaActividadModel;
use App\Models\RutinaAsignacionModel;
use App\Models\RutinaRegistroModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class RutinasController extends BaseController
{
    protected $actividadModel;
    protected $asignacionModel;
    protected $registroModel;
    protected $userModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->actividadModel  = new RutinaActividadModel();
        $this->asignacionModel = new RutinaAsignacionModel();
        $this->registroModel   = new RutinaRegistroModel();
        $this->userModel       = new UserModel();
    }

    // =========================================================
    // CRUD ACTIVIDADES (tabla maestra)
    // =========================================================

    public function listActividades()
    {
        $data['actividades'] = $this->actividadModel->orderBy('nombre', 'ASC')->findAll();
        return view('rutinas/list_actividades', $data);
    }

    public function addActividad()
    {
        return view('rutinas/add_actividad');
    }

    public function addActividadPost()
    {
        $rules = [
            'nombre'    => 'required|max_length[255]',
            'categoria' => 'permit_empty|max_length[100]',
            'descripcion' => 'permit_empty',
            'frecuencia'  => 'required|in_list[L-V,diaria]',
            'peso'        => 'required|decimal',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }
        $this->actividadModel->insert([
            'nombre'      => $this->request->getPost('nombre'),
            'categoria'   => trim($this->request->getPost('categoria') ?: 'General'),
            'descripcion' => $this->request->getPost('descripcion'),
            'frecuencia'  => $this->request->getPost('frecuencia'),
            'peso'        => $this->request->getPost('peso'),
            'activa'      => 1,
        ]);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad creada.');
    }

    public function editActividad($id)
    {
        $actividad = $this->actividadModel->find($id);
        if (! $actividad) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }
        return view('rutinas/edit_actividad', ['actividad' => $actividad]);
    }

    public function editActividadPost($id)
    {
        $rules = [
            'nombre'    => 'required|max_length[255]',
            'categoria' => 'permit_empty|max_length[100]',
            'descripcion' => 'permit_empty',
            'frecuencia'  => 'required|in_list[L-V,diaria]',
            'peso'        => 'required|decimal',
            'activa'      => 'required|in_list[0,1]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }
        $this->actividadModel->update($id, [
            'nombre'      => $this->request->getPost('nombre'),
            'categoria'   => trim($this->request->getPost('categoria') ?: 'General'),
            'descripcion' => $this->request->getPost('descripcion'),
            'frecuencia'  => $this->request->getPost('frecuencia'),
            'peso'        => $this->request->getPost('peso'),
            'activa'      => $this->request->getPost('activa'),
        ]);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad actualizada.');
    }

    public function deleteActividad($id)
    {
        $this->actividadModel->delete($id);
        return redirect()->to('/rutinas/actividades')->with('success', 'Actividad eliminada.');
    }

    // =========================================================
    // CRUD ASIGNACIONES (quién hace qué)
    // =========================================================

    public function listAsignaciones()
    {
        $db = \Config\Database::connect();

        // Filtros server-side
        $fUser      = (int) ($this->request->getGet('usuario') ?: 0);
        $fCategoria = trim((string) $this->request->getGet('categoria'));
        $fFrecuencia = trim((string) $this->request->getGet('frecuencia'));
        $fEstado    = $this->request->getGet('estado'); // '', '1', '0'
        $vista      = $this->request->getGet('vista') ?: 'lista'; // lista | usuarios

        $builder = $db->table('rutinas_asignaciones ra')
            ->select('ra.id_asignacion, ra.activa,
                      u.id_users, u.nombre_completo, u.correo,
                      a.id_actividad, a.nombre AS actividad_nombre,
                      a.categoria, a.frecuencia, a.peso')
            ->join('users u', 'u.id_users = ra.id_users')
            ->join('rutinas_actividades a', 'a.id_actividad = ra.id_actividad')
            ->orderBy('u.nombre_completo, a.categoria, a.nombre');

        if ($fUser > 0)         $builder->where('ra.id_users', $fUser);
        if ($fCategoria !== '') $builder->where('a.categoria', $fCategoria);
        if ($fFrecuencia !== '') $builder->where('a.frecuencia', $fFrecuencia);
        if ($fEstado === '1')   $builder->where('ra.activa', 1);
        if ($fEstado === '0')   $builder->where('ra.activa', 0);

        $asignaciones = $builder->get()->getResultArray();

        // ── % CUMPLIMIENTO DEL MES ACTUAL ──
        // Calcular días hábiles transcurridos del mes actual
        $hoy = date('Y-m-d');
        $primerDiaMes = date('Y-m-01');
        $cumplimiento = $this->calcularCumplimientoMes($db, $primerDiaMes, $hoy, $asignaciones);

        // Inyectar cumplimiento en cada asignación
        foreach ($asignaciones as &$a) {
            $key = $a['id_users'] . '_' . $a['id_actividad'];
            $a['cumplimiento_pct']  = $cumplimiento[$key]['pct']  ?? 0;
            $a['cumplimiento_done'] = $cumplimiento[$key]['done'] ?? 0;
            $a['cumplimiento_esp']  = $cumplimiento[$key]['esperados'] ?? 0;
        }
        unset($a);

        $data['asignaciones'] = $asignaciones;
        $data['usuarios']     = $this->userModel->where('activo', 1)->orderBy('nombre_completo')->findAll();
        $data['actividades']  = $this->actividadModel->where('activa', 1)->orderBy('categoria, nombre')->findAll();

        // Categorías únicas para el filtro
        $cats = $db->query("SELECT DISTINCT categoria FROM rutinas_actividades WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria")->getResultArray();
        $data['categorias'] = array_column($cats, 'categoria');

        // Filtros activos
        $data['filtros'] = [
            'usuario' => $fUser, 'categoria' => $fCategoria,
            'frecuencia' => $fFrecuencia, 'estado' => $fEstado, 'vista' => $vista,
        ];

        // Mes que se está midiendo (para mostrar en vista)
        $data['mesActual'] = strftime('%B %Y', strtotime($hoy)) ?: date('m/Y', strtotime($hoy));

        return view('rutinas/list_asignaciones', $data);
    }

    /**
     * Calcula % cumplimiento del mes (hasta hoy) por user+actividad
     * Para frecuencia L-V solo cuenta días hábiles, para 'diaria' todos los días
     */
    private function calcularCumplimientoMes(\CodeIgniter\Database\BaseConnection $db, string $desde, string $hasta, array $asignaciones): array
    {
        if (empty($asignaciones)) return [];

        // Días totales y hábiles entre desde y hasta (inclusivo)
        $diasTotales = 0;
        $diasHabiles = 0;
        $cursor = strtotime($desde);
        $fin = strtotime($hasta);
        while ($cursor <= $fin) {
            $diasTotales++;
            $dow = (int) date('N', $cursor);
            if ($dow <= 5) $diasHabiles++;
            $cursor += 86400;
        }

        // IDs únicos para query
        $userIds = array_unique(array_column($asignaciones, 'id_users'));
        $actIds  = array_unique(array_column($asignaciones, 'id_actividad'));
        if (empty($userIds) || empty($actIds)) return [];

        // Conteo de registros completados por user+actividad en el rango
        $rows = $db->table('rutinas_registros')
            ->select('id_users, id_actividad, COUNT(*) as completados')
            ->where('completada', 1)
            ->where('fecha >=', $desde)
            ->where('fecha <=', $hasta)
            ->whereIn('id_users', $userIds)
            ->whereIn('id_actividad', $actIds)
            ->groupBy('id_users, id_actividad')
            ->get()->getResultArray();

        $regs = [];
        foreach ($rows as $r) {
            $regs[$r['id_users'].'_'.$r['id_actividad']] = (int) $r['completados'];
        }

        // Calcular % por asignación según frecuencia
        $resultado = [];
        foreach ($asignaciones as $a) {
            $key = $a['id_users'].'_'.$a['id_actividad'];
            $esperados = ($a['frecuencia'] === 'diaria') ? $diasTotales : $diasHabiles;
            $done = $regs[$key] ?? 0;
            $pct = $esperados > 0 ? round(($done / $esperados) * 100) : 0;
            if ($pct > 100) $pct = 100;
            $resultado[$key] = ['done' => $done, 'esperados' => $esperados, 'pct' => $pct];
        }

        return $resultado;
    }

    public function addAsignacionPost()
    {
        $idUsers     = $this->request->getPost('id_users'); // puede ser array o int
        $actividades = $this->request->getPost('actividades'); // array de ids

        if (empty($idUsers) || empty($actividades)) {
            return redirect()->back()->with('error', 'Selecciona al menos un usuario y una actividad.');
        }

        // Soportar N usuarios x N actividades
        $idUsers = is_array($idUsers) ? $idUsers : [$idUsers];

        $insertadas = 0;
        foreach ($idUsers as $idU) {
            $idU = (int) $idU;
            if (!$idU) continue;
            foreach ($actividades as $idAct) {
                $existe = $this->asignacionModel
                    ->where('id_users', $idU)
                    ->where('id_actividad', $idAct)
                    ->first();
                if (! $existe) {
                    $this->asignacionModel->insert([
                        'id_users'     => $idU,
                        'id_actividad' => $idAct,
                        'activa'       => 1,
                    ]);
                    $insertadas++;
                }
            }
        }

        return redirect()->to('/rutinas/asignaciones')
            ->with('success', "$insertadas asignacion(es) creada(s).");
    }

    public function deleteAsignacion($id)
    {
        $this->asignacionModel->delete($id);
        return redirect()->to('/rutinas/asignaciones')->with('success', 'Asignacion eliminada.');
    }

    /**
     * Toggle inline de activa/inactiva (vía AJAX o link directo)
     */
    public function toggleAsignacion($id)
    {
        $a = $this->asignacionModel->find($id);
        if (! $a) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'No existe']);
            }
            return redirect()->to('/rutinas/asignaciones')->with('error', 'Asignacion no existe.');
        }
        $nueva = ((int) $a['activa']) ? 0 : 1;
        $this->asignacionModel->update($id, ['activa' => $nueva]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'activa' => $nueva]);
        }
        return redirect()->to('/rutinas/asignaciones')->with('success', 'Estado actualizado.');
    }

    /**
     * Bulk: eliminar / activar / desactivar varias asignaciones
     */
    public function bulkAsignaciones()
    {
        $ids    = $this->request->getPost('ids'); // array
        $accion = $this->request->getPost('accion'); // delete|activar|desactivar

        if (empty($ids) || !is_array($ids)) {
            return redirect()->to('/rutinas/asignaciones')->with('error', 'No se selecciono ninguna asignacion.');
        }
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($v) => $v > 0);

        $count = 0;
        if ($accion === 'delete') {
            $this->asignacionModel->whereIn('id_asignacion', $ids)->delete();
            $count = count($ids);
            $msg = "$count asignacion(es) eliminada(s).";
        } elseif ($accion === 'activar') {
            $this->asignacionModel->whereIn('id_asignacion', $ids)->set(['activa' => 1])->update();
            $count = count($ids);
            $msg = "$count asignacion(es) activadas.";
        } elseif ($accion === 'desactivar') {
            $this->asignacionModel->whereIn('id_asignacion', $ids)->set(['activa' => 0])->update();
            $count = count($ids);
            $msg = "$count asignacion(es) desactivadas.";
        } else {
            return redirect()->to('/rutinas/asignaciones')->with('error', 'Accion invalida.');
        }

        return redirect()->to('/rutinas/asignaciones')->with('success', $msg);
    }

    // =========================================================
    // CALENDARIO (vista interna, autenticada)
    // =========================================================

    public function calendario()
    {
        $mes  = (int) ($this->request->getGet('mes')  ?: date('n'));
        $anio = (int) ($this->request->getGet('anio') ?: date('Y'));
        $idUser = (int) ($this->request->getGet('usuario') ?: 0);

        // Días hábiles (L-V) del mes
        $diasHabiles = [];
        $totalDias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mes)));
        for ($d = 1; $d <= $totalDias; $d++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
            $dow = (int) date('N', strtotime($fecha)); // 1=Lun .. 5=Vie
            if ($dow <= 5) {
                $diasHabiles[] = [
                    'fecha'     => $fecha,
                    'dia'       => $d,
                    'dia_letra' => ['L','M','X','J','V'][$dow - 1],
                ];
            }
        }

        // Usuarios con asignaciones activas
        $db = \Config\Database::connect();
        $data['usuariosConRutinas'] = $db->query("
            SELECT DISTINCT u.id_users, u.nombre_completo
            FROM rutinas_asignaciones ra
            JOIN users u ON u.id_users = ra.id_users
            WHERE ra.activa = 1
            ORDER BY u.nombre_completo
        ")->getResultArray();

        // Si no hay usuario seleccionado, tomar el primero
        if (! $idUser && ! empty($data['usuariosConRutinas'])) {
            $idUser = (int) $data['usuariosConRutinas'][0]['id_users'];
        }

        // Actividades asignadas al usuario
        $actividades = [];
        if ($idUser) {
            $actividades = $db->query("
                SELECT a.id_actividad, a.nombre, a.peso
                FROM rutinas_asignaciones ra
                JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
                WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
                ORDER BY a.nombre
            ", [$idUser])->getResultArray();
        }

        // Registros del mes
        $registros = [];
        if ($idUser) {
            $rows = $this->registroModel
                ->where('id_users', $idUser)
                ->where('fecha >=', sprintf('%04d-%02d-01', $anio, $mes))
                ->where('fecha <=', sprintf('%04d-%02d-%02d', $anio, $mes, $totalDias))
                ->where('completada', 1)
                ->findAll();
            foreach ($rows as $r) {
                $registros[$r['id_actividad'] . '_' . $r['fecha']] = true;
            }
        }

        // Puntajes diarios
        $puntajeDiario = [];
        $totalPeso = array_sum(array_column($actividades, 'peso'));
        foreach ($diasHabiles as $dh) {
            $sumaPeso = 0;
            foreach ($actividades as $act) {
                $key = $act['id_actividad'] . '_' . $dh['fecha'];
                if (isset($registros[$key])) {
                    $sumaPeso += $act['peso'];
                }
            }
            $puntajeDiario[$dh['fecha']] = $totalPeso > 0
                ? round(($sumaPeso / $totalPeso) * 100)
                : 0;
        }

        // Acumulados semanales
        $semanales = [];
        $semanaActual = null;
        $semanaSum = 0;
        $semanaDias = 0;
        foreach ($diasHabiles as $dh) {
            $semana = (int) date('W', strtotime($dh['fecha']));
            if ($semanaActual !== null && $semana !== $semanaActual) {
                $semanales[$semanaActual] = $semanaDias > 0
                    ? round($semanaSum / $semanaDias)
                    : 0;
                $semanaSum = 0;
                $semanaDias = 0;
            }
            $semanaActual = $semana;
            $semanaSum += $puntajeDiario[$dh['fecha']];
            $semanaDias++;
        }
        if ($semanaActual !== null) {
            $semanales[$semanaActual] = $semanaDias > 0
                ? round($semanaSum / $semanaDias)
                : 0;
        }

        // Acumulado mensual: solo promediar los días que ya pasaron (hasta hoy)
        $hoy = date('Y-m-d');
        $diasPasados = 0;
        $sumaPasados = 0;
        foreach ($diasHabiles as $dh) {
            if ($dh['fecha'] <= $hoy) {
                $diasPasados++;
                $sumaPasados += $puntajeDiario[$dh['fecha']];
            }
        }
        $acumuladoMensual = $diasPasados > 0 ? round($sumaPasados / $diasPasados) : 0;

        $data['diasHabiles']      = $diasHabiles;
        $data['actividades']      = $actividades;
        $data['registros']        = $registros;
        $data['puntajeDiario']    = $puntajeDiario;
        $data['semanales']        = $semanales;
        $data['acumuladoMensual'] = $acumuladoMensual;
        $data['mes']              = $mes;
        $data['anio']             = $anio;
        $data['idUser']           = $idUser;
        $data['hoy']              = $hoy;

        return view('rutinas/calendario', $data);
    }

    // =========================================================
    // VISTA PÚBLICA (token, sin auth) — checklist diario
    // =========================================================

    private function generarTokenRutina(int $userId, string $fecha): string
    {
        return substr(hash('sha256', $userId . '|' . $fecha . '|rutinas2026'), 0, 24);
    }

    public function checklistPublico(int $userId, string $fecha, string $token)
    {
        // Validar token
        $esperado = $this->generarTokenRutina($userId, $fecha);
        if (! hash_equals($esperado, $token)) {
            return view('rutinas/checklist_error', ['mensaje' => 'Enlace no válido.']);
        }

        // Validar que sea día hábil
        $dow = (int) date('N', strtotime($fecha));
        if ($dow > 5) {
            return view('rutinas/checklist_error', ['mensaje' => 'No es un día hábil.']);
        }

        // Obtener usuario
        $usuario = $this->userModel->find($userId);
        if (! $usuario) {
            return view('rutinas/checklist_error', ['mensaje' => 'Usuario no encontrado.']);
        }

        // Actividades asignadas
        $db = \Config\Database::connect();
        $actividades = $db->query("
            SELECT a.id_actividad, a.nombre, a.descripcion, a.peso
            FROM rutinas_asignaciones ra
            JOIN rutinas_actividades a ON a.id_actividad = ra.id_actividad
            WHERE ra.id_users = ? AND ra.activa = 1 AND a.activa = 1
            ORDER BY a.nombre
        ", [$userId])->getResultArray();

        // Registros ya completados hoy
        $completados = [];
        $rows = $this->registroModel
            ->where('id_users', $userId)
            ->where('fecha', $fecha)
            ->where('completada', 1)
            ->findAll();
        foreach ($rows as $r) {
            $completados[$r['id_actividad']] = true;
        }

        return view('rutinas/checklist_publico', [
            'usuario'     => $usuario,
            'fecha'       => $fecha,
            'token'       => $token,
            'actividades' => $actividades,
            'completados' => $completados,
        ]);
    }

    public function updateChecklistPublico()
    {
        $userId = (int) $this->request->getPost('user_id');
        $fecha  = $this->request->getPost('fecha');
        $token  = $this->request->getPost('token');
        $idAct  = (int) $this->request->getPost('id_actividad');

        // Validar token
        $esperado = $this->generarTokenRutina($userId, $fecha);
        if (! hash_equals($esperado, $token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Token inválido']);
        }

        // Validar que la actividad pertenece al usuario
        $asignacion = $this->asignacionModel
            ->where('id_users', $userId)
            ->where('id_actividad', $idAct)
            ->where('activa', 1)
            ->first();
        if (! $asignacion) {
            return $this->response->setJSON(['success' => false, 'message' => 'Actividad no asignada']);
        }

        // Verificar si ya existe registro
        $existe = $this->registroModel
            ->where('id_users', $userId)
            ->where('id_actividad', $idAct)
            ->where('fecha', $fecha)
            ->first();

        if ($existe) {
            // Ya marcado, no hacer nada
            return $this->response->setJSON(['success' => true]);
        }

        // Insertar registro
        $this->registroModel->insert([
            'id_users'         => $userId,
            'id_actividad'     => $idAct,
            'fecha'            => $fecha,
            'completada'       => 1,
            'hora_completado'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true]);
    }
}
