<?php

namespace App\Controllers;

use App\Models\ActividadModel;
use App\Models\CategoriaActividadModel;
use App\Models\ActividadComentarioModel;
use App\Models\ActividadHistorialModel;
use App\Models\ActividadArchivoModel;
use App\Models\UserModel;
use App\Models\AreaModel;
use App\Libraries\NotificadorActividades;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class ActividadController extends BaseController
{
    protected $actividadModel;
    protected $categoriaModel;
    protected $comentarioModel;
    protected $historialModel;
    protected $archivoModel;
    protected $userModel;
    protected $areaModel;
    protected $notificador;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);

        $this->actividadModel  = new ActividadModel();
        $this->categoriaModel  = new CategoriaActividadModel();
        $this->comentarioModel = new ActividadComentarioModel();
        $this->historialModel  = new ActividadHistorialModel();
        $this->archivoModel    = new ActividadArchivoModel();
        $this->userModel       = new UserModel();
        $this->areaModel       = new AreaModel();
        $this->notificador     = new NotificadorActividades();

        // Verificar vencimientos una vez al día (sin cron)
        $this->verificarVencimientosDiarios();
    }

    /**
     * Verifica actividades vencidas/próximas a vencer (una vez al día)
     * Alternativa al cron: se ejecuta cuando alguien accede al sistema
     */
    protected function verificarVencimientosDiarios()
    {
        $cacheKey = 'ultima_verificacion_vencimientos';
        $cache = \Config\Services::cache();

        $ultimaVerificacion = $cache->get($cacheKey);
        $hoy = date('Y-m-d');

        // Si ya se verificó hoy, no hacer nada
        if ($ultimaVerificacion === $hoy) {
            return;
        }

        // Marcar como verificado para hoy
        $cache->save($cacheKey, $hoy, 86400); // 24 horas

        // Buscar actividades que vencen mañana
        $manana = date('Y-m-d', strtotime('+1 day'));
        $actividadesProximas = $this->actividadModel
            ->where('fecha_limite', $manana)
            ->whereNotIn('estado', ['completada', 'cancelada'])
            ->findAll();

        foreach ($actividadesProximas as $act) {
            $this->notificador->notificarVencimiento($act, false);
        }

        // Buscar actividades vencidas (no notificadas aún)
        $actividadesVencidas = $this->actividadModel
            ->where('fecha_limite <', $hoy)
            ->whereNotIn('estado', ['completada', 'cancelada'])
            ->groupStart()
                ->where('notificado_vencimiento', null)
                ->orWhere('notificado_vencimiento', 0)
            ->groupEnd()
            ->findAll();

        foreach ($actividadesVencidas as $act) {
            $this->notificador->notificarVencimiento($act, true);
            // Marcar como notificado para no enviar repetidamente
            $this->actividadModel->update($act['id_actividad'], ['notificado_vencimiento' => 1]);
        }

        log_message('info', 'Verificación de vencimientos ejecutada: ' . count($actividadesProximas) . ' próximas, ' . count($actividadesVencidas) . ' vencidas');
    }

    /**
     * Tablero Kanban por estado
     */
    public function tableroEstado()
    {
        $filtros = [
            'id_asignado'          => $this->request->getGet('responsable'),
            'id_creador'           => $this->request->getGet('creador'),
            'prioridad'            => $this->request->getGet('prioridad'),
            'id_categoria'         => $this->request->getGet('categoria'),
            'fecha_limite_desde'   => $this->request->getGet('fecha_desde'),
            'fecha_limite_hasta'   => $this->request->getGet('fecha_hasta'),
            'busqueda'             => $this->request->getGet('busqueda'),
            'vencidas'             => $this->request->getGet('vencidas'),
            'proximas_vencer'      => $this->request->getGet('proximas'),
            'estado'               => $this->request->getGet('estado'),
            'esperando_revision'   => $this->request->getGet('esperando_revision')
        ];

        $data = [
            'tablero'         => $this->actividadModel->getActividadesPorEstado($filtros),
            'resumen'         => $this->actividadModel->getResumenTablero($filtros),
            'resumenCreador'  => $this->actividadModel->getResumenComoCreador(session()->get('id_users')),
            'usuarios'        => $this->userModel->where('activo', 1)->orderBy('nombre_completo')->findAll(),
            'categorias'      => $this->categoriaModel->getActivas(),
            'filtros'         => $filtros
        ];

        return view('actividades/tablero_estado', $data);
    }

    /**
     * Tablero por responsable
     */
    public function tableroResponsable()
    {
        $filtros = [
            'estado'      => $this->request->getGet('estado'),
            'prioridad'   => $this->request->getGet('prioridad'),
            'id_categoria' => $this->request->getGet('categoria')
        ];

        $data = [
            'porResponsable' => $this->actividadModel->getActividadesPorResponsable($filtros),
            'categorias'     => $this->categoriaModel->getActivas(),
            'filtros'        => $filtros
        ];

        return view('actividades/tablero_responsable', $data);
    }

    /**
     * Lista de actividades (tabla tradicional)
     */
    public function listActividades()
    {
        $data = [
            'actividades' => $this->actividadModel->getActividadesCompletas(),
            'categorias'  => $this->categoriaModel->getActivas()
        ];

        return view('actividades/list_actividades', $data);
    }

    /**
     * Formulario para crear actividad
     */
    public function addActividad()
    {
        $data = [
            'categorias' => $this->categoriaModel->getActivas(),
            'usuarios'   => $this->userModel->where('activo', 1)->orderBy('nombre_completo')->findAll(),
            'areas'      => $this->areaModel->where('estado_area', 'activa')->findAll()
        ];

        return view('actividades/add_actividad', $data);
    }

    /**
     * Procesar creación de actividad
     */
    public function addActividadPost()
    {
        $rules = [
            'titulo'      => 'required|min_length[3]|max_length[255]',
            'descripcion' => 'permit_empty',
            'prioridad'   => 'required|in_list[baja,media,alta,urgente]',
            'fecha_limite' => 'permit_empty|valid_date[Y-m-d]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = [
            'codigo'             => $this->actividadModel->generarCodigo(),
            'titulo'             => $this->request->getPost('titulo'),
            'descripcion'        => $this->request->getPost('descripcion'),
            'id_categoria'       => $this->request->getPost('id_categoria') ?: null,
            'id_usuario_creador' => session()->get('id_users'),
            'id_usuario_asignado' => $this->request->getPost('id_usuario_asignado') ?: null,
            'id_area'            => $this->request->getPost('id_area') ?: null,
            'prioridad'          => $this->request->getPost('prioridad'),
            'estado'             => 'pendiente',
            'fecha_limite'       => $this->request->getPost('fecha_limite') ?: null,
            'observaciones'      => $this->request->getPost('observaciones'),
            'requiere_revision'  => $this->request->getPost('requiere_revision') ? 1 : 0
        ];

        $this->actividadModel->insert($data);
        $idActividad = $this->actividadModel->getInsertID();
        $data['id_actividad'] = $idActividad;

        // Registrar en historial
        $this->historialModel->insert([
            'id_actividad'   => $idActividad,
            'id_usuario'     => session()->get('id_users'),
            'campo'          => 'creacion',
            'valor_anterior' => null,
            'valor_nuevo'    => 'Actividad creada',
            'created_at'     => date('Y-m-d H:i:s')
        ]);

        // Notificar al usuario asignado
        if (!empty($data['id_usuario_asignado'])) {
            $this->notificador->notificarAsignacion($data, $data['id_usuario_asignado']);
        }

        return redirect()->to('/actividades/tablero')
            ->with('success', 'Actividad creada exitosamente. Código: ' . $data['codigo']);
    }

    /**
     * Ver detalle de actividad
     */
    public function viewActividad($id)
    {
        $actividad = $this->actividadModel->getActividadesCompletas(['id_actividad' => $id]);

        if (empty($actividad)) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }

        $actividad = $actividad[0] ?? $this->actividadModel->find($id);

        $data = [
            'actividad'   => $actividad,
            'comentarios' => $this->comentarioModel->getComentariosPorActividad($id),
            'historial'   => $this->historialModel->getHistorialPorActividad($id),
            'archivos'    => $this->archivoModel->getArchivosPorActividad($id),
            'usuarios'    => $this->userModel->where('activo', 1)->findAll()
        ];

        return view('actividades/view_actividad', $data);
    }

    /**
     * Verifica si el usuario puede editar/eliminar la actividad
     * Solo el creador o superadmin (rol_id = 1) pueden hacerlo
     */
    protected function puedeEditarActividad($actividad): bool
    {
        $idUsuario = session()->get('id_users');
        $rolId = session()->get('rol_id');

        return ($idUsuario == $actividad['id_usuario_creador']) || ($rolId == 1);
    }

    /**
     * Formulario para editar actividad
     */
    public function editActividad($id)
    {
        $actividad = $this->actividadModel->find($id);
        if (!$actividad) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }

        // Verificar permisos
        if (!$this->puedeEditarActividad($actividad)) {
            return redirect()->to('/actividades/ver/' . $id)
                ->with('error', 'No tienes permisos para editar esta actividad.');
        }

        $data = [
            'actividad'  => $actividad,
            'categorias' => $this->categoriaModel->getActivas(),
            'usuarios'   => $this->userModel->where('activo', 1)->orderBy('nombre_completo')->findAll(),
            'areas'      => $this->areaModel->where('estado_area', 'activa')->findAll()
        ];

        return view('actividades/edit_actividad', $data);
    }

    /**
     * Procesar edición de actividad
     */
    public function editActividadPost($id)
    {
        $actividad = $this->actividadModel->find($id);
        if (!$actividad) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }

        // Verificar permisos
        if (!$this->puedeEditarActividad($actividad)) {
            return redirect()->to('/actividades/ver/' . $id)
                ->with('error', 'No tienes permisos para editar esta actividad.');
        }

        $rules = [
            'titulo'      => 'required|min_length[3]|max_length[255]',
            'prioridad'   => 'required|in_list[baja,media,alta,urgente]',
            'estado'      => 'required|in_list[pendiente,en_progreso,en_revision,completada,cancelada]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        // Validar restriccion: solo el creador puede cancelar
        $nuevoEstado = $this->request->getPost('estado');
        if ($nuevoEstado === 'cancelada') {
            $idUsuario = session()->get('id_users');
            $rolId = session()->get('rol_id');
            if ($idUsuario != $actividad['id_usuario_creador'] && $rolId != 1) {
                return redirect()->back()
                    ->with('error', 'Solo el creador de la actividad puede cancelarla.')
                    ->withInput();
            }
        }

        // Validar restriccion de revision al editar
        if ($nuevoEstado === 'completada' && !empty($actividad['requiere_revision'])) {
            $idUsuario = session()->get('id_users');
            $rolId = session()->get('rol_id');
            $esCreador = ($idUsuario == $actividad['id_usuario_creador']);
            $esSuperAdmin = ($rolId == 1);

            if (!$esCreador && !$esSuperAdmin) {
                return redirect()->back()
                    ->with('error', 'Esta actividad requiere revision. Solo el creador puede marcarla como completada.')
                    ->withInput();
            }
        }

        $camposActualizar = [
            'titulo', 'descripcion', 'id_categoria', 'id_usuario_asignado',
            'id_area', 'prioridad', 'estado', 'fecha_limite',
            'porcentaje_avance', 'observaciones'
        ];

        $dataUpdate = [];
        foreach ($camposActualizar as $campo) {
            $valor = $this->request->getPost($campo);
            $valorAnterior = $actividad[$campo] ?? null;

            if ($valor !== null && $valor != $valorAnterior) {
                $dataUpdate[$campo] = $valor ?: null;

                // Registrar cambio en historial
                $this->historialModel->insert([
                    'id_actividad'   => $id,
                    'id_usuario'     => session()->get('id_users'),
                    'campo'          => $campo,
                    'valor_anterior' => $valorAnterior,
                    'valor_nuevo'    => $valor,
                    'created_at'     => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Manejar checkbox requiere_revision (solo si el usuario puede editarlo)
        $idUsuario = session()->get('id_users');
        $rolId = session()->get('rol_id');
        if ($idUsuario == $actividad['id_usuario_creador'] || $rolId == 1) {
            $nuevoRequiereRevision = $this->request->getPost('requiere_revision') ? 1 : 0;
            $valorAnteriorRevision = (int)($actividad['requiere_revision'] ?? 0);
            if ($nuevoRequiereRevision !== $valorAnteriorRevision) {
                $dataUpdate['requiere_revision'] = $nuevoRequiereRevision;
                $this->historialModel->insert([
                    'id_actividad'   => $id,
                    'id_usuario'     => $idUsuario,
                    'campo'          => 'requiere_revision',
                    'valor_anterior' => $valorAnteriorRevision ? 'Si' : 'No',
                    'valor_nuevo'    => $nuevoRequiereRevision ? 'Si' : 'No',
                    'created_at'     => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Manejar fechas especiales
        $nuevoEstado = $this->request->getPost('estado');
        if ($nuevoEstado === 'en_progreso' && empty($actividad['fecha_inicio'])) {
            $dataUpdate['fecha_inicio'] = date('Y-m-d H:i:s');
        }
        if (in_array($nuevoEstado, ['completada', 'cancelada']) && empty($actividad['fecha_cierre'])) {
            $dataUpdate['fecha_cierre'] = date('Y-m-d H:i:s');
        }

        if (!empty($dataUpdate)) {
            $this->actividadModel->update($id, $dataUpdate);

            // Notificar si cambió el estado
            $nuevoEstadoPost = $this->request->getPost('estado');
            $estadoAnterior = $actividad['estado'];
            if ($nuevoEstadoPost !== $estadoAnterior) {
                $actividadActualizada = $this->actividadModel->find($id);
                $this->notificador->notificarCambioEstado(
                    $actividadActualizada,
                    $estadoAnterior,
                    $nuevoEstadoPost,
                    session()->get('id_users')
                );
            }

            // Notificar si se asignó a alguien nuevo
            $nuevoAsignado = $this->request->getPost('id_usuario_asignado');
            $asignadoAnterior = $actividad['id_usuario_asignado'];
            if (!empty($nuevoAsignado) && $nuevoAsignado != $asignadoAnterior) {
                $actividadActualizada = $this->actividadModel->find($id);
                $this->notificador->notificarAsignacion($actividadActualizada, $nuevoAsignado);
            }
        }

        return redirect()->to('/actividades/ver/' . $id)
            ->with('success', 'Actividad actualizada correctamente.');
    }

    /**
     * Cambiar estado via AJAX (para drag & drop)
     */
    public function cambiarEstadoAjax()
    {
        // Permitir peticiones POST (AJAX o normales)
        // Nota: getMethod() puede devolver 'post' o 'POST' segun la version de CI4
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Metodo no permitido: ' . $this->request->getMethod()
            ]);
        }

        $idActividad = $this->request->getPost('id_actividad');
        $nuevoEstado = $this->request->getPost('estado');

        // Validar datos recibidos
        if (empty($idActividad) || empty($nuevoEstado)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
        }

        $estadosValidos = ['pendiente', 'en_progreso', 'en_revision', 'completada', 'cancelada'];
        if (!in_array($nuevoEstado, $estadosValidos)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Estado invalido: ' . $nuevoEstado
            ]);
        }

        // Obtener actividad y estado anterior
        $actividad = $this->actividadModel->find($idActividad);
        if (!$actividad) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Actividad no encontrada'
            ]);
        }

        $estadoAnterior = $actividad['estado'] ?? '';

        // Validar restriccion: solo el creador puede cancelar
        if ($nuevoEstado === 'cancelada') {
            $idUsuario = session()->get('id_users');
            $rolId = session()->get('rol_id');
            if ($idUsuario != $actividad['id_usuario_creador'] && $rolId != 1) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Solo el creador de la actividad puede cancelarla.'
                ]);
            }
        }

        // Validar restriccion de revision: si requiere_revision y el usuario no es el creador, no puede completar
        if ($nuevoEstado === 'completada' && !empty($actividad['requiere_revision'])) {
            $idUsuario = session()->get('id_users');
            $rolId = session()->get('rol_id');
            $esCreador = ($idUsuario == $actividad['id_usuario_creador']);
            $esSuperAdmin = ($rolId == 1);

            if (!$esCreador && !$esSuperAdmin) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Esta actividad requiere revision. Solo el creador puede marcarla como completada.'
                ]);
            }
        }

        // Cambiar estado
        $resultado = $this->actividadModel->cambiarEstado(
            $idActividad,
            $nuevoEstado,
            session()->get('id_users')
        );

        // Notificar el cambio de estado
        if ($resultado && $estadoAnterior !== $nuevoEstado) {
            $actividadActualizada = $this->actividadModel->find($idActividad);
            $this->notificador->notificarCambioEstado(
                $actividadActualizada,
                $estadoAnterior,
                $nuevoEstado,
                session()->get('id_users')
            );
        }

        return $this->response->setJSON([
            'success' => $resultado,
            'message' => $resultado ? 'Estado actualizado correctamente' : 'Error al actualizar el estado'
        ]);
    }

    /**
     * Agregar comentario via AJAX
     */
    public function agregarComentarioAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Petición inválida']);
        }

        $idActividad = $this->request->getPost('id_actividad');
        $comentario  = $this->request->getPost('comentario');
        $esInterno   = $this->request->getPost('es_interno') ? 1 : 0;

        if (empty($comentario)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Comentario vacío']);
        }

        $dataComentario = [
            'id_actividad' => $idActividad,
            'id_usuario'   => session()->get('id_users'),
            'comentario'   => $comentario,
            'es_interno'   => $esInterno
        ];

        $this->comentarioModel->insert($dataComentario);

        // Notificar comentario (solo si no es interno)
        if (!$esInterno) {
            $actividad = $this->actividadModel->find($idActividad);
            if ($actividad) {
                $this->notificador->notificarComentario(
                    $actividad,
                    $dataComentario,
                    session()->get('id_users')
                );
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Comentario agregado',
            'usuario' => session()->get('nombre_completo'),
            'fecha'   => date('d/m/Y H:i')
        ]);
    }

    /**
     * Eliminar actividad
     */
    public function deleteActividad($id)
    {
        $actividad = $this->actividadModel->find($id);
        if (!$actividad) {
            throw new PageNotFoundException("Actividad con ID $id no existe");
        }

        // Verificar permisos
        if (!$this->puedeEditarActividad($actividad)) {
            return redirect()->to('/actividades/ver/' . $id)
                ->with('error', 'No tienes permisos para eliminar esta actividad.');
        }

        $this->actividadModel->delete($id);

        return redirect()->to('/actividades/tablero')
            ->with('success', 'Actividad eliminada correctamente.');
    }

    /**
     * Dashboard de estadísticas
     */
    public function dashboard()
    {
        $data = [
            'estadisticas' => $this->actividadModel->getEstadisticas(),
            'tablero'      => $this->actividadModel->getActividadesPorEstado()
        ];

        return view('actividades/dashboard', $data);
    }

    /**
     * Mis actividades (para el usuario logueado)
     */
    public function misActividades()
    {
        $idUsuario = session()->get('id_users');

        $data = [
            'asignadas' => $this->actividadModel->getActividadesCompletas(['id_asignado' => $idUsuario]),
            'creadas'   => $this->actividadModel->getActividadesCompletas(['id_creador' => $idUsuario])
        ];

        return view('actividades/mis_actividades', $data);
    }

    /**
     * Subir archivo a una actividad
     */
    public function subirArchivo($idActividad)
    {
        $actividad = $this->actividadModel->find($idActividad);
        if (!$actividad) {
            return $this->response->setJSON(['success' => false, 'message' => 'Actividad no encontrada']);
        }

        $archivo = $this->request->getFile('archivo');

        if (!$archivo || !$archivo->isValid()) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'No se recibio ningun archivo valido']);
            }
            return redirect()->back()->with('error', 'No se recibio ningun archivo valido');
        }

        // Obtener información ANTES de mover (el archivo temporal se elimina después de move)
        $nombreOriginal = $archivo->getClientName();
        $extension = $archivo->getClientExtension();
        $tipoMime = $archivo->getClientMimeType(); // Usar getClientMimeType en lugar de getMimeType
        $tamanio = $archivo->getSize();

        // Validar tipo y tamaño
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf',
                           'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                           'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                           'text/plain', 'application/zip', 'application/x-rar-compressed',
                           'application/octet-stream', 'application/x-zip-compressed'];

        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($tipoMime, $tiposPermitidos)) {
            $msg = 'Tipo de archivo no permitido (' . $tipoMime . '). Tipos validos: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        if ($tamanio > $maxSize) {
            $msg = 'El archivo excede el tamano maximo permitido (10MB)';
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Generar nombre único
        $nombreServidor = 'act_' . $idActividad . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

        // Mover archivo
        $rutaDestino = WRITEPATH . 'uploads/actividades';

        if (!is_dir($rutaDestino)) {
            mkdir($rutaDestino, 0755, true);
        }

        if ($archivo->move($rutaDestino, $nombreServidor)) {
            // Guardar en base de datos
            $this->archivoModel->insert([
                'id_actividad'    => $idActividad,
                'id_usuario'      => session()->get('id_users'),
                'nombre_original' => $nombreOriginal,
                'nombre_servidor' => $nombreServidor,
                'tipo_mime'       => $tipoMime,
                'tamanio'         => $tamanio
            ]);

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Archivo subido correctamente',
                    'archivo' => [
                        'id' => $this->archivoModel->getInsertID(),
                        'nombre' => $nombreOriginal,
                        'tamanio' => $this->formatearTamanio($tamanio)
                    ]
                ]);
            }
            return redirect()->back()->with('success', 'Archivo subido correctamente');
        }

        $msg = 'Error al guardar el archivo';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => $msg]);
        }
        return redirect()->back()->with('error', $msg);
    }

    /**
     * Descargar archivo
     */
    public function descargarArchivo($idArchivo)
    {
        $archivo = $this->archivoModel->find($idArchivo);
        if (!$archivo) {
            throw new PageNotFoundException('Archivo no encontrado');
        }

        $rutaArchivo = WRITEPATH . 'uploads/actividades/' . $archivo['nombre_servidor'];

        if (!file_exists($rutaArchivo)) {
            throw new PageNotFoundException('El archivo no existe en el servidor');
        }

        return $this->response->download($rutaArchivo, null)->setFileName($archivo['nombre_original']);
    }

    /**
     * Eliminar archivo
     */
    public function eliminarArchivo($idArchivo)
    {
        $archivo = $this->archivoModel->find($idArchivo);
        if (!$archivo) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Archivo no encontrado']);
            }
            return redirect()->back()->with('error', 'Archivo no encontrado');
        }

        // Eliminar archivo físico
        $rutaArchivo = WRITEPATH . 'uploads/actividades/' . $archivo['nombre_servidor'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }

        // Eliminar registro
        $this->archivoModel->delete($idArchivo);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => 'Archivo eliminado']);
        }
        return redirect()->back()->with('success', 'Archivo eliminado correctamente');
    }

    /**
     * Formatear tamaño de archivo
     */
    protected function formatearTamanio($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
