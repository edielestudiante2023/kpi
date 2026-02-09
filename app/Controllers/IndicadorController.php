<?php

namespace App\Controllers;

use App\Models\IndicadorModel;
use App\Models\PartesFormulaModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class IndicadorController extends BaseController
{
    protected $indicadorModel;

    /**
     * Evalúa una expresión aritmética de forma segura (sin eval).
     * Soporta +, -, *, /, paréntesis y números decimales.
     */
    private function safeEval(string $expr): float
    {
        $expr = preg_replace('/\s+/', '', $expr);
        $pos = 0;
        $len = strlen($expr);

        $parseExpr = null;
        $parseTerm = null;
        $parseFactor = null;

        $parseFactor = function () use ($expr, &$pos, $len, &$parseExpr, &$parseFactor): float {
            if ($pos < $len && $expr[$pos] === '-') {
                $pos++;
                return -$parseFactor();
            }
            if ($pos < $len && $expr[$pos] === '(') {
                $pos++;
                $result = $parseExpr();
                if ($pos < $len && $expr[$pos] === ')') $pos++;
                return $result;
            }
            $start = $pos;
            while ($pos < $len && (ctype_digit($expr[$pos]) || $expr[$pos] === '.')) {
                $pos++;
            }
            if ($start === $pos) return 0.0;
            return (float) substr($expr, $start, $pos - $start);
        };

        $parseTerm = function () use ($expr, &$pos, $len, &$parseFactor): float {
            $result = $parseFactor();
            while ($pos < $len) {
                if ($expr[$pos] === '*') { $pos++; $result *= $parseFactor(); }
                elseif ($expr[$pos] === '/') {
                    $pos++;
                    $divisor = $parseFactor();
                    $result = ($divisor != 0) ? $result / $divisor : INF;
                }
                else break;
            }
            return $result;
        };

        $parseExpr = function () use ($expr, &$pos, $len, &$parseTerm): float {
            $result = $parseTerm();
            while ($pos < $len) {
                if ($expr[$pos] === '+') { $pos++; $result += $parseTerm(); }
                elseif ($expr[$pos] === '-') { $pos++; $result -= $parseTerm(); }
                else break;
            }
            return $result;
        };

        $result = $parseExpr();
        return round($result, 4);
    }

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->indicadorModel = new IndicadorModel();
    }

    public function listIndicador()
    {
        $indicadores = $this->indicadorModel->orderBy('created_at', 'DESC')->findAll();

        // Cargar todas las fórmulas de una sola vez (evita N+1)
        $formulaModel = new PartesFormulaModel();
        $indicadorIds = array_column($indicadores, 'id_indicador');
        $formulasTexto = $formulaModel->getFormulasComoTextoPorIndicadores($indicadorIds);

        foreach ($indicadores as &$i) {
            $i['formula_renderizada'] = $formulasTexto[$i['id_indicador']] ?? '';
        }

        return view('management/list_indicador', ['indicadores' => $indicadores]);
    }

    public function addIndicador()
    {
        return view('management/add_indicador');
    }

    public function addIndicadorPost()
    {
        $rules = [
            'nombre'             => 'required',
            'periodicidad'       => 'required|in_list[Mensual,Bimensual,Trimestral,Semestral,Anual]',
            'ponderacion'        => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
            'meta_valor'         => 'required|numeric',
            'meta_descripcion'   => 'required',
            'tipo_meta'          => 'required|in_list[mayor_igual,menor_igual,igual,comparativa]',
            'metodo_calculo'     => 'required|in_list[formula,manual,semiautomatico]',
            'unidad'             => 'required',
            'objetivo_proceso'   => 'required',
            'objetivo_calidad'   => 'required',
            'tipo_aplicacion'    => 'required|in_list[cargo,area]'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $data = $this->request->getPost();
        $this->indicadorModel->insert($data);
        $newId = $this->indicadorModel->getInsertID();

        // Si presionó “guardar y diseñar”, lo enviamos al constructor de partes
        if ($this->request->getPost('accion') === 'guardar_disenar') {
            return redirect()->to('/partesformula/add?id_indicador=' . $newId)
                ->with('success', 'Indicador creado. Ahora construye la fórmula.');
        }

        return redirect()->to('/indicadores')->with('success', 'Indicador creado.');
    }

    public function editIndicador($id)
    {
        $indicador = $this->indicadorModel->find($id);
        if (! $indicador) {
            throw new PageNotFoundException("Indicador con ID $id no existe");
        }

        return view('management/edit_indicador', ['indicador' => $indicador]);
    }

    public function editIndicadorPost($id)
    {
        $rules = [
            'nombre'             => 'required',
            'periodicidad'       => 'required|in_list[Mensual,Bimensual,Trimestral,Semestral,Anual]',
            'ponderacion'        => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
            'meta_valor'         => 'required|numeric',
            'meta_descripcion'   => 'required',
            'tipo_meta'          => 'required|in_list[mayor_igual,menor_igual,igual,comparativa]',
            'metodo_calculo'     => 'required|in_list[formula,manual,semiautomatico]',
            'unidad'             => 'required',
            'objetivo_proceso'   => 'required',
            'objetivo_calidad'   => 'required',
            'tipo_aplicacion'    => 'required|in_list[cargo,area]'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        // Actualiza el indicador
        $data = $this->request->getPost();
        $this->indicadorModel->update($id, $data);

        // Si presionó “Guardar y Diseñar Fórmula”, redirige al constructor de partes
        if ($this->request->getPost('accion') === 'guardar_disenar') {
            return redirect()->to('/partesformula/add?id_indicador=' . $id)
                ->with('success', 'Indicador actualizado. Ahora edita la fórmula.');
        }

        // Caso contrario, vuelve al listado
        return redirect()->to('/indicadores')
            ->with('success', 'Indicador actualizado correctamente.');
    }


    public function deleteIndicador($id)
    {
        $this->indicadorModel->delete($id);
        return redirect()->to('/indicadores')->with('success', 'Indicador eliminado.');
    }

    public function fillIndicador($id)
    {
        $indicador = $this->indicadorModel->find($id);
        if (! $indicador) throw new PageNotFoundException();

        $partesModel = new PartesFormulaModel();
        $partes      = $partesModel->getPartesPorIndicador($id);

        return view('management/fill_indicador', [
            'indicador' => $indicador,
            'partes'    => $partes
        ]);
    }

    public function fillIndicadorPost($id)
    {
        $partesModel = new PartesFormulaModel();
        $partes      = $partesModel->getPartesPorIndicador($id);
        $inputs      = $this->request->getPost('dato');

        $formula = '';
        foreach ($partes as $p) {
            if ($p['tipo_parte'] === 'dato') {
                $val = isset($inputs[$p['valor']]) ? floatval($inputs[$p['valor']]) : 0;
                $formula .= " {$val}";
            } else {
                $formula .= " {$p['valor']}";
            }
        }

        $resultado = $this->safeEval($formula);

        return view('management/fill_result', [
            'indicador' => $this->indicadorModel->find($id),
            'formula'   => $formula,
            'resultado' => $resultado,
        ]);
    }

    public function diligenciarFormulaTrabajador($id)
{
    $indicador = $this->indicadorModel->find($id);
    if (! $indicador) throw new PageNotFoundException();

    $partesModel = new PartesFormulaModel();
    $partes      = $partesModel->getPartesPorIndicador($id);

    return view('trabajador/fill_formula_trabajador', [
        'indicador' => $indicador,
        'partes'    => $partes,
    ]);
}

public function evaluarFormulaTrabajadorPost($id)
{
    $indicador = $this->indicadorModel->find($id);
    if (! $indicador) throw new PageNotFoundException();

    $partesModel = new PartesFormulaModel();
    $partes      = $partesModel->getPartesPorIndicador($id);
    $inputs      = $this->request->getPost('dato');

    // Log de entrada
    log_message('debug', '📥 INPUTS DEL TRABAJADOR: ' . json_encode($inputs));
    log_message('debug', '📐 PARTES DE LA FÓRMULA: ' . json_encode($partes));

    $formula = '';
    $valoresLimpios = [];

    foreach ($partes as $index => $p) {
        if ($p['tipo_parte'] === 'dato') {
            $clave = $p['valor'];
            $val   = isset($inputs[$clave]) ? floatval($inputs[$clave]) : 0;
            $valoresLimpios[$clave] = $val;

            // Log detallado por parte
            log_message('debug', "🔢 Parte #{$index}: DATO clave '{$clave}' = {$val}");

            $formula .= $val;
        } else {
            log_message('debug', "🧮 Parte #{$index}: OPERADOR '{$p['valor']}'");

            $formula .= $p['valor'];
        }
    }

    // Log fórmula final ensamblada
    log_message('debug', "🧪 Fórmula construida: {$formula}");

    try {
        $resultado = $this->safeEval($formula);
        log_message('debug', "✅ Resultado calculado: {$resultado}");
    } catch (\Throwable $e) {
        log_message('error', "❌ Error al evaluar fórmula (Trabajador): {$e->getMessage()}");
        return redirect()->back()
            ->with('error', 'Error al calcular la fórmula. Verifica los valores ingresados.');
    }

    return view('trabajador/confirmar_formula_trabajador', [
        'indicador'       => $indicador,
        'formula'         => $formula,
        'resultado'       => $resultado,
        'formula_partes'  => $valoresLimpios,
    ]);
}

public function diligenciarFormulaJefe($id)
{
    $indicador = $this->indicadorModel->find($id);
    if (! $indicador) throw new PageNotFoundException();

    $partesModel = new PartesFormulaModel();
    $partes      = $partesModel->getPartesPorIndicador($id);

    return view('jefatura/fill_formula_jefe', [
        'indicador' => $indicador,
        'partes'    => $partes,
    ]);
}

public function evaluarFormulaJefePost($id)
{
    $indicador   = $this->indicadorModel->find($id);
    if (! $indicador) throw new PageNotFoundException();

    $partesModel = new PartesFormulaModel();
    $partes      = $partesModel->getPartesPorIndicador($id);
    $inputs      = $this->request->getPost('dato');

    // Log: datos ingresados por el usuario
    log_message('debug', '📥 INPUTS DEL JEFE: ' . json_encode($inputs));
    log_message('debug', '📐 PARTES DE LA FÓRMULA: ' . json_encode($partes));

    $formula = '';
    $valoresLimpios = [];

    foreach ($partes as $index => $p) {
        if ($p['tipo_parte'] === 'dato') {
            $clave = $p['valor'];
            $val   = isset($inputs[$clave]) ? floatval($inputs[$clave]) : 0;
            $valoresLimpios[$clave] = $val;

            // Log por cada parte tipo dato
            log_message('debug', "🔢 Parte #{$index}: DATO clave '{$clave}' = {$val}");

            $formula .= $val;
        } else {
            // Log por cada parte operador/paréntesis
            log_message('debug', "🧮 Parte #{$index}: OPERADOR '{$p['valor']}'");

            $formula .= $p['valor'];
        }
    }

    // Log final de la fórmula ensamblada
    log_message('debug', "🧪 Fórmula construida: {$formula}");

    try {
        $resultado = $this->safeEval($formula);

        log_message('debug', "✅ Resultado calculado: {$resultado}");
    } catch (\Throwable $e) {
        log_message('error', "❌ Error al evaluar fórmula: {$e->getMessage()}");
        return redirect()->back()
            ->with('error', 'Error al calcular la fórmula. Verifica los valores ingresados.');
    }

    return view('jefatura/confirmar_formula_jefe', [
        'indicador'       => $indicador,
        'formula'         => $formula,
        'resultado'       => $resultado,
        'formula_partes'  => $valoresLimpios,
    ]);
}


}
