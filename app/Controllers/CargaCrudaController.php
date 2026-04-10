<?php

namespace App\Controllers;

use App\Models\FacturacionCrudaModel;
use App\Models\FacturacionModel;
use App\Models\PortafolioModel;
use App\Models\MovimientoBancarioCrudoModel;
use App\Models\CuentaBancoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CargaCrudaController extends BaseController
{
    protected $factCrudaModel;
    protected $facturacionModel;
    protected $portafolioModel;
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
        $this->facturacionModel = new FacturacionModel();
        $this->portafolioModel  = new PortafolioModel();
        $this->movCrudoModel    = new MovimientoBancarioCrudoModel();
        $this->cuentaBancoModel = new CuentaBancoModel();
    }

    // ══════════════════════════════════════
    //  FACTURACIÓN CRUDA
    // ══════════════════════════════════════

    public function uploadFacturacion()
    {
        $db = \Config\Database::connect();
        $data['totalRegistros'] = $db->table('tbl_facturacion')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_facturacion')
            ->selectMax('created_at')->get()->getRow()->created_at;
        return view('conciliaciones/upload_facturacion_cruda', $data);
    }

    /**
     * Paso 1: Parsear CSV y mostrar vista intermedia para asignar portafolios
     */
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

        $contenido = file_get_contents($file->getTempName());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $tmpPath = $file->getTempName();
        file_put_contents($tmpPath, $contenido);

        $handle = fopen($tmpPath, 'r');
        if (! $handle) {
            return redirect()->back()->with('errors', ['No se pudo abrir el archivo.']);
        }

        // Detectar separador
        $primeraLinea = fgets($handle);
        rewind($handle);
        $comas      = substr_count($primeraLinea, ',');
        $puntoyComa = substr_count($primeraLinea, ';');
        $tabs       = substr_count($primeraLinea, "\t");
        $separador  = ',';
        if ($puntoyComa > $comas && $puntoyComa > $tabs) $separador = ';';
        elseif ($tabs > $comas && $tabs > $puntoyComa) $separador = "\t";

        $header = fgetcsv($handle, 0, $separador);
        if (! $header || count($header) < 13) {
            fclose($handle);
            return redirect()->back()->with('errors', ['El archivo debe tener al menos 13 columnas. Se encontraron ' . count($header ?: []) . '. Separador detectado: "' . ($separador === "\t" ? 'TAB' : $separador) . '"']);
        }

        // Cargar comprobantes existentes
        $db = \Config\Database::connect();
        $existentes = array_flip(array_column(
            $db->table('tbl_facturacion')->select('comprobante')->get()->getResultArray(),
            'comprobante'
        ));

        $nuevas     = [];
        $duplicados = 0;
        $errores    = [];
        $fila       = 1;

        while (($row = fgetcsv($handle, 0, $separador)) !== false) {
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

            if (isset($existentes[$comprobante])) {
                $duplicados++;
                continue;
            }

            $fechaElab = $this->parseDateCSV($row[1]);
            if (! $fechaElab) {
                $errores[] = "Fila {$fila}: fecha inválida '{$row[1]}', se omite.";
                continue;
            }

            $nuevas[] = [
                'comprobante'       => $comprobante,
                'fecha_elaboracion' => $fechaElab,
                'identificacion'    => $this->cleanInt($row[2]),
                'sucursal'          => trim($row[3]) ?: null,
                'nombre_tercero'    => trim($row[4]),
                'base_gravada'      => $this->cleanDecimal($row[5]) ?? 0,
                'base_exenta'       => $this->cleanDecimal($row[6]) ?? 0,
                'iva'               => $this->cleanDecimal($row[7]) ?? 0,
                'cargo_en_totales'  => $this->cleanDecimal($row[10]) ?? 0,
                'descuento_en_totales' => $this->cleanDecimal($row[11]) ?? 0,
                'total'             => $this->cleanDecimal($row[12]) ?? 0,
            ];

            $existentes[$comprobante] = true; // evitar duplicados dentro del mismo CSV
        }

        fclose($handle);

        if (empty($nuevas) && $duplicados === 0) {
            return redirect()->back()->with('errors', ['El archivo no contenía registros válidos.']);
        }

        if (empty($nuevas) && $duplicados > 0) {
            return redirect()->back()->with('errors', ["Las {$duplicados} facturas del archivo ya existen en el sistema."]);
        }

        // Guardar en sesión para paso 2
        session()->set('csv_facturas', $nuevas);

        $data['facturas']    = $nuevas;
        $data['duplicados']  = $duplicados;
        $data['errores']     = $errores;
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();

        return view('conciliaciones/revisar_facturacion_csv', $data);
    }

    /**
     * Paso 2: Confirmar e insertar en tbl_facturacion con portafolios asignados
     */
    public function confirmarFacturacionPost()
    {
        $facturas = session()->get('csv_facturas');
        if (empty($facturas)) {
            return redirect()->to('/conciliaciones/cruda/facturacion')
                ->with('errors', ['No hay facturas para procesar. Suba el CSV nuevamente.']);
        }

        $portafoliosPorFila = $this->request->getPost('portafolio') ?? [];
        $portafolioGlobal   = (int) $this->request->getPost('portafolio_global');

        // Cargar portafolios para nombre detallado
        $portafoliosDB = [];
        foreach ($this->portafolioModel->findAll() as $p) {
            $portafoliosDB[$p['id_portafolio']] = $p['portafolio'];
        }

        $insertados = 0;
        $sinPortafolio = 0;
        $errores    = [];
        $lote       = [];

        foreach ($facturas as $i => $f) {
            $idPort = (int)($portafoliosPorFila[$i] ?? $portafolioGlobal);
            if (! $idPort) {
                $sinPortafolio++;
                $errores[] = "{$f['comprobante']}: sin portafolio asignado, se omite.";
                continue;
            }

            $baseGravada = (float) $f['base_gravada'];
            $iva         = (float) $f['iva'];
            $retefuente4 = round($baseGravada * 0.04, 2);
            $fechaElab   = $f['fecha_elaboracion'];
            $anio = (int) date('Y', strtotime($fechaElab));
            $mes  = (int) date('m', strtotime($fechaElab));

            $numFactura = null;
            if (preg_match('/(\d+)$/', $f['comprobante'], $m)) $numFactura = (int) $m[1];
            $extrae = null;
            if (preg_match('/\d+-(\d+)$/', $f['comprobante'], $m)) $extrae = $m[1];

            $portNombre = $portafoliosDB[$idPort] ?? '';

            $lote[] = [
                'id_portafolio'              => $idPort,
                'semana'                     => (int) date('W', strtotime($fechaElab)),
                'fecha_pago'                 => null,
                'mes_pago'                   => null,
                'valor_pagado'               => null,
                'dif_facturado_pagado'       => 0,
                'valor_esperado_recaudo_iva' => $baseGravada + $iva - $retefuente4,
                'retencion_renta_4'          => $retefuente4,
                'base_gravable_neta'         => $baseGravada - $retefuente4,
                'pagado'                     => 0,
                'estado_pago'                => 'pendiente',
                'anio'                       => $anio,
                'mes'                        => $mes,
                'extrae'                     => $extrae,
                'fecha_anticipo'             => null,
                'anticipo'                   => 0,
                'comprobante'                => $f['comprobante'],
                'fecha_elaboracion'          => $fechaElab,
                'identificacion'             => $f['identificacion'],
                'sucursal'                   => $f['sucursal'],
                'nombre_tercero'             => $f['nombre_tercero'],
                'base_gravada'               => $baseGravada,
                'base_exenta'                => (float) $f['base_exenta'],
                'iva'                        => $iva,
                'retefuente_4'               => $retefuente4,
                'recompra'                   => 0,
                'cargo_en_totales'           => (float) $f['cargo_en_totales'],
                'descuento_en_totales'       => (float) $f['descuento_en_totales'],
                'total'                      => (float) $f['total'],
                'vendedor'                   => null,
                'base_comisiones'            => $baseGravada - $retefuente4,
                'numero_factura'             => $numFactura,
                'portafolio_detallado'       => $portNombre ? $portNombre . '-PH' : null,
                'fecha_vence'                => date('Y-m-d', strtotime($fechaElab . ' +30 days')),
            ];
        }

        if (! empty($lote)) {
            try {
                $this->facturacionModel->insertBatch($lote);
                $insertados = count($lote);
            } catch (\Exception $e) {
                $errores[] = "Error BD: " . $e->getMessage();
            }
        }

        session()->remove('csv_facturas');

        if ($insertados === 0) {
            return redirect()->to('/conciliaciones/cruda/facturacion')
                ->with('errors', ['No se pudo importar ningún registro.'])
                ->with('import_errors', $errores);
        }

        $msg = "Se importaron {$insertados} facturas nuevas a facturación.";
        if ($sinPortafolio > 0) $msg .= " | {$sinPortafolio} omitidas (sin portafolio).";
        if (! empty($errores)) $msg .= ' | ' . count($errores) . ' errores.';

        return redirect()->to('/conciliaciones/facturacion')
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
        $data['totalRegistros'] = $db->table('tbl_conciliacion_bancaria')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_conciliacion_bancaria')
            ->selectMax('created_at')->get()->getRow()->created_at;
        $data['porCuenta'] = $db->table('tbl_conciliacion_bancaria cb')
            ->select('c.nombre_cuenta, COUNT(*) as total')
            ->join('tbl_cuentas_banco c', 'c.id_cuenta_banco = cb.id_cuenta_banco')
            ->groupBy('c.nombre_cuenta')
            ->get()->getResultArray();
        return view('conciliaciones/upload_bancario_crudo', $data);
    }

    /**
     * Paso 1 Bancario: Parsear CSV y mostrar vista intermedia
     */
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

        $contenido = file_get_contents($file->getTempName());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $tmpPath = $file->getTempName();
        file_put_contents($tmpPath, $contenido);

        $handle = fopen($tmpPath, 'r');
        if (! $handle) {
            return redirect()->back()->with('errors', ['No se pudo abrir el archivo.']);
        }

        // Detectar separador
        $primeraLinea = fgets($handle);
        rewind($handle);
        $comas      = substr_count($primeraLinea, ',');
        $puntoyComa = substr_count($primeraLinea, ';');
        $tabs       = substr_count($primeraLinea, "\t");
        $separador  = ',';
        if ($puntoyComa > $comas && $puntoyComa > $tabs) $separador = ';';
        elseif ($tabs > $comas && $tabs > $puntoyComa) $separador = "\t";

        $header = fgetcsv($handle, 0, $separador);
        if (! $header || count($header) < 10) {
            fclose($handle);
            return redirect()->back()->with('errors', ['El archivo debe tener al menos 10 columnas. Se encontraron ' . count($header ?: []) . '. Separador detectado: "' . ($separador === "\t" ? 'TAB' : $separador) . '"']);
        }

        $movimientos = [];
        $errores     = [];
        $fila        = 1;

        while (($row = fgetcsv($handle, 0, $separador)) !== false) {
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

            $transaccion = trim($row[3]) ?: null;
            $valorTotal  = $this->cleanDecimal($row[7]) ?? 0;
            // Cálculo: si es Nota Débito, valor negativo
            $valor = (stripos($transaccion ?? '', 'bito') !== false || stripos($transaccion ?? '', 'Nota D') !== false)
                ? abs($valorTotal) * -1
                : abs($valorTotal);

            $movimientos[] = [
                'fecha_sistema'      => $fecha,
                'documento'          => trim($row[1]) ?: null,
                'descripcion_motivo' => trim($row[2]) ?: null,
                'transaccion'        => $transaccion,
                'oficina_recaudo'    => trim($row[4]) ?: null,
                'nit_originador'     => $this->cleanInt($row[5]),
                'valor_cheque'       => $this->cleanDecimal($row[6]) ?? 0,
                'valor_total'        => $valorTotal,
                'valor'              => $valor,
                'deb_cred'           => $valor >= 0 ? 'INGRESO' : 'EGRESO',
                'referencia_1'       => trim($row[8]) ?: null,
                'referencia_2'       => trim($row[9]) ?: null,
            ];
        }

        fclose($handle);

        if (empty($movimientos)) {
            return redirect()->back()->with('errors', ['El archivo no contenía registros válidos.'])
                ->with('import_errors', $errores);
        }

        // Guardar en sesión
        session()->set('csv_bancario', $movimientos);
        session()->set('csv_bancario_cuenta', $idCuentaBanco);

        // Obtener llave_items existentes para sugerencias
        $db = \Config\Database::connect();
        $llaveItems = array_column(
            $db->table('tbl_conciliacion_bancaria')->select('llave_item')->distinct()->orderBy('llave_item')->get()->getResultArray(),
            'llave_item'
        );

        $data['movimientos']  = $movimientos;
        $data['errores']      = $errores;
        $data['idCuentaBanco'] = $idCuentaBanco;
        $data['cuentaNombre'] = $this->cuentaBancoModel->find($idCuentaBanco)['nombre_cuenta'] ?? '';
        $data['centros']      = $db->table('tbl_centros_costo')->orderBy('centro_costo')->get()->getResultArray();
        $data['llaveItems']   = $llaveItems;

        return view('conciliaciones/revisar_bancario_csv', $data);
    }

    /**
     * Paso 2 Bancario: Confirmar e insertar en tbl_conciliacion_bancaria
     */
    public function confirmarBancarioPost()
    {
        $movimientos   = session()->get('csv_bancario');
        $idCuentaBanco = session()->get('csv_bancario_cuenta');

        if (empty($movimientos) || !$idCuentaBanco) {
            return redirect()->to('/conciliaciones/cruda/bancario')
                ->with('errors', ['No hay movimientos para procesar. Suba el CSV nuevamente.']);
        }

        $centrosPorFila  = $this->request->getPost('centro') ?? [];
        $centroGlobal    = (int) $this->request->getPost('centro_global');
        $llavesPorFila   = $this->request->getPost('llave_item') ?? [];
        $fvPorFila       = $this->request->getPost('fv') ?? [];
        $clientePorFila  = $this->request->getPost('item_cliente') ?? [];

        $db = \Config\Database::connect();
        $concModel = new \App\Models\ConciliacionBancariaModel();

        $insertados    = 0;
        $sinCentro     = 0;
        $errores       = [];
        $lote          = [];

        foreach ($movimientos as $i => $m) {
            $idCentro = (int)($centrosPorFila[$i] ?? $centroGlobal);
            if (! $idCentro) {
                $sinCentro++;
                $errores[] = "Mov. #{$i}: sin centro de costo, se omite.";
                continue;
            }

            $llaveItem   = trim($llavesPorFila[$i] ?? '');
            $fv          = trim($fvPorFila[$i] ?? '') ?: null;
            $itemCliente = trim($clientePorFila[$i] ?? '') ?: $llaveItem;
            $fecha       = $m['fecha_sistema'];

            if (empty($llaveItem)) {
                $llaveItem = 'SIN CLASIFICAR';
                $itemCliente = $itemCliente ?: 'SIN CLASIFICAR';
            }

            $lote[] = [
                'id_cuenta_banco'    => $idCuentaBanco,
                'id_centro_costo'    => $idCentro,
                'llave_item'         => $llaveItem,
                'deb_cred'           => $m['deb_cred'],
                'fv'                 => $fv,
                'item_cliente'       => $itemCliente,
                'anio'               => (int) date('Y', strtotime($fecha)),
                'mes'                => (int) date('m', strtotime($fecha)),
                'mes_real'           => (int) date('m', strtotime($fecha)),
                'semana'             => (int) date('W', strtotime($fecha)),
                'valor'              => (float) $m['valor'],
                'fecha_sistema'      => $fecha,
                'documento'          => $m['documento'],
                'descripcion_motivo' => $m['descripcion_motivo'],
                'transaccion'        => $m['transaccion'],
                'oficina_recaudo'    => $m['oficina_recaudo'],
                'nit_originador'     => $m['nit_originador'],
                'valor_cheque'       => (float) $m['valor_cheque'],
                'valor_total'        => (float) $m['valor_total'],
                'referencia_1'       => $m['referencia_1'],
                'referencia_2'       => $m['referencia_2'],
            ];
        }

        if (! empty($lote)) {
            try {
                $concModel->insertBatch($lote);
                $insertados = count($lote);
            } catch (\Exception $e) {
                $errores[] = "Error BD: " . $e->getMessage();
            }
        }

        session()->remove('csv_bancario');
        session()->remove('csv_bancario_cuenta');

        if ($insertados === 0) {
            return redirect()->to('/conciliaciones/cruda/bancario')
                ->with('errors', ['No se pudo importar ningún movimiento.'])
                ->with('import_errors', $errores);
        }

        $msg = "Se importaron {$insertados} movimientos bancarios.";
        if ($sinCentro > 0) $msg .= " | {$sinCentro} omitidos (sin centro de costo).";
        if (! empty($errores)) $msg .= ' | ' . count($errores) . ' errores.';

        return redirect()->to('/conciliaciones/bancaria')
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
