<?php

namespace App\Controllers;

use App\Models\ConciliacionBancariaModel;
use App\Models\CentroCostoConciliacionModel;
use App\Models\CuentaBancoModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ConciliacionBancariaController extends BaseController
{
    protected $conciliacionModel;
    protected $centroCostoModel;
    protected $cuentaBancoModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->conciliacionModel = new ConciliacionBancariaModel();
        $this->centroCostoModel  = new CentroCostoConciliacionModel();
        $this->cuentaBancoModel  = new CuentaBancoModel();
    }

    /**
     * Vista de carga
     */
    public function uploadForm()
    {
        $data['cuentas'] = $this->cuentaBancoModel->orderBy('nombre_cuenta', 'ASC')->findAll();

        $db = \Config\Database::connect();
        $data['totalRegistros'] = $db->table('tbl_conciliacion_bancaria')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_conciliacion_bancaria')
            ->selectMax('created_at')->get()->getRow()->created_at;

        // Registros por cuenta
        $data['porCuenta'] = $db->table('tbl_conciliacion_bancaria cb')
            ->select('c.nombre_cuenta, COUNT(*) as total')
            ->join('tbl_cuentas_banco c', 'c.id_cuenta_banco = cb.id_cuenta_banco')
            ->groupBy('c.nombre_cuenta')
            ->get()->getResultArray();

        return view('conciliaciones/upload_conciliacion', $data);
    }

    /**
     * Procesar carga del Excel
     */
    public function uploadPost()
    {
        $file = $this->request->getFile('archivo_excel');
        $idCuentaBanco = (int) $this->request->getPost('id_cuenta_banco');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('errors', ['Debe seleccionar un archivo válido.']);
        }
        if (! $idCuentaBanco) {
            return redirect()->back()->with('errors', ['Debe seleccionar una cuenta bancaria.']);
        }

        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('errors', ['Solo se permiten archivos .xlsx o .xls']);
        }

        // Mapear centros de costo
        $centros = $this->centroCostoModel->findAll();
        $mapCentro = [];
        foreach ($centros as $c) {
            $mapCentro[mb_strtoupper(trim($c['centro_costo']))] = (int) $c['id_centro_costo'];
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return redirect()->back()->with('errors', ['Error al leer el archivo: ' . $e->getMessage()]);
        }

        // Saltar header
        array_shift($rows);

        $insertados = 0;
        $errores    = [];
        $lote       = [];

        foreach ($rows as $numFila => $row) {
            $filaReal = $numFila + 1;

            // Columna C: PORTAFOLIO → centro de costo
            $ccNombre = mb_strtoupper(trim($row['C'] ?? ''));
            // Correcciones conocidas
            if ($ccNombre === 'SS') $ccNombre = 'SST';
            if ($ccNombre === 'ASADO') $ccNombre = 'BIENESTAR';
            // Normalizar tildes
            $ccNombre = str_replace(
                ['CRÉDITO', 'DÉBITO', 'RECONSIGNACIÓN', 'DEVOLUCIÓN'],
                ['CREDITO', 'DEBITO', 'RECONSIGNACION', 'DEVOLUCION'],
                $ccNombre
            );

            $idCentroCosto = $mapCentro[$ccNombre] ?? null;
            if (! $idCentroCosto) {
                $errores[] = "Fila {$filaReal}: Centro de costo '{$ccNombre}' no encontrado en maestro.";
                continue;
            }

            $fechaSistema = $this->toDate($row['L']);
            $mesReal = $this->toInt($row['I']); // Por defecto = mes del movimiento

            $registro = [
                'id_cuenta_banco'    => $idCuentaBanco,
                'id_centro_costo'    => $idCentroCosto,
                'llave_item'         => trim($row['D'] ?? ''),
                'deb_cred'           => mb_strtoupper(trim($row['E'] ?? '')),
                'fv'                 => trim($row['F'] ?? '') ?: null,
                'item_cliente'       => trim($row['G'] ?? '') ?: null,
                'anio'               => $this->toInt($row['H']),
                'mes'                => $this->toInt($row['I']),
                'semana'             => $this->toInt($row['J']),
                'valor'              => $this->toDecimal($row['K']),
                'fecha_sistema'      => $fechaSistema,
                'documento'          => $this->toInt($row['M']),
                'descripcion_motivo' => trim($row['N'] ?? '') ?: null,
                'transaccion'        => trim($row['O'] ?? '') ?: null,
                'oficina_recaudo'    => trim($row['P'] ?? '') ?: null,
                'nit_originador'     => $this->toInt($row['Q']),
                'valor_cheque'       => $this->toDecimal($row['R']),
                'valor_total'        => $this->toDecimal($row['S']),
                'referencia_1'       => trim((string)($row['T'] ?? '')) ?: null,
                'referencia_2'       => trim((string)($row['U'] ?? '')) ?: null,
                'mes_real'           => $mesReal,
            ];

            if (empty($registro['llave_item']) || empty($registro['anio'])) {
                $errores[] = "Fila {$filaReal}: Llave Item o Año vacío, se omite.";
                continue;
            }

            $lote[] = $registro;

            if (count($lote) >= 200) {
                $this->conciliacionModel->insertBatch($lote);
                $insertados += count($lote);
                $lote = [];
            }
        }

        if (! empty($lote)) {
            $this->conciliacionModel->insertBatch($lote);
            $insertados += count($lote);
        }

        $msg = "Se importaron {$insertados} registros correctamente.";
        if (! empty($errores)) {
            $msg .= ' | ' . count($errores) . ' filas con errores.';
        }

        return redirect()->to('/conciliaciones/bancaria/upload')
            ->with('success', $msg)
            ->with('import_errors', $errores);
    }

    /**
     * Listado
     */
    public function listConciliacion()
    {
        $db = \Config\Database::connect();

        $anio      = $this->request->getGet('anio') ?: date('Y');
        $rango     = $this->request->getGet('rango') ?: 'todos';
        $cuenta    = $this->request->getGet('cuenta');
        $centro    = $this->request->getGet('centro');
        $tipo      = $this->request->getGet('tipo');
        $debcred   = $this->request->getGet('debcred');
        $categoria = $this->request->getGet('categoria');
        $llave     = $this->request->getGet('llave');

        // Años disponibles
        $data['anios'] = $db->table('tbl_conciliacion_bancaria')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anioActual']    = $anio;
        $data['rangoActual']   = $rango;
        $data['filtroCuenta']  = $cuenta;
        $data['filtroCentro']    = $centro;
        $data['filtroTipo']      = $tipo;
        $data['filtroDebCred']   = $debcred;
        $data['filtroCategoria'] = $categoria;
        $data['filtroLlave']     = $llave;

        // Calcular rango de fechas
        if ($rango === 'personalizado') {
            $fechas = [
                'desde' => $this->request->getGet('desde') ?: null,
                'hasta' => $this->request->getGet('hasta') ?: null,
            ];
        } else {
            $anioNum = ($anio === 'todos') ? 0 : (int) $anio;
            $fechas = $this->calcularRangoFechas($rango, $anioNum);
        }
        $data['fechaDesde'] = $fechas['desde'];
        $data['fechaHasta'] = $fechas['hasta'];

        // ── CARDS: respetan TODOS los filtros ──
        $cardWhere = function($builder) use ($fechas, $cuenta, $debcred) {
            if ($fechas['desde']) $builder->where('cb.fecha_sistema >=', $fechas['desde']);
            if ($fechas['hasta']) $builder->where('cb.fecha_sistema <=', $fechas['hasta']);
            if ($cuenta) $builder->where('cb.id_cuenta_banco', (int) $cuenta);
            if ($debcred) $builder->where('cb.deb_cred', $debcred);
            return $builder;
        };

        // Centros de costo (respeta cuenta + debcred + fechas)
        $resumenCentros = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cc.id_centro_costo, cc.centro_costo, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->groupBy('cc.id_centro_costo, cc.centro_costo')
            ->orderBy('total_valor', 'DESC');
        $cardWhere($resumenCentros);
        $data['resumenCentros'] = $resumenCentros->get()->getResultArray();

        // Cuentas banco (respeta debcred + fechas, NO cuenta porque es el filtro propio)
        $resumenCuentas = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cu.id_cuenta_banco, cu.nombre_cuenta, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->join('tbl_cuentas_banco cu', 'cu.id_cuenta_banco = cb.id_cuenta_banco', 'left')
            ->groupBy('cu.id_cuenta_banco, cu.nombre_cuenta')
            ->orderBy('cu.nombre_cuenta', 'ASC');
        if ($fechas['desde']) $resumenCuentas->where('cb.fecha_sistema >=', $fechas['desde']);
        if ($fechas['hasta']) $resumenCuentas->where('cb.fecha_sistema <=', $fechas['hasta']);
        if ($debcred) $resumenCuentas->where('cb.deb_cred', $debcred);
        $data['resumenCuentas'] = $resumenCuentas->get()->getResultArray();

        // Deb/Cred (respeta cuenta + centro + fechas, NO debcred porque es el filtro propio)
        $resumenDebCred = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.deb_cred, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->groupBy('cb.deb_cred')
            ->orderBy('cb.deb_cred', 'ASC');
        if ($fechas['desde']) $resumenDebCred->where('cb.fecha_sistema >=', $fechas['desde']);
        if ($fechas['hasta']) $resumenDebCred->where('cb.fecha_sistema <=', $fechas['hasta']);
        if ($cuenta) $resumenDebCred->where('cb.id_cuenta_banco', (int) $cuenta);
        if ($centro) $resumenDebCred->where('cb.id_centro_costo', (int) $centro);
        $data['resumenDebCred'] = $resumenDebCred->get()->getResultArray();

        // Resumen INGRESO / EGRESO para cards
        $resumenDebCred = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.deb_cred, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->groupBy('cb.deb_cred')
            ->orderBy('cb.deb_cred', 'ASC');
        $cardWhere($resumenDebCred);
        $data['resumenDebCred'] = $resumenDebCred->get()->getResultArray();

        // ── CARDS JERÁRQUICOS: Categoría y Llave Item ──
        // Categorías (respeta fechas + cuenta + debcred)
        $resumenCategorias = $db->table('tbl_conciliacion_bancaria cb')
            ->select('ccl.categoria, ccl.tipo, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
            ->join('tbl_clasificacion_costos ccl', 'ccl.llave_item = cb.llave_item', 'left')
            ->where('ccl.categoria IS NOT NULL')
            ->groupBy('ccl.categoria, ccl.tipo')
            ->orderBy('ABS(SUM(cb.valor))', 'DESC');
        $cardWhere($resumenCategorias);
        $data['resumenCategorias'] = $resumenCategorias->get()->getResultArray();

        // Llave Items (solo si hay categoría seleccionada)
        $data['resumenLlaveItems'] = [];
        if ($categoria) {
            $resumenLlaveItems = $db->table('tbl_conciliacion_bancaria cb')
                ->select('cb.llave_item, ccl.categoria, SUM(cb.valor) as total_valor, COUNT(*) as movimientos')
                ->join('tbl_clasificacion_costos ccl', 'ccl.llave_item = cb.llave_item', 'left')
                ->where('ccl.categoria', $categoria)
                ->groupBy('cb.llave_item, ccl.categoria')
                ->orderBy('ABS(SUM(cb.valor))', 'DESC');
            $cardWhere($resumenLlaveItems);
            $data['resumenLlaveItems'] = $resumenLlaveItems->get()->getResultArray();
        }

        // ── TABLA: con todos los filtros aplicados ──
        $builder = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.*, cc.centro_costo, cu.nombre_cuenta')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->join('tbl_cuentas_banco cu', 'cu.id_cuenta_banco = cb.id_cuenta_banco', 'left')
            ->orderBy('cb.fecha_sistema', 'DESC');

        if ($fechas['desde'])  $builder->where('cb.fecha_sistema >=', $fechas['desde']);
        if ($fechas['hasta'])  $builder->where('cb.fecha_sistema <=', $fechas['hasta']);
        if ($cuenta)           $builder->where('cb.id_cuenta_banco', (int) $cuenta);
        if ($centro)           $builder->where('cb.id_centro_costo', (int) $centro);
        if ($debcred)          $builder->where('cb.deb_cred', $debcred);
        if ($tipo || $categoria) {
            $builder->join('tbl_clasificacion_costos ccl', 'ccl.llave_item = cb.llave_item', 'left');
            if ($tipo)      $builder->where('ccl.tipo', $tipo);
            if ($categoria) $builder->where('ccl.categoria', $categoria);
        }
        if ($llave) $builder->where('cb.llave_item', $llave);

        $data['registros'] = $builder->get()->getResultArray();
        $data['cuentas']   = $this->cuentaBancoModel->findAll();

        return view('conciliaciones/list_conciliacion', $data);
    }

    /**
     * Exportar Excel con los mismos filtros
     */
    public function exportarCsv()
    {
        $db = \Config\Database::connect();

        $anio      = $this->request->getGet('anio') ?: date('Y');
        $rango     = $this->request->getGet('rango') ?: 'todos';
        $cuenta    = $this->request->getGet('cuenta');
        $centro    = $this->request->getGet('centro');
        $debcred   = $this->request->getGet('debcred');
        $categoria = $this->request->getGet('categoria');
        $llave     = $this->request->getGet('llave');

        if ($rango === 'personalizado') {
            $fechas = ['desde' => $this->request->getGet('desde') ?: null, 'hasta' => $this->request->getGet('hasta') ?: null];
        } else {
            $anioNum = ($anio === 'todos') ? 0 : (int) $anio;
            $fechas = $this->calcularRangoFechas($rango, $anioNum);
        }

        $builder = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cu.nombre_cuenta, cc.centro_costo, cb.llave_item, cb.deb_cred, cb.fv, cb.item_cliente, cb.anio, cb.mes, cb.mes_real, cb.fecha_sistema, cb.valor, cb.transaccion, cb.descripcion_motivo')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->join('tbl_cuentas_banco cu', 'cu.id_cuenta_banco = cb.id_cuenta_banco', 'left')
            ->orderBy('cb.fecha_sistema', 'DESC');

        if ($fechas['desde']) $builder->where('cb.fecha_sistema >=', $fechas['desde']);
        if ($fechas['hasta']) $builder->where('cb.fecha_sistema <=', $fechas['hasta']);
        if ($cuenta)          $builder->where('cb.id_cuenta_banco', (int) $cuenta);
        if ($centro)          $builder->where('cb.id_centro_costo', (int) $centro);
        if ($debcred)         $builder->where('cb.deb_cred', $debcred);
        if ($categoria || $llave) {
            $builder->join('tbl_clasificacion_costos ccl', 'ccl.llave_item = cb.llave_item', 'left');
            if ($categoria) $builder->where('ccl.categoria', $categoria);
        }
        if ($llave) $builder->where('cb.llave_item', $llave);

        $rows = $builder->get()->getResultArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Conciliacion Bancaria');

        $headers = ['Cuenta','Centro Costo','Llave Item','Deb/Cred','FV','Cliente/Item','Anio','Mes','Mes Real','Fecha Sistema','Valor','Transaccion','Descripcion'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '212529']]];
        $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

        $fila = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                'Banco ' . $r['nombre_cuenta'], $r['centro_costo'], $r['llave_item'], $r['deb_cred'],
                $r['fv'], $r['item_cliente'], (int)$r['anio'], (int)$r['mes'], (int)$r['mes_real'],
                $r['fecha_sistema'] ? date('d/m/Y', strtotime($r['fecha_sistema'])) : '',
                (float)$r['valor'], $r['transaccion'], $r['descripcion_motivo'],
            ], null, "A{$fila}");
            $fila++;
        }

        $sheet->getStyle("K2:K{$fila}")->getNumberFormat()->setFormatCode('"$"#,##0');

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'conciliacion_bancaria_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function calcularRangoFechas(string $rango, int $anio): array
    {
        $desde = null;
        $hasta = null;

        switch ($rango) {
            case 'todos':
                if ($anio !== 0) {
                    $desde = sprintf('%04d-01-01', $anio);
                    $hasta = sprintf('%04d-12-31', $anio);
                }
                break;
            case 'mes_actual':
                $desde = date('Y-m-01');
                $hasta = date('Y-m-t');
                break;
            case 'mes_anterior':
                $desde = date('Y-m-01', strtotime('first day of last month'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'bimestre_anterior':
                $desde = date('Y-m-01', strtotime('-2 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'trimestre_anterior':
                $desde = date('Y-m-01', strtotime('-3 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'cuatrimestre_anterior':
                $desde = date('Y-m-01', strtotime('-4 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'semestre_anterior':
                $desde = date('Y-m-01', strtotime('-6 months'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            default:
                // Meses individuales: "01", "02", ..., "12"
                if (preg_match('/^(\d{2})$/', $rango, $m)) {
                    $mes = (int) $m[1];
                    $desde = sprintf('%04d-%02d-01', $anio, $mes);
                    $hasta = date('Y-m-t', strtotime($desde));
                }
                break;
        }

        return ['desde' => $desde, 'hasta' => $hasta];
    }

    /**
     * Truncar por cuenta
     */
    public function truncar($idCuenta)
    {
        $db = \Config\Database::connect();
        $cuenta = $this->cuentaBancoModel->find($idCuenta);
        $nombre = $cuenta ? $cuenta['nombre_cuenta'] : $idCuenta;
        $db->table('tbl_conciliacion_bancaria')->where('id_cuenta_banco', $idCuenta)->delete();
        return redirect()->to('/conciliaciones/bancaria/upload')
            ->with('success', "Movimientos de cuenta {$nombre} eliminados. Puede reimportar.");
    }

    // ── Helpers ──

    private function toInt($val): ?int
    {
        if ($val === null || $val === '') return null;
        $clean = str_replace([' '], '', (string) $val);
        return is_numeric($clean) ? (int) $clean : null;
    }

    private function toDecimal($val): ?float
    {
        if ($val === null || $val === '') return null;
        $str = (string) $val;
        $str = preg_replace('/[\$\s]/', '', $str);
        // Formato US: "840,000" (coma como miles, sin decimales)
        if (strpos($str, ',') !== false && strpos($str, '.') === false) {
            $str = str_replace(',', '', $str);
        }
        // Formato colombiano: "1.234,56" (punto miles, coma decimal)
        elseif (preg_match('/^\-?[\d.]+,\d{1,2}$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }
        return is_numeric($str) ? round((float) $str, 2) : null;
    }

    private function toDate($val): ?string
    {
        if ($val === null || $val === '') return null;
        if ($val instanceof \DateTimeInterface) return $val->format('Y-m-d');
        if (is_numeric($val) && (float) $val > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $val)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        $str = trim((string) $val);
        // Serial de Excel como texto con coma (ej: "45,643")
        $cleaned = str_replace(',', '', $str);
        if (is_numeric($cleaned) && (float) $cleaned > 1000 && (float) $cleaned < 100000) {
            try { return ExcelDate::excelToDateTimeObject((float) $cleaned)->format('Y-m-d'); }
            catch (\Exception $e) { /* continuar */ }
        }
        // Formato con /: detectar MM/DD/YYYY vs DD/MM/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $y = (int) $m[3];
            if ($b > 12) {
                // b no puede ser mes → formato MM/DD/YYYY: a=mes, b=día
                return sprintf('%04d-%02d-%02d', $y, $a, $b);
            } elseif ($a > 12) {
                // a no puede ser mes → formato DD/MM/YYYY: a=día, b=mes
                return sprintf('%04d-%02d-%02d', $y, $b, $a);
            } else {
                // Ambos <= 12: asumir MM/DD/YYYY (formato US del banco)
                return sprintf('%04d-%02d-%02d', $y, $a, $b);
            }
        }
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{2,4})$#', $str, $m)) {
            $y = (int) $m[3];
            if ($y < 100) $y += 2000;
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($b > 12) {
                return sprintf('%04d-%02d-%02d', $y, $a, $b);
            } elseif ($a > 12) {
                return sprintf('%04d-%02d-%02d', $y, $b, $a);
            } else {
                return sprintf('%04d-%02d-%02d', $y, $a, $b);
            }
        }
        return null;
    }
}
