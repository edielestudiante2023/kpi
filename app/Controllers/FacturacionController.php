<?php

namespace App\Controllers;

use App\Models\FacturacionModel;
use App\Models\PortafolioModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FacturacionController extends BaseController
{
    protected $facturacionModel;
    protected $portafolioModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->facturacionModel = new FacturacionModel();
        $this->portafolioModel  = new PortafolioModel();
    }

    /**
     * Vista de carga de Excel
     */
    public function uploadForm()
    {
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();

        // Estadísticas rápidas
        $db = \Config\Database::connect();
        $data['totalRegistros'] = $db->table('tbl_facturacion')->countAllResults();
        $data['ultimaCarga']    = $db->table('tbl_facturacion')
            ->selectMax('created_at')
            ->get()->getRow()->created_at;

        return view('conciliaciones/upload_facturacion', $data);
    }

    /**
     * Procesar la carga del Excel
     */
    public function uploadPost()
    {
        $file = $this->request->getFile('archivo_excel');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('errors', ['Debe seleccionar un archivo válido.']);
        }

        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('errors', ['Solo se permiten archivos .xlsx o .xls']);
        }

        // Cargar portafolios para mapear nombre → id
        $portafolios = $this->portafolioModel->findAll();
        $mapPortafolio = [];
        foreach ($portafolios as $p) {
            $mapPortafolio[mb_strtoupper(trim($p['portafolio']))] = (int) $p['id_portafolio'];
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return redirect()->back()->with('errors', ['Error al leer el archivo: ' . $e->getMessage()]);
        }

        // Saltar la primera fila (headers)
        $header = array_shift($rows);

        $insertados = 0;
        $errores    = [];
        $lote       = [];

        foreach ($rows as $numFila => $row) {
            $filaReal = $numFila + 1;

            // Columna A: PORTAFOLIO → buscar FK
            $portNombre = mb_strtoupper(trim($row['A'] ?? ''));
            // Corregir typos conocidos
            if ($portNombre === 'ST') {
                $portNombre = 'SST';
            }
            $idPortafolio = $mapPortafolio[$portNombre] ?? null;
            if (! $idPortafolio) {
                $errores[] = "Fila {$filaReal}: Portafolio '{$portNombre}' no encontrado en maestro.";
                continue;
            }

            $registro = [
                'id_portafolio'              => $idPortafolio,
                'semana'                     => $this->toInt($row['B']),
                'fecha_pago'                 => $this->toDate($row['C']),
                'mes_pago'                   => $this->toInt($row['D']),
                'valor_pagado'               => $this->toDecimal($row['E']),
                'dif_facturado_pagado'       => $this->toDecimal($row['F']),
                'valor_esperado_recaudo_iva' => $this->toDecimal($row['G']),
                'retencion_renta_4'          => $this->toDecimal($row['H']),
                'base_gravable_neta'         => $this->toDecimal($row['I']),
                'pagado'                     => (mb_strtoupper(trim($row['J'] ?? '')) === 'SI') ? 1 : 0,
                'anio'                       => $this->toInt($row['K']),
                'mes'                        => $this->toInt($row['L']),
                'extrae'                     => trim($row['M'] ?? '') ?: null,
                'fecha_anticipo'             => $this->toDate($row['N']),
                'anticipo'                   => $this->toDecimal($row['O']),
                'comprobante'                => trim($row['P'] ?? ''),
                'fecha_elaboracion'          => $this->toDate($row['Q']),
                'identificacion'             => $this->toInt($row['R']),
                'sucursal'                   => trim($row['S'] ?? '') ?: null,
                'nombre_tercero'             => trim($row['T'] ?? ''),
                'base_gravada'               => $this->toDecimal($row['U']),
                'base_exenta'                => $this->toDecimal($row['V']),
                'iva'                        => $this->toDecimal($row['W']),
                'retefuente_4'               => $this->toDecimal($row['X']),
                'recompra'                   => $this->toInt($row['Y']) ? 1 : 0,
                'cargo_en_totales'           => $this->toDecimal($row['Z']),
                'descuento_en_totales'       => $this->toDecimal($row['AA']),
                'total'                      => $this->toDecimal($row['AB']),
                'vendedor'                   => trim($row['AC'] ?? '') ?: null,
                'base_comisiones'            => $this->toDecimal($row['AD']),
                'numero_factura'             => $this->toInt($row['AE']),
                'portafolio_detallado'       => trim($row['AF'] ?? '') ?: null,
                'fecha_vence'                => $this->toDate($row['AG']),
            ];

            // Validación mínima
            if (empty($registro['comprobante']) || empty($registro['anio'])) {
                $errores[] = "Fila {$filaReal}: Comprobante o Año vacío, se omite.";
                continue;
            }

            $lote[] = $registro;

            // Insertar en lotes de 200
            if (count($lote) >= 200) {
                $this->facturacionModel->insertBatch($lote);
                $insertados += count($lote);
                $lote = [];
            }
        }

        // Insertar el resto
        if (! empty($lote)) {
            $this->facturacionModel->insertBatch($lote);
            $insertados += count($lote);
        }

        $msg = "Se importaron {$insertados} registros correctamente.";
        if (! empty($errores)) {
            $msg .= ' | ' . count($errores) . ' filas con errores.';
        }

        return redirect()->to('/conciliaciones/facturacion/upload')
            ->with('success', $msg)
            ->with('import_errors', $errores);
    }

    /**
     * Listado de facturación con filtros
     */
    public function listFacturacion()
    {
        $db = \Config\Database::connect();

        $anio   = $this->request->getGet('anio') ?: date('Y');
        $rango  = $this->request->getGet('rango') ?: 'todos';
        $pagado = $this->request->getGet('pagado');
        $portafolioFiltro = $this->request->getGet('portafolio');
        $vencida = $this->request->getGet('vencida');

        $data['anios'] = $db->table('tbl_facturacion')
            ->select('anio')->distinct()->orderBy('anio', 'DESC')
            ->get()->getResultArray();
        $data['anioActual']       = $anio;
        $data['rangoActual']      = $rango;
        $data['filtroPagado']     = $pagado;
        $data['filtroPortafolio'] = $portafolioFiltro;
        $data['filtroVencida']    = $vencida;

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

        // Función para aplicar filtros base (año + rango)
        $aplicarBase = function($builder, $prefix = 'f') use ($fechas) {
            if ($fechas['desde'])  $builder->where("{$prefix}.fecha_elaboracion >=", $fechas['desde']);
            if ($fechas['hasta'])  $builder->where("{$prefix}.fecha_elaboracion <=", $fechas['hasta']);
            return $builder;
        };

        // ── CARDS: respetan TODOS los filtros ──
        // Portafolios (respeta pagado + fechas)
        $resumenBuilder = $db->table('tbl_facturacion f')
            ->select('p.id_portafolio, p.portafolio, SUM(f.base_gravada) as total_base, COUNT(*) as facturas')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->groupBy('p.id_portafolio, p.portafolio')
            ->orderBy('total_base', 'DESC');
        $aplicarBase($resumenBuilder);
        if ($pagado !== null && $pagado !== '') $resumenBuilder->where('f.pagado', (int) $pagado);
        $data['resumenPortafolios'] = $resumenBuilder->get()->getResultArray();

        // Pagado/Cartera (respeta portafolio + fechas)
        $resumenPagado = $db->table('tbl_facturacion f')
            ->select('f.pagado, SUM(f.base_gravada) as total_base, COUNT(*) as facturas')
            ->groupBy('f.pagado');
        $aplicarBase($resumenPagado);
        if ($portafolioFiltro) $resumenPagado->where('f.id_portafolio', (int) $portafolioFiltro);
        $data['resumenPagado'] = $resumenPagado->get()->getResultArray();

        // Totales de IVA, Retenciones y Líquido para cards (respetan TODOS los filtros)
        $totalesBuilder = $db->table('tbl_facturacion f')
            ->select('SUM(f.base_gravada) as total_base, SUM(f.iva) as total_iva, SUM(ABS(f.retefuente_4)) as total_retencion');
        $aplicarBase($totalesBuilder);
        if ($pagado !== null && $pagado !== '') $totalesBuilder->where('f.pagado', (int) $pagado);
        if ($portafolioFiltro) $totalesBuilder->where('f.id_portafolio', (int) $portafolioFiltro);
        $totales = $totalesBuilder->get()->getRow();
        $data['totalBaseGravada'] = (float) ($totales->total_base ?? 0);
        $data['totalIva']         = (float) ($totales->total_iva ?? 0);
        $data['totalRetencion']   = (float) ($totales->total_retencion ?? 0);
        $data['totalLiquido']     = $data['totalBaseGravada'] + $data['totalIva'] - $data['totalRetencion'];

        // Cartera vencida (no pagadas + fecha_elaboracion + 30 días < hoy)
        $vencidaBuilder = $db->table('tbl_facturacion f')
            ->select('SUM(f.base_gravada) as total_vencida, COUNT(*) as facturas_vencidas')
            ->where('f.pagado', 0)
            ->where('DATE_ADD(f.fecha_elaboracion, INTERVAL 30 DAY) <', date('Y-m-d'));
        $aplicarBase($vencidaBuilder);
        if ($portafolioFiltro) $vencidaBuilder->where('f.id_portafolio', (int) $portafolioFiltro);
        $vencidaRow = $vencidaBuilder->get()->getRow();
        $data['totalCarteraVencida']   = (float) ($vencidaRow->total_vencida ?? 0);
        $data['facturasVencidas']      = (int) ($vencidaRow->facturas_vencidas ?? 0);

        // ── TABLA: con todos los filtros ──
        $builder = $db->table('tbl_facturacion f')
            ->select('f.*, p.portafolio')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->orderBy('f.fecha_elaboracion', 'DESC')
            ->orderBy('f.numero_factura', 'DESC');

        if ($fechas['desde']) $builder->where('f.fecha_elaboracion >=', $fechas['desde']);
        if ($fechas['hasta']) $builder->where('f.fecha_elaboracion <=', $fechas['hasta']);
        if ($pagado !== null && $pagado !== '') $builder->where('f.pagado', (int) $pagado);
        if ($portafolioFiltro) $builder->where('f.id_portafolio', (int) $portafolioFiltro);
        if ($vencida === '1') {
            $builder->where('f.pagado', 0)
                    ->where('DATE_ADD(f.fecha_elaboracion, INTERVAL 30 DAY) <', date('Y-m-d'));
        }

        $data['registros']   = $builder->get()->getResultArray();
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();

        return view('conciliaciones/list_facturacion', $data);
    }

    /**
     * Exportar Excel con los mismos filtros aplicados
     */
    public function exportarCsv()
    {
        $db = \Config\Database::connect();

        $anio   = $this->request->getGet('anio') ?: date('Y');
        $rango  = $this->request->getGet('rango') ?: 'todos';
        $pagado = $this->request->getGet('pagado');
        $portafolioFiltro = $this->request->getGet('portafolio');
        $vencida = $this->request->getGet('vencida');

        if ($rango === 'personalizado') {
            $fechas = ['desde' => $this->request->getGet('desde') ?: null, 'hasta' => $this->request->getGet('hasta') ?: null];
        } else {
            $anioNum = ($anio === 'todos') ? 0 : (int) $anio;
            $fechas = $this->calcularRangoFechas($rango, $anioNum);
        }

        $builder = $db->table('tbl_facturacion f')
            ->select('p.portafolio, f.comprobante, f.fecha_elaboracion, f.identificacion, f.nombre_tercero, f.base_gravada, f.iva, f.retefuente_4, f.pagado, f.fecha_pago, f.valor_pagado, f.vendedor, f.portafolio_detallado')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->orderBy('f.fecha_elaboracion', 'DESC');

        if ($fechas['desde']) $builder->where('f.fecha_elaboracion >=', $fechas['desde']);
        if ($fechas['hasta']) $builder->where('f.fecha_elaboracion <=', $fechas['hasta']);
        if ($pagado !== null && $pagado !== '') $builder->where('f.pagado', (int) $pagado);
        if ($portafolioFiltro) $builder->where('f.id_portafolio', (int) $portafolioFiltro);
        if ($vencida === '1') {
            $builder->where('f.pagado', 0)->where('DATE_ADD(f.fecha_elaboracion, INTERVAL 30 DAY) <', date('Y-m-d'));
        }

        $rows = $builder->get()->getResultArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Facturacion');

        $headers = ['Portafolio','Comprobante','Fecha Elaboracion','NIT','Cliente','Base Gravada','IVA','Retencion 4%','Liquido','Pagado','Fecha Pago','Valor Pagado','Vendedor','Detallado'];
        $sheet->fromArray($headers, null, 'A1');

        // Estilo headers
        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '212529']]];
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);

        $fila = 2;
        foreach ($rows as $r) {
            $liquido = (float)$r['base_gravada'] - abs((float)$r['retefuente_4']);
            $sheet->fromArray([
                $r['portafolio'], $r['comprobante'],
                $r['fecha_elaboracion'] ? date('d/m/Y', strtotime($r['fecha_elaboracion'])) : '',
                $r['identificacion'], $r['nombre_tercero'],
                (float)$r['base_gravada'], (float)$r['iva'], (float)$r['retefuente_4'], $liquido,
                $r['pagado'] ? 'SI' : 'NO',
                $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '',
                $r['valor_pagado'] ? (float)$r['valor_pagado'] : '',
                $r['vendedor'], $r['portafolio_detallado'],
            ], null, "A{$fila}");
            $fila++;
        }

        // Formato moneda
        foreach (['F','G','H','I','L'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$fila}")->getNumberFormat()->setFormatCode('"$"#,##0');
        }

        // Auto-ancho
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'facturacion_' . date('Y-m-d_His') . '.xlsx';
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
     * Truncar tabla para reimportar
     */
    public function truncar()
    {
        $db = \Config\Database::connect();
        $db->table('tbl_facturacion')->truncate();
        return redirect()->to('/conciliaciones/facturacion/upload')
            ->with('success', 'Tabla de facturación vaciada. Puede reimportar.');
    }

    // ── Helpers de conversión ──

    private function toInt($val): ?int
    {
        if ($val === null || $val === '') return null;
        $clean = str_replace([',', '.', ' '], '', (string) $val);
        return is_numeric($clean) ? (int) $clean : null;
    }

    private function toDecimal($val): ?float
    {
        if ($val === null || $val === '') return null;
        // Limpiar formato colombiano "$ 1.234,56" o "1234.56"
        $str = (string) $val;
        $str = preg_replace('/[\\$\\s]/', '', $str);
        // Si tiene formato colombiano (punto como miles, coma como decimal)
        if (preg_match('/^\-?[\d.]+,\d{1,2}$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        }
        return is_numeric($str) ? round((float) $str, 2) : null;
    }

    private function toDate($val): ?string
    {
        if ($val === null || $val === '') return null;

        // Si es un objeto DateTime de PhpSpreadsheet
        if ($val instanceof \DateTimeInterface) {
            return $val->format('Y-m-d');
        }

        // Si es numérico (serial de Excel)
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
                return sprintf('%04d-%02d-%02d', $y, $a, $b);
            } elseif ($a > 12) {
                return sprintf('%04d-%02d-%02d', $y, $b, $a);
            } else {
                return sprintf('%04d-%02d-%02d', $y, $a, $b); // Asumir MM/DD/YYYY
            }
        }
        // Formato con -
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
