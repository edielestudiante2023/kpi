<?php

namespace App\Controllers;

use App\Models\PartesFormulaModel;
use App\Models\IndicadorModel;
use CodeIgniter\Controller;

class PartesFormulaController extends Controller
{
    public function listPartesFormulaModel()
    {
        $model = new PartesFormulaModel();
        $data['partes'] = $model
            ->select('partes_formula_indicador.*, indicadores.nombre AS nombre_indicador')
            ->join('indicadores', 'indicadores.id_indicador = partes_formula_indicador.id_indicador')
            ->orderBy('partes_formula_indicador.id_indicador ASC, orden ASC')
            ->findAll();

        return view('management/list_partes_formula', $data);
    }

    public function addPartesFormulaModel()
    {
        $indicadorModel = new IndicadorModel();
        $data['indicadores'] = $indicadorModel->orderBy('nombre', 'ASC')->findAll();

        // Si viene un ID desde la URL, cargar partes existentes para mostrar vista previa
        $idIndicador = $this->request->getGet('id_indicador');
        if ($idIndicador) {
            $partesModel = new PartesFormulaModel();
            $data['formula_actual'] = $partesModel
                ->where('id_indicador', $idIndicador)
                ->orderBy('orden', 'ASC')
                ->findAll();
            $data['id_indicador_seleccionado'] = $idIndicador;
        } else {
            $data['formula_actual'] = [];
            $data['id_indicador_seleccionado'] = null;
        }

        return view('management/add_partes_formula', $data);
    }

    public function getNextOrden($idIndicador)
{
    $model = new PartesFormulaModel();
    $count = $model
        ->where('id_indicador', $idIndicador)
        ->countAllResults();

    return $this->response->setJSON(['next_orden' => $count + 1]);
}


    public function addPartesFormulaModelPost()
    {
        $model = new PartesFormulaModel();
        $idIndicador = $this->request->getPost('id_indicador');
        $tipoParte = $this->request->getPost('tipo_parte');

        // Validar tipo_parte con normalización de mayúsculas/minúsculas
        $tiposPermitidos = [
            'paréntesis_apertura',
            'paréntesis_cierre', 
            'operador',
            'dato',
            'constante'
        ];

        // Normalizar el tipo_parte a minúsculas para la validación
        $tipoParteNormalizado = strtolower(trim($tipoParte));
        $tiposPermitidosNormalizados = array_map('strtolower', $tiposPermitidos);

        if (!in_array($tipoParteNormalizado, $tiposPermitidosNormalizados)) {
            $mensaje = "❌ ERROR: El tipo de parte '$tipoParte' no es válido.\n\n";
            $mensaje .= "📋 Valores permitidos (exactamente como se muestran):\n";
            $mensaje .= "• paréntesis_apertura\n";
            $mensaje .= "• paréntesis_cierre\n"; 
            $mensaje .= "• operador\n";
            $mensaje .= "• dato\n";
            $mensaje .= "• constante\n\n";
            $mensaje .= "⚠️ Nota: No se permiten mayúsculas, espacios adicionales ni valores como 'variable', 'DATO', 'Dato', etc.";

            return redirect()->to(site_url('partesformula/add?id_indicador=' . $idIndicador))
                           ->with('error', $mensaje);
        }

        // Encontrar el valor correcto (con la capitalización apropiada) del array original
        $indiceEncontrado = array_search($tipoParteNormalizado, $tiposPermitidosNormalizados);
        $tipoParteCorregido = $tiposPermitidos[$indiceEncontrado];

        $model->insert([
            'id_indicador' => $idIndicador,
            'tipo_parte'   => $tipoParteCorregido, // Usar el valor corregido
            'valor'        => $this->request->getPost('valor'),
            'orden'        => $this->request->getPost('orden'),
        ]);

        return redirect()->to(site_url('partesformula/add?id_indicador=' . $idIndicador))
                         ->with('success', 'Parte agregada. Puedes seguir construyendo la fórmula.');
    }

    public function editPartesFormulaModel($id)
    {
        $model = new PartesFormulaModel();
        $indicadorModel = new IndicadorModel();

        $data['parte'] = $model->find($id);
        $data['indicadores'] = $indicadorModel->orderBy('nombre', 'ASC')->findAll();

        return view('management/edit_partes_formula', $data);
    }

    public function editPartesFormulaModelPost($id)
    {
        $model = new PartesFormulaModel();
        $tipoParte = $this->request->getPost('tipo_parte');

        // Validar tipo_parte con normalización de mayúsculas/minúsculas
        $tiposPermitidos = [
            'paréntesis_apertura',
            'paréntesis_cierre', 
            'operador',
            'dato',
            'constante'
        ];

        // Normalizar el tipo_parte a minúsculas para la validación
        $tipoParteNormalizado = strtolower(trim($tipoParte));
        $tiposPermitidosNormalizados = array_map('strtolower', $tiposPermitidos);

        if (!in_array($tipoParteNormalizado, $tiposPermitidosNormalizados)) {
            $mensaje = "❌ ERROR: El tipo de parte '$tipoParte' no es válido.\n\n";
            $mensaje .= "📋 Valores permitidos (exactamente como se muestran):\n";
            $mensaje .= "• paréntesis_apertura\n";
            $mensaje .= "• paréntesis_cierre\n"; 
            $mensaje .= "• operador\n";
            $mensaje .= "• dato\n";
            $mensaje .= "• constante\n\n";
            $mensaje .= "⚠️ Nota: No se permiten mayúsculas, espacios adicionales ni valores como 'variable', 'DATO', 'Dato', etc.";

            return redirect()->to(site_url('partesformula/edit/' . $id))
                           ->with('error', $mensaje);
        }

        // Encontrar el valor correcto (con la capitalización apropiada) del array original
        $indiceEncontrado = array_search($tipoParteNormalizado, $tiposPermitidosNormalizados);
        $tipoParteCorregido = $tiposPermitidos[$indiceEncontrado];

        $model->update($id, [
            'id_indicador' => $this->request->getPost('id_indicador'),
            'tipo_parte'   => $tipoParteCorregido, // Usar el valor corregido
            'valor'        => $this->request->getPost('valor'),
            'orden'        => $this->request->getPost('orden'),
        ]);
        return redirect()->to(site_url('partesformula/list'));
    }

    public function deletePartesFormulaModel($id)
    {
        $model = new PartesFormulaModel();
        $parte = $model->find($id);
        $model->delete($id);

        // Redirigir de nuevo al indicador actual si se estaba construyendo
        return redirect()->to(site_url('/partesformula/list'))
                         ->with('success', 'Parte eliminada correctamente.');
    }

    public function uploadCSVForm()
    {
        return view('management/upload_partes_formula');
    }

    public function uploadCSVPost()
    {
        $file = $this->request->getFile('csv_file');

        if ($file->isValid() && $file->getClientExtension() === 'csv') {
            $path = $file->getTempName();
            $handle = fopen($path, "r");

            $model = new PartesFormulaModel();
            fgetcsv($handle); // skip headers

            // Valores permitidos para tipo_parte
            $tiposPermitidos = [
                'paréntesis_apertura',
                'paréntesis_cierre', 
                'operador',
                'dato',
                'constante'
            ];

            $tiposPermitidosNormalizados = array_map('strtolower', $tiposPermitidos);
            $errores = [];
            $linea = 2; // Empezamos en línea 2 (después del header)
            $registrosInsertados = 0;

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                // Validar que la fila tenga al menos 4 columnas
                if (count($row) < 4) {
                    $errores[] = "⚠️ Línea $linea: Faltan columnas. Se esperan 4 columnas: id_indicador, tipo_parte, valor, orden";
                    $linea++;
                    continue;
                }

                // Normalizar y validar tipo_parte
                $tipoParteOriginal = trim($row[1]);
                $tipoParteNormalizado = strtolower($tipoParteOriginal);

                if (!in_array($tipoParteNormalizado, $tiposPermitidosNormalizados)) {
                    $errores[] = "❌ Línea $linea: tipo_parte '$tipoParteOriginal' no es válido.\n   📋 Valores permitidos: paréntesis_apertura, paréntesis_cierre, operador, dato, constante\n   ⚠️ No se aceptan: 'variable', 'DATO', 'Dato', etc.";
                    $linea++;
                    continue;
                }

                // Encontrar el valor correcto del array original
                $indiceEncontrado = array_search($tipoParteNormalizado, $tiposPermitidosNormalizados);
                $tipoParteCorregido = $tiposPermitidos[$indiceEncontrado];

                $model->insert([
                    'id_indicador' => trim($row[0]),
                    'tipo_parte'   => $tipoParteCorregido, // Usar el valor corregido
                    'valor'        => trim($row[2]),
                    'orden'        => trim($row[3]),
                ]);
                $registrosInsertados++;
                $linea++;
            }

            fclose($handle);

            if (!empty($errores)) {
                $mensajeError = "🚨 ERRORES ENCONTRADOS EN EL CSV:\n\n";
                $mensajeError .= implode("\n\n", $errores);
                $mensajeError .= "\n\n✅ Registros válidos insertados: $registrosInsertados";
                $mensajeError .= "\n❌ Registros con errores: " . count($errores);
                $mensajeError .= "\n\n📝 Corrija los errores y vuelva a subir el archivo.";
                return redirect()->to(site_url('partesformula/list'))->with('error', $mensajeError);
            }

            return redirect()->to(site_url('partesformula/list'))->with('success', "✅ CSV cargado exitosamente: $registrosInsertados registros insertados sin errores.");
        }

        return redirect()->back()->with('error', 'Error al subir el archivo.');
    }
}
