<?php

namespace App\Controllers;

use App\Models\CuentaCobroModel;
use App\Models\CentroCostoConciliacionModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CuentaCobroController extends BaseController
{
    protected $cuentaCobroModel;
    protected $centroCostoModel;

    private const MAX_SIZE_MB = 10;
    private const UPLOAD_BASE = WRITEPATH . 'uploads/cuentas_cobro';

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->cuentaCobroModel = new CuentaCobroModel();
        $this->centroCostoModel = new CentroCostoConciliacionModel();
    }

    /**
     * Listado con filtros y cards de resumen
     */
    public function index()
    {
        $db = \Config\Database::connect();

        $anio    = $this->request->getGet('anio') ?: date('Y');
        $estado  = $this->request->getGet('estado');
        $centro  = $this->request->getGet('centro');
        $busqueda = trim($this->request->getGet('busqueda') ?? '');

        // Años disponibles
        $aniosRows = $db->table('tbl_cuenta_cobro')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anios'] = array_column($aniosRows, 'anio') ?: [(int)date('Y')];
        $data['anioActual']    = (int) $anio;
        $data['filtroEstado']  = $estado;
        $data['filtroCentro']  = $centro;
        $data['filtroBusqueda']= $busqueda;

        // Cards de resumen (respetan filtro de año)
        $resumen = $db->table('tbl_cuenta_cobro')
            ->select('estado, COUNT(*) cnt, SUM(valor_neto_a_pagar) total_neto, SUM(valor_bruto) total_bruto')
            ->where('anio', (int) $anio)
            ->groupBy('estado')
            ->get()->getResultArray();
        $data['resumenEstado'] = [];
        foreach ($resumen as $r) $data['resumenEstado'][$r['estado']] = $r;

        // Tabla con filtros
        $builder = $db->table('tbl_cuenta_cobro cc')
            ->select('cc.*, c.centro_costo')
            ->join('tbl_centros_costo c', 'c.id_centro_costo = cc.id_centro_costo', 'left')
            ->where('cc.anio', (int) $anio)
            ->orderBy('cc.fecha_gasto', 'DESC')
            ->orderBy('cc.id_cuenta_cobro', 'DESC');

        if ($estado) $builder->where('cc.estado', $estado);
        if ($centro) $builder->where('cc.id_centro_costo', (int) $centro);
        if ($busqueda) {
            $builder->groupStart()
                ->like('cc.nombre_cobrador', $busqueda)
                ->orLike('cc.documento', $busqueda)
                ->orLike('cc.descripcion_servicio', $busqueda)
            ->groupEnd();
        }

        $data['cuentas']  = $builder->get()->getResultArray();
        $data['centros']  = $this->centroCostoModel->orderBy('centro_costo','ASC')->findAll();

        return view('conciliaciones/list_cuenta_cobro', $data);
    }

    /**
     * Form de creación
     */
    public function crear()
    {
        $data['centros'] = $this->centroCostoModel->orderBy('centro_costo','ASC')->findAll();
        $db = \Config\Database::connect();
        $data['clasificaciones'] = $db->table('tbl_clasificacion_costos')
            ->orderBy('categoria', 'ASC')->orderBy('llave_item','ASC')
            ->get()->getResultArray();
        $data['terceros'] = $db->table('tbl_terceros')
            ->select('id_tercero, tipo_documento, documento, nombre, email, telefono, banco, tipo_cuenta, numero_cuenta, titular_cuenta')
            ->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->get()->getResultArray();
        return view('conciliaciones/add_cuenta_cobro', $data);
    }

    /**
     * POST: crear cuenta de cobro
     */
    public function crearPost()
    {
        $file = $this->request->getFile('archivo_pdf');
        $datos = $this->extraerDatosForm();

        $errores = $this->validar($datos, $file, true);
        if (! empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }

        // Procesar PDF
        $pdfInfo = $this->guardarPdf($file, $datos['fecha_gasto']);
        if (isset($pdfInfo['error'])) {
            return redirect()->back()->withInput()->with('errors', [$pdfInfo['error']]);
        }

        // Detectar duplicado por hash (warning)
        $dup = $this->cuentaCobroModel->where('hash_pdf', $pdfInfo['hash'])->first();

        $datos['ruta_pdf']            = $pdfInfo['ruta'];
        $datos['nombre_pdf_original'] = $pdfInfo['nombre_original'];
        $datos['hash_pdf']            = $pdfInfo['hash'];
        $datos['tamano_pdf']          = $pdfInfo['tamano'];
        $datos['creado_por']          = session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema';

        $id = $this->cuentaCobroModel->insert($datos, true);

        $msg = "Cuenta de cobro #{$id} creada correctamente.";
        if ($dup) {
            $msg .= " ⚠️ El PDF coincide en hash con la cuenta #{$dup['id_cuenta_cobro']} (posible duplicado).";
        }

        return redirect()->to("/conciliaciones/cuentas-cobro/ver/{$id}")
            ->with('success', $msg);
    }

    /**
     * Ver detalle + preview PDF
     */
    public function ver($id)
    {
        $cc = $this->cuentaCobroModel->find((int) $id);
        if (! $cc) {
            return redirect()->to('/conciliaciones/cuentas-cobro')
                ->with('errors', ['Cuenta de cobro no encontrada']);
        }
        $db = \Config\Database::connect();
        $cc['centro_costo'] = $db->table('tbl_centros_costo')
            ->where('id_centro_costo', $cc['id_centro_costo'])
            ->get()->getRow()->centro_costo ?? '';
        if ($cc['id_clasificacion']) {
            $cc['clasificacion'] = $db->table('tbl_clasificacion_costos')
                ->where('id_clasificacion', $cc['id_clasificacion'])
                ->get()->getRow();
        }
        $data['cc'] = $cc;
        return view('conciliaciones/view_cuenta_cobro', $data);
    }

    /**
     * Form de edición
     */
    public function editar($id)
    {
        $cc = $this->cuentaCobroModel->find((int) $id);
        if (! $cc) {
            return redirect()->to('/conciliaciones/cuentas-cobro')->with('errors', ['No encontrada']);
        }
        $data['cc'] = $cc;
        $data['centros'] = $this->centroCostoModel->orderBy('centro_costo','ASC')->findAll();
        $db = \Config\Database::connect();
        $data['clasificaciones'] = $db->table('tbl_clasificacion_costos')
            ->orderBy('categoria','ASC')->orderBy('llave_item','ASC')->get()->getResultArray();
        $data['terceros'] = $db->table('tbl_terceros')
            ->select('id_tercero, tipo_documento, documento, nombre, email, telefono, banco, tipo_cuenta, numero_cuenta, titular_cuenta')
            ->where('activo', 1)
            ->orderBy('nombre', 'ASC')
            ->get()->getResultArray();
        return view('conciliaciones/edit_cuenta_cobro', $data);
    }

    /**
     * POST: actualizar (con reemplazo opcional de PDF)
     */
    public function editarPost($id)
    {
        $cc = $this->cuentaCobroModel->find((int) $id);
        if (! $cc) {
            return redirect()->to('/conciliaciones/cuentas-cobro')->with('errors', ['No encontrada']);
        }

        $datos = $this->extraerDatosForm();
        $file  = $this->request->getFile('archivo_pdf');
        $reemplazarPdf = $file && $file->isValid();

        $errores = $this->validar($datos, $file, $reemplazarPdf);
        if (! empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }

        if ($reemplazarPdf) {
            $pdfInfo = $this->guardarPdf($file, $datos['fecha_gasto']);
            if (isset($pdfInfo['error'])) {
                return redirect()->back()->withInput()->with('errors', [$pdfInfo['error']]);
            }
            // Borrar PDF anterior (best effort)
            if ($cc['ruta_pdf'] && is_file($cc['ruta_pdf'])) {
                @unlink($cc['ruta_pdf']);
            }
            $datos['ruta_pdf']            = $pdfInfo['ruta'];
            $datos['nombre_pdf_original'] = $pdfInfo['nombre_original'];
            $datos['hash_pdf']            = $pdfInfo['hash'];
            $datos['tamano_pdf']          = $pdfInfo['tamano'];
        }

        $this->cuentaCobroModel->update((int) $id, $datos);
        return redirect()->to("/conciliaciones/cuentas-cobro/ver/{$id}")
            ->with('success', 'Cuenta de cobro actualizada.');
    }

    /**
     * Marcar como pagada (con datos de pago)
     */
    public function marcarPagada($id)
    {
        $cc = $this->cuentaCobroModel->find((int) $id);
        if (! $cc) return redirect()->back()->with('errors', ['No encontrada']);

        $datos = [
            'estado'                => 'pagada',
            'fecha_pago'            => $this->request->getPost('fecha_pago') ?: date('Y-m-d'),
            'forma_pago'            => $this->request->getPost('forma_pago') ?: 'transferencia',
            'referencia_pago'       => $this->request->getPost('referencia_pago') ?: null,
            'id_cuenta_banco_pago'  => (int) $this->request->getPost('id_cuenta_banco_pago') ?: null,
        ];
        $this->cuentaCobroModel->update((int) $id, $datos);

        return redirect()->to("/conciliaciones/cuentas-cobro/ver/{$id}")
            ->with('success', 'Cuenta marcada como pagada.');
    }

    /**
     * Eliminar (con borrado de PDF)
     */
    public function eliminar($id)
    {
        $cc = $this->cuentaCobroModel->find((int) $id);
        if (! $cc) return redirect()->back()->with('errors', ['No encontrada']);

        if ($cc['ruta_pdf'] && is_file($cc['ruta_pdf'])) {
            @unlink($cc['ruta_pdf']);
        }
        $this->cuentaCobroModel->delete((int) $id);
        return redirect()->to('/conciliaciones/cuentas-cobro')
            ->with('success', "Cuenta #{$id} eliminada.");
    }

    /**
     * Servir PDF inline para preview (iframe)
     */
    public function visualizarPdf($id)
    {
        return $this->servirPdf((int) $id, 'inline');
    }

    /**
     * Servir PDF como descarga (attachment)
     */
    public function descargarPdf($id)
    {
        return $this->servirPdf((int) $id, 'attachment');
    }

    // ─────────────────────────────── HELPERS ───────────────────────────────

    private function servirPdf(int $id, string $disposition)
    {
        $cc = $this->cuentaCobroModel->find($id);
        if (! $cc || ! is_file($cc['ruta_pdf'])) {
            return $this->response->setStatusCode(404)->setBody('PDF no encontrado');
        }
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $cc['nombre_pdf_original'] ?? "cc_{$id}.pdf");
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', "{$disposition}; filename=\"{$nombre}\"")
            ->setHeader('Content-Length', (string) filesize($cc['ruta_pdf']))
            ->setBody(file_get_contents($cc['ruta_pdf']));
    }

    private function extraerDatosForm(): array
    {
        $req = $this->request;
        return [
            'id_tercero'           => (int) $req->getPost('id_tercero') ?: null,
            'tipo_documento'       => $req->getPost('tipo_documento') ?: 'CC',
            'documento'            => trim((string) $req->getPost('documento')),
            'nombre_cobrador'      => trim((string) $req->getPost('nombre_cobrador')),
            'email_cobrador'       => trim((string) $req->getPost('email_cobrador')) ?: null,
            'telefono_cobrador'    => trim((string) $req->getPost('telefono_cobrador')) ?: null,
            'id_centro_costo'      => (int) $req->getPost('id_centro_costo'),
            'id_clasificacion'     => (int) $req->getPost('id_clasificacion') ?: null,
            'descripcion_servicio' => trim((string) $req->getPost('descripcion_servicio')),
            'fecha_gasto'          => $req->getPost('fecha_gasto'),
            'periodo_desde'        => $req->getPost('periodo_desde') ?: null,
            'periodo_hasta'        => $req->getPost('periodo_hasta') ?: null,
            'valor_bruto'          => $this->parseMonto($req->getPost('valor_bruto')),
            'retencion_fuente'     => $this->parseMonto($req->getPost('retencion_fuente')),
            'retencion_iva'        => $this->parseMonto($req->getPost('retencion_iva')),
            'retencion_ica'        => $this->parseMonto($req->getPost('retencion_ica')),
            'otras_deducciones'    => $this->parseMonto($req->getPost('otras_deducciones')),
            'valor_neto_a_pagar'   => $this->parseMonto($req->getPost('valor_neto_a_pagar')),
            'banco_destino'        => trim((string) $req->getPost('banco_destino')) ?: null,
            'tipo_cuenta_destino'  => $req->getPost('tipo_cuenta_destino') ?: null,
            'numero_cuenta_destino'=> trim((string) $req->getPost('numero_cuenta_destino')) ?: null,
            'titular_cuenta'       => trim((string) $req->getPost('titular_cuenta')) ?: null,
            'notas'                => trim((string) $req->getPost('notas')) ?: null,
        ];
    }

    private function validar(array $datos, $file, bool $exigirPdf): array
    {
        $errores = [];
        if (empty($datos['documento']))            $errores[] = 'Documento es obligatorio.';
        if (empty($datos['nombre_cobrador']))      $errores[] = 'Nombre del cobrador es obligatorio.';
        if (empty($datos['id_centro_costo']))      $errores[] = 'Centro de costo es obligatorio.';
        if (empty($datos['descripcion_servicio'])) $errores[] = 'Descripción del servicio es obligatoria.';
        if (empty($datos['fecha_gasto']))          $errores[] = 'Fecha del gasto es obligatoria.';
        if ($datos['valor_bruto'] <= 0)            $errores[] = 'Valor bruto debe ser mayor a 0.';
        if ($datos['valor_neto_a_pagar'] <= 0)     $errores[] = 'Valor neto a pagar debe ser mayor a 0.';

        // Consistencia: bruto - retenciones ≈ neto (tolerancia $1)
        $sumaRet = $datos['retencion_fuente'] + $datos['retencion_iva']
                 + $datos['retencion_ica'] + $datos['otras_deducciones'];
        $netoCalc = $datos['valor_bruto'] - $sumaRet;
        if (abs($netoCalc - $datos['valor_neto_a_pagar']) > 1) {
            $errores[] = sprintf(
                "El valor neto (\$%s) no cuadra con bruto (\$%s) - retenciones (\$%s) = \$%s.",
                number_format($datos['valor_neto_a_pagar'], 0, ',', '.'),
                number_format($datos['valor_bruto'], 0, ',', '.'),
                number_format($sumaRet, 0, ',', '.'),
                number_format($netoCalc, 0, ',', '.')
            );
        }

        // PDF
        if ($exigirPdf) {
            if (! $file || ! $file->isValid()) {
                $errores[] = 'El PDF es obligatorio.';
            } else {
                $ext  = strtolower($file->getExtension());
                $mime = $file->getMimeType();
                if ($ext !== 'pdf' || $mime !== 'application/pdf') {
                    $errores[] = 'Solo se aceptan archivos PDF.';
                }
                if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
                    $errores[] = "El PDF supera el máximo de " . self::MAX_SIZE_MB . " MB.";
                }
            }
        }

        return $errores;
    }

    private function guardarPdf($file, string $fechaGasto): array
    {
        try {
            $ts = strtotime($fechaGasto) ?: time();
            $anio = date('Y', $ts);
            $mes  = date('m', $ts);
            $dir = self::UPLOAD_BASE . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . $mes;
            if (! is_dir($dir)) {
                if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                    return ['error' => "No se pudo crear la carpeta de uploads: {$dir}"];
                }
            }

            $hash = hash_file('sha256', $file->getTempName());
            $shortHash = substr($hash, 0, 12);
            $nombre = 'cc_' . date('Ymd_His') . '_' . $shortHash . '.pdf';
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

    private function parseMonto($val): float
    {
        if ($val === null || $val === '') return 0.0;
        $str = preg_replace('/[\$\s]/', '', (string) $val);
        // Formato colombiano: punto siempre es separador de miles; coma es decimal.
        if (strpos($str, ',') !== false) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            $str = str_replace('.', '', $str);
        }
        return is_numeric($str) ? (float) $str : 0.0;
    }
}
