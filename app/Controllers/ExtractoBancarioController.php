<?php

namespace App\Controllers;

use App\Models\ExtractoBancarioModel;
use App\Models\CuentaBancoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ExtractoBancarioController extends BaseController
{
    private const MAX_SIZE_MB = 20;
    private const UPLOAD_BASE = WRITEPATH . 'uploads/extractos_bancarios';

    protected $extractoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->extractoModel = new ExtractoBancarioModel();
    }

    /**
     * Listado con filtros por año y cuenta bancaria.
     */
    public function index()
    {
        $anio          = (int) ($this->request->getGet('anio') ?: date('Y'));
        $idCuentaBanco = (int) ($this->request->getGet('cuenta') ?: 0);

        $db = \Config\Database::connect();
        $cuentas = $db->table('tbl_cuentas_banco')
            ->orderBy('nombre_cuenta', 'ASC')
            ->get()->getResultArray();

        $extractos = $this->extractoModel->getListado($anio, $idCuentaBanco ?: null);
        $anios = $this->extractoModel->getAniosDisponibles() ?: [(int) date('Y')];
        // Asegurar que el año actual esté en la lista
        if (!in_array((int) date('Y'), $anios, true)) {
            array_unshift($anios, (int) date('Y'));
        }

        return view('conciliaciones/extractos_bancarios', [
            'extractos'      => $extractos,
            'cuentas'        => $cuentas,
            'anios'          => $anios,
            'filtroAnio'     => $anio,
            'filtroCuenta'   => $idCuentaBanco,
        ]);
    }

    /**
     * Subir un extracto (POST multipart).
     */
    public function subir()
    {
        $idCuentaBanco = (int) $this->request->getPost('id_cuenta_banco');
        $anio          = (int) $this->request->getPost('anio');
        $mes           = (int) $this->request->getPost('mes');
        $descripcion   = trim((string) $this->request->getPost('descripcion')) ?: null;

        $errores = [];
        if ($idCuentaBanco <= 0) $errores[] = 'Selecciona una cuenta bancaria.';
        if ($anio < 2000 || $anio > 2100) $errores[] = 'Año inválido.';
        if ($mes < 1 || $mes > 12) $errores[] = 'Mes inválido.';

        $file = $this->request->getFile('archivo');
        if (!$file || !$file->isValid()) {
            $errores[] = 'Adjunta un archivo PDF válido.';
        } else {
            $ext  = strtolower($file->getExtension());
            $mime = $file->getMimeType();
            if ($ext !== 'pdf' || $mime !== 'application/pdf') {
                $errores[] = 'Solo se aceptan PDFs.';
            }
            if ($file->getSize() > self::MAX_SIZE_MB * 1024 * 1024) {
                $errores[] = 'El PDF supera ' . self::MAX_SIZE_MB . ' MB.';
            }
        }

        if (!empty($errores)) {
            return redirect()->back()->withInput()->with('errors', $errores);
        }

        $info = $this->guardarPdf($file, $anio, $mes);
        if (isset($info['error'])) {
            return redirect()->back()->withInput()->with('errors', [$info['error']]);
        }

        $this->extractoModel->insert([
            'id_cuenta_banco' => $idCuentaBanco,
            'anio'            => $anio,
            'mes'             => $mes,
            'descripcion'     => $descripcion,
            'nombre_original' => $info['nombre_original'],
            'ruta_pdf'        => $info['ruta'],
            'hash_pdf'        => $info['hash'],
            'tamano_pdf'      => $info['tamano'],
            'subido_por'      => session()->get('nombre_completo') ?? session()->get('email') ?? 'sistema',
        ]);

        return redirect()->to('/conciliaciones/extractos-bancarios?' . http_build_query([
            'anio' => $anio, 'cuenta' => $idCuentaBanco,
        ]))->with('success', 'Extracto subido correctamente.');
    }

    /**
     * Eliminar extracto.
     */
    public function eliminar($id)
    {
        $id = (int) $id;
        $e = $this->extractoModel->find($id);
        if (!$e) return redirect()->back()->with('errors', ['Extracto no encontrado.']);

        if (!empty($e['ruta_pdf']) && is_file($e['ruta_pdf'])) {
            @unlink($e['ruta_pdf']);
        }
        $this->extractoModel->delete($id);
        return redirect()->back()->with('success', 'Extracto eliminado.');
    }

    /**
     * Servir PDF inline (preview).
     */
    public function ver($id)
    {
        return $this->servirPdf((int) $id, 'inline');
    }

    /**
     * Servir PDF como descarga.
     */
    public function descargar($id)
    {
        return $this->servirPdf((int) $id, 'attachment');
    }

    // ─────────────────────────────── HELPERS ───────────────────────────────

    private function servirPdf(int $id, string $disposition)
    {
        $e = $this->extractoModel->find($id);
        if (!$e || !is_file($e['ruta_pdf'])) {
            return $this->response->setStatusCode(404)->setBody('PDF no encontrado');
        }
        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', $e['nombre_original'] ?? "extracto_{$id}.pdf");
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', "{$disposition}; filename=\"{$nombre}\"")
            ->setHeader('Content-Length', (string) filesize($e['ruta_pdf']))
            ->setBody(file_get_contents($e['ruta_pdf']));
    }

    private function guardarPdf($file, int $anio, int $mes): array
    {
        try {
            $dir = self::UPLOAD_BASE . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                    return ['error' => "No se pudo crear la carpeta: {$dir}"];
                }
            }

            $hash = hash_file('sha256', $file->getTempName());
            $shortHash = substr($hash, 0, 12);
            $nombre = 'ext_' . date('Ymd_His') . '_' . $shortHash . '.pdf';
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
