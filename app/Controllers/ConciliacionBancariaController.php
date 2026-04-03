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
        $builder = $db->table('tbl_conciliacion_bancaria cb')
            ->select('cb.*, cc.centro_costo, cu.nombre_cuenta')
            ->join('tbl_centros_costo cc', 'cc.id_centro_costo = cb.id_centro_costo', 'left')
            ->join('tbl_cuentas_banco cu', 'cu.id_cuenta_banco = cb.id_cuenta_banco', 'left')
            ->orderBy('cb.fecha_sistema', 'DESC');

        $data['registros'] = $builder->get()->getResultArray();
        $data['cuentas']   = $this->cuentaBancoModel->findAll();
        return view('conciliaciones/list_conciliacion', $data);
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
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^(\d{1,2})-(\d{1,2})-(\d{2,4})$#', $str, $m)) {
            $y = (int) $m[3];
            if ($y < 100) $y += 2000;
            return sprintf('%04d-%02d-%02d', $y, $m[1], $m[2]);
        }
        return null;
    }
}
