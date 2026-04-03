<?php

namespace App\Controllers;

use App\Models\FacturacionCrudaModel;
use App\Models\MovimientoBancarioCrudoModel;
use App\Models\CuentaBancoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CargaCrudaController extends BaseController
{
    protected $factCrudaModel;
    protected $movCrudoModel;
    protected $cuentaBancoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->factCrudaModel   = new FacturacionCrudaModel();
        $this->movCrudoModel    = new MovimientoBancarioCrudoModel();
        $this->cuentaBancoModel = new CuentaBancoModel();
    }

    // ══════════════════════════════════════
    //  FACTURACIÓN CRUDA
    // ══════════════════════════════════════

    public function uploadFacturacion()
    {
        $db = \Config\Database::connect();
        $data['totalRegistros'] = $db->table('tbl_facturacion_cruda')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_facturacion_cruda')
            ->selectMax('created_at')->get()->getRow()->created_at;
        return view('conciliaciones/upload_facturacion_cruda', $data);
    }

    public function uploadFacturacionPost()
    {
        $file = $this->request->getFile('archivo_csv');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('errors', ['Debe seleccionar un archivo válido.']);
        }

        $ext = strtolower($file->getExtension());
        if ($ext !== 'csv') {
            return redirect()->back()->with('errors', ['Solo se permiten archivos .csv']);
        }

        $handle = fopen($file->getTempName(), 'r');
        if (! $handle) {
            return redirect()->back()->with('errors', ['No se pudo abrir el archivo.']);
        }

        // Leer header
        $header = fgetcsv($handle, 0, ',');
        if (! $header || count($header) < 13) {
            fclose($handle);
            return redirect()->back()->with('errors', ['El archivo debe tener al menos 13 columnas. Se encontraron ' . count($header ?: []) . '.']);
        }

        $insertados = 0;
        $errores    = [];
        $lote       = [];
        $fila       = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $fila++;

            if (count($row) < 13) {
                $errores[] = "Fila {$fila}: menos de 13 columnas, se omite.";
                continue;
            }

            $comprobante = trim($row[0]);
            if (empty($comprobante)) {
                $errores[] = "Fila {$fila}: comprobante vacío, se omite.";
                continue;
            }

            $registro = [
                'comprobante'          => $comprobante,
                'fecha_elaboracion'    => $this->parseDateCSV($row[1]),
                'identificacion'       => $this->cleanInt($row[2]),
                'sucursal'             => trim($row[3]) ?: null,
                'nombre_tercero'       => trim($row[4]),
                'base_gravada'         => $this->cleanDecimal($row[5]),
                'base_exenta'          => $this->cleanDecimal($row[6]),
                'iva'                  => $this->cleanDecimal($row[7]),
                'impoconsumo'          => $this->cleanDecimal($row[8]),
                'ad_valorem'           => $this->cleanDecimal($row[9]),
                'cargo_en_totales'     => $this->cleanDecimal($row[10]),
                'descuento_en_totales' => $this->cleanDecimal($row[11]),
                'total'                => $this->cleanDecimal($row[12]),
            ];

            if (! $registro['fecha_elaboracion']) {
                $errores[] = "Fila {$fila}: fecha elaboración inválida '{$row[1]}', se omite.";
                continue;
            }

            $lote[] = $registro;

            if (count($lote) >= 200) {
                $this->factCrudaModel->insertBatch($lote);
                $insertados += count($lote);
                $lote = [];
            }
        }

        fclose($handle);

        if (! empty($lote)) {
            $this->factCrudaModel->insertBatch($lote);
            $insertados += count($lote);
        }

        $msg = "Se importaron {$insertados} registros de facturación.";
        if (! empty($errores)) {
            $msg .= ' | ' . count($errores) . ' filas con errores.';
        }

        return redirect()->to('/conciliaciones/cruda/facturacion')
            ->with('success', $msg)
            ->with('import_errors', $errores);
    }

    public function listFacturacionCruda()
    {
        $data['registros'] = $this->factCrudaModel
            ->orderBy('fecha_elaboracion', 'DESC')
            ->findAll();
        return view('conciliaciones/list_facturacion_cruda', $data);
    }

    public function truncarFacturacion()
    {
        $db = \Config\Database::connect();
        $db->table('tbl_facturacion_cruda')->truncate();
        return redirect()->to('/conciliaciones/cruda/facturacion')
            ->with('success', 'Tabla de facturación cruda vaciada.');
    }

    // ══════════════════════════════════════
    //  MOVIMIENTO BANCARIO CRUDO
    // ══════════════════════════════════════

    public function uploadBancario()
    {
        $db = \Config\Database::connect();
        $data['cuentas']        = $this->cuentaBancoModel->orderBy('nombre_cuenta', 'ASC')->findAll();
        $data['totalRegistros'] = $db->table('tbl_movimiento_bancario_crudo')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_movimiento_bancario_crudo')
            ->selectMax('created_at')->get()->getRow()->created_at;
        $data['porCuenta'] = $db->table('tbl_movimiento_bancario_crudo mc')
            ->select('c.nombre_cuenta, COUNT(*) as total')
            ->join('tbl_cuentas_banco c', 'c.id_cuenta_banco = mc.id_cuenta_banco')
            ->groupBy('c.nombre_cuenta')
            ->get()->getResultArray();
        return view('conciliaciones/upload_bancario_crudo', $data);
    }

    public function uploadBancarioPost()
    {
        $file = $this->request->getFile('archivo_csv');
        $idCuentaBanco = (int) $this->request->getPost('id_cuenta_banco');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('errors', ['Debe seleccionar un archivo válido.']);
        }
        if (! $idCuentaBanco) {
            return redirect()->back()->with('errors', ['Debe seleccionar una cuenta bancaria.']);
        }

        $ext = strtolower($file->getExtension());
        if ($ext !== 'csv') {
            return redirect()->back()->with('errors', ['Solo se permiten archivos .csv']);
        }

        $handle = fopen($file->getTempName(), 'r');
        if (! $handle) {
            return redirect()->back()->with('errors', ['No se pudo abrir el archivo.']);
        }

        $header = fgetcsv($handle, 0, ',');
        if (! $header || count($header) < 10) {
            fclose($handle);
            return redirect()->back()->with('errors', ['El archivo debe tener al menos 10 columnas. Se encontraron ' . count($header ?: []) . '.']);
        }

        $insertados = 0;
        $errores    = [];
        $lote       = [];
        $fila       = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $fila++;

            if (count($row) < 10) {
                $errores[] = "Fila {$fila}: menos de 10 columnas, se omite.";
                continue;
            }

            $fecha = $this->parseDateCSV($row[0]);
            if (! $fecha) {
                $errores[] = "Fila {$fila}: fecha inválida '{$row[0]}', se omite.";
                continue;
            }

            $registro = [
                'id_cuenta_banco'    => $idCuentaBanco,
                'fecha_sistema'      => $fecha,
                'documento'          => trim($row[1]) ?: null,
                'descripcion_motivo' => trim($row[2]) ?: null,
                'transaccion'        => trim($row[3]) ?: null,
                'oficina_recaudo'    => trim($row[4]) ?: null,
                'id_origen_destino'  => trim($row[5]) ?: null,
                'valor_cheque'       => $this->cleanDecimal($row[6]),
                'valor_total'        => $this->cleanDecimal($row[7]),
                'referencia_1'       => trim($row[8]) ?: null,
                'referencia_2'       => trim($row[9]) ?: null,
            ];

            $lote[] = $registro;

            if (count($lote) >= 200) {
                $this->movCrudoModel->insertBatch($lote);
                $insertados += count($lote);
                $lote = [];
            }
        }

        fclose($handle);

        if (! empty($lote)) {
            $this->movCrudoModel->insertBatch($lote);
            $insertados += count($lote);
        }

        $msg = "Se importaron {$insertados} movimientos bancarios.";
        if (! empty($errores)) {
            $msg .= ' | ' . count($errores) . ' filas con errores.';
        }

        return redirect()->to('/conciliaciones/cruda/bancario')
            ->with('success', $msg)
            ->with('import_errors', $errores);
    }

    public function listBancarioCrudo()
    {
        $db = \Config\Database::connect();
        $data['registros'] = $db->table('tbl_movimiento_bancario_crudo mc')
            ->select('mc.*, c.nombre_cuenta')
            ->join('tbl_cuentas_banco c', 'c.id_cuenta_banco = mc.id_cuenta_banco', 'left')
            ->orderBy('mc.fecha_sistema', 'DESC')
            ->get()->getResultArray();
        $data['cuentas'] = $this->cuentaBancoModel->findAll();
        return view('conciliaciones/list_bancario_crudo', $data);
    }

    public function truncarBancario($idCuenta)
    {
        $db = \Config\Database::connect();
        $cuenta = $this->cuentaBancoModel->find($idCuenta);
        $nombre = $cuenta ? $cuenta['nombre_cuenta'] : $idCuenta;
        $db->table('tbl_movimiento_bancario_crudo')->where('id_cuenta_banco', $idCuenta)->delete();
        return redirect()->to('/conciliaciones/cruda/bancario')
            ->with('success', "Movimientos crudos de {$nombre} eliminados.");
    }

    // ══════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════

    private function parseDateCSV($val): ?string
    {
        $str = trim((string) $val);
        if (empty($str)) return null;
        // DD/MM/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // MM/DD/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        // YYYY-MM-DD
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        // DD-MM-YYYY
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return null;
    }

    private function cleanInt($val): ?int
    {
        $str = trim((string) $val);
        $str = str_replace([',', '.', ' ', '$'], '', $str);
        return is_numeric($str) ? (int) $str : null;
    }

    private function cleanDecimal($val): ?float
    {
        $str = trim((string) $val);
        if ($str === '') return null;
        // Quitar $ y espacios
        $str = preg_replace('/[\$\s]/', '', $str);
        // Formato colombiano $ 1.234,56
        if (preg_match('/^\-?[\d.]+,\d{1,2}$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }
        // Formato US 1,234.56 o 1,234
        elseif (strpos($str, ',') !== false && strpos($str, '.') === false) {
            $str = str_replace(',', '', $str);
        }
        return is_numeric($str) ? round((float) $str, 2) : null;
    }
}
