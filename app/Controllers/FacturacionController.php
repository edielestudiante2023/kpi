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
        $builder = $db->table('tbl_facturacion f')
            ->select('f.*, p.portafolio')
            ->join('tbl_portafolios p', 'p.id_portafolio = f.id_portafolio', 'left')
            ->orderBy('f.anio', 'DESC')
            ->orderBy('f.mes', 'DESC')
            ->orderBy('f.numero_factura', 'DESC');

        $data['registros']   = $builder->get()->getResultArray();
        $data['portafolios'] = $this->portafolioModel->orderBy('portafolio', 'ASC')->findAll();
        return view('conciliaciones/list_facturacion', $data);
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
                $dt = ExcelDate::excelToDateTimeObject((float) $val);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Si es string con formato DD/MM/YYYY
        $str = trim((string) $val);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        // MM-DD-YY o similar
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{2,4})$#', $str, $m)) {
            $y = (int) $m[3];
            if ($y < 100) $y += 2000;
            return sprintf('%04d-%02d-%02d', $y, $m[1], $m[2]);
        }

        return null;
    }
}
