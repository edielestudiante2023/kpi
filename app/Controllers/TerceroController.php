<?php

namespace App\Controllers;

use App\Models\TerceroModel;
use App\Models\TerceroDocumentoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class TerceroController extends BaseController
{
    private const MAX_SIZE_MB = 10;
    private const UPLOAD_BASE = WRITEPATH . 'uploads/terceros';
    private const TIPOS_VALIDOS = ['rut', 'cedula', 'cert_bancaria'];

    protected $terceroModel;
    protected $documentoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->terceroModel   = new TerceroModel();
        $this->documentoModel = new TerceroDocumentoModel();
    }

    /**
     * Listado con buscador.
     */
    public function index()
    {
        $busqueda = trim($this->request->getGet('busqueda') ?? '');
        $terceros = $this->terceroModel->getListadoConDocumentos($busqueda ?: null);

        // Adjuntar documentos por tercero (para el detalle expandible)
        foreach ($terceros as &$t) {
            $t['documentos'] = $this->documentoModel->getDocumentosTercero((int) $t['id_tercero']);
        }
        unset($t);

        return view('conciliaciones/terceros', [
            'terceros'       => $terceros,
            'filtroBusqueda' => $busqueda,
        ]);
    }

    /**
     * Crear o editar (POST) — mismo endpoint, decide por id_tercero presente.
     */
    public function guardar()
    {
        $idTercero = (int) $this->request->getPost('id_tercero');
        $datos = $this->extraerDatos();
        $errores = $this->validar($datos, $idTercero);

        if (!empty($errores)) {
            return $this->response->setJSON(['ok' => false, 'errors' => $errores])->setStatusCode(400);
        }

        $datos['creado_por'] = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';

        if ($idTercero > 0) {
            $this->terceroModel->update($idTercero, $datos);
            return $this->response->setJSON(['ok' => true, 'id' => $idTercero]);
        }

        $newId = $this->terceroModel->insert($datos, true);
        return $this->response->setJSON(['ok' => true, 'id' => $newId]);
    }

    /**
     * Eliminar tercero (solo si no tiene cuentas de cobro asociadas).
     * Sus documentos se borran en cascada (FK ON DELETE CASCADE) más el unlink físico.
     */
    public function eliminar($id)
    {
        $id = (int) $id;
        $t  = $this->terceroModel->find($id);
        if (!$t) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Tercero no encontrado'])->setStatusCode(404);
        }

        if ($this->terceroModel->contarCuentasCobro($id) > 0) {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'No se puede eliminar: el tercero tiene cuentas de cobro asociadas. Inactívalo en su lugar.',
            ])->setStatusCode(400);
        }

        // Borrar PDFs físicos
        foreach ($this->documentoModel->getDocumentosTercero($id) as $d) {
            if (!empty($d['ruta_pdf']) && is_file($d['ruta_pdf'])) {
                @unlink($d['ruta_pdf']);
            }
        }

        $this->terceroModel->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Subir documento (RUT / cédula / certificación bancaria) — multipart POST.
     */
    public function subirDocumento($idTercero)
    {
        $idTercero = (int) $idTercero;
        $tercero = $this->terceroModel->find($idTercero);
        if (!$tercero) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Tercero no encontrado'])->setStatusCode(404);
        }

        $tipo = $this->request->getPost('tipo');
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Tipo de documento inválido'])->setStatusCode(400);
        }

        $file = $this->request->getFile('archivo');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Archivo inválido'])->setStatusCode(400);
        }
        $ext  = strtolower($file->getExtension());
        $mime = $file->getMimeType();
        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Solo se aceptan PDFs'])->setStatusCode(400);
        }
        if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
            return $this->response->setJSON(['ok' => false, 'error' => 'PDF supera ' . self::MAX_SIZE_MB . ' MB'])->setStatusCode(400);
        }

        $info = $this->guardarPdf($file, $idTercero, $tipo);
        if (isset($info['error'])) {
            return $this->response->setJSON(['ok' => false, 'error' => $info['error']])->setStatusCode(500);
        }

        $id = $this->documentoModel->insert([
            'id_tercero'      => $idTercero,
            'tipo'            => $tipo,
            'nombre_original' => $info['nombre_original'],
            'ruta_pdf'        => $info['ruta'],
            'hash_pdf'        => $info['hash'],
            'tamano_pdf'      => $info['tamano'],
            'subido_por'      => session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema',
        ], true);

        return $this->response->setJSON(['ok' => true, 'id' => $id]);
    }

    /**
     * Eliminar un documento adjunto.
     */
    public function eliminarDocumento($idDocumento)
    {
        $idDocumento = (int) $idDocumento;
        $doc = $this->documentoModel->find($idDocumento);
        if (!$doc) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Documento no encontrado'])->setStatusCode(404);
        }
        if (!empty($doc['ruta_pdf']) && is_file($doc['ruta_pdf'])) {
            @unlink($doc['ruta_pdf']);
        }
        $this->documentoModel->delete($idDocumento);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Servir PDF inline (preview).
     */
    public function verDocumento($idDocumento)
    {
        $doc = $this->documentoModel->find((int) $idDocumento);
        if (!$doc || !is_file($doc['ruta_pdf'])) {
            return $this->response->setStatusCode(404)->setBody('PDF no encontrado');
        }
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $doc['nombre_original'] ?? "doc_{$idDocumento}.pdf");
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', "inline; filename=\"{$nombre}\"")
            ->setHeader('Content-Length', (string) filesize($doc['ruta_pdf']))
            ->setBody(file_get_contents($doc['ruta_pdf']));
    }

    /**
     * Búsqueda AJAX para autocompletar tercero en el form de cuenta de cobro.
     * Devuelve un JSON con los datos básicos + cuenta bancaria.
     */
    public function buscar()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (strlen($q) < 2) return $this->response->setJSON(['items' => []]);

        $db = \Config\Database::connect();
        $rows = $db->table('tbl_terceros')
            ->select('id_tercero, tipo_documento, documento, nombre, email, telefono, banco, tipo_cuenta, numero_cuenta, titular_cuenta')
            ->where('activo', 1)
            ->groupStart()
                ->like('nombre', $q)
                ->orLike('documento', $q)
            ->groupEnd()
            ->orderBy('nombre', 'ASC')
            ->limit(20)
            ->get()->getResultArray();

        return $this->response->setJSON(['items' => $rows]);
    }

    /**
     * Devuelve un tercero por id (JSON) — para autocompletar el form de cuenta de cobro.
     */
    public function obtener($id)
    {
        $t = $this->terceroModel->find((int) $id);
        if (!$t) return $this->response->setJSON(['ok' => false])->setStatusCode(404);
        return $this->response->setJSON(['ok' => true, 'tercero' => $t]);
    }

    // ─────────────────────────────── HELPERS ───────────────────────────────

    private function extraerDatos(): array
    {
        $req = $this->request;
        return [
            'tipo_documento' => $req->getPost('tipo_documento') ?: 'CC',
            'documento'      => trim((string) $req->getPost('documento')),
            'nombre'         => trim((string) $req->getPost('nombre')),
            'email'          => trim((string) $req->getPost('email')) ?: null,
            'telefono'       => trim((string) $req->getPost('telefono')) ?: null,
            'banco'          => trim((string) $req->getPost('banco')) ?: null,
            'tipo_cuenta'    => $req->getPost('tipo_cuenta') ?: null,
            'numero_cuenta'  => trim((string) $req->getPost('numero_cuenta')) ?: null,
            'titular_cuenta' => trim((string) $req->getPost('titular_cuenta')) ?: null,
            'activo'         => (int) ($req->getPost('activo') ?? 1),
            'notas'          => trim((string) $req->getPost('notas')) ?: null,
        ];
    }

    private function validar(array $d, int $idActual): array
    {
        $errores = [];
        if ($d['documento'] === '') $errores[] = 'El documento es obligatorio.';
        if ($d['nombre'] === '')    $errores[] = 'El nombre es obligatorio.';

        // Documento único
        if ($d['documento'] !== '') {
            $b = $this->terceroModel->where('documento', $d['documento']);
            if ($idActual > 0) $b->where('id_tercero !=', $idActual);
            if ($b->first()) {
                $errores[] = "Ya existe un tercero con el documento {$d['documento']}.";
            }
        }
        return $errores;
    }

    private function guardarPdf($file, int $idTercero, string $tipo): array
    {
        try {
            $dir = self::UPLOAD_BASE . DIRECTORY_SEPARATOR . $idTercero . DIRECTORY_SEPARATOR . $tipo;
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                    return ['error' => "No se pudo crear la carpeta: {$dir}"];
                }
            }

            $hash = hash_file('sha256', $file->getTempName());
            $shortHash = substr($hash, 0, 12);
            $nombre = $tipo . '_' . date('Ymd_His') . '_' . $shortHash . '.pdf';
            $file->move($dir, $nombre);

            return [
                'ruta'            => $dir . DIRECTORY_SEPARATOR . $nombre,
                'nombre_original' => $file->getClientName(),
                'hash'            => $hash,
                'tamano'          => filesize($dir . DIRECTORY_SEPARATOR . $nombre),
            ];
        } catch (\Throwable $e) {
            return ['error' => 'Error guardando PDF: ' . $e->getMessage()];
        }
    }
}
