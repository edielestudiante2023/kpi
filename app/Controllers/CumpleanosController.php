<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\NotificadorCumpleanos;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class CumpleanosController extends BaseController
{
    protected $userModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        helper(['url', 'form']);
        $this->userModel = new UserModel();
    }

    /**
     * Panel de cumpleaños: lista ordenada por proximidad, con estado y acciones.
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $hoy = date('Y-m-d');
        $hoyTs = strtotime($hoy);

        $usuarios = $db->table('users')
            ->select('id_users, nombre_completo, correo, fecha_nacimiento, cumple_silenciado_hasta')
            ->where('activo', 1)
            ->where('fecha_nacimiento IS NOT NULL')
            ->get()->getResultArray();

        $lista = [];
        foreach ($usuarios as $u) {
            $proximo = $this->proximoCumple($u['fecha_nacimiento'], $hoyTs);
            if ($proximo === null) continue;
            $diasFaltan = (int) floor(($proximo - $hoyTs) / 86400);
            $silenciado = !empty($u['cumple_silenciado_hasta']) && $u['cumple_silenciado_hasta'] >= $hoy;
            $edad = (int) date('Y', $proximo) - (int) date('Y', strtotime($u['fecha_nacimiento']));

            $lista[] = [
                'id_users'        => $u['id_users'],
                'nombre'          => $u['nombre_completo'],
                'correo'          => $u['correo'],
                'fecha_nacimiento'=> $u['fecha_nacimiento'],
                'proximo'         => date('Y-m-d', $proximo),
                'dias_faltan'     => $diasFaltan,
                'edad'            => $edad,
                'silenciado'      => $silenciado,
                'silenciado_hasta'=> $u['cumple_silenciado_hasta'],
                'en_ventana'      => $diasFaltan <= 30,
            ];
        }

        // Ordenar por días que faltan (más cercano primero)
        usort($lista, fn($a, $b) => $a['dias_faltan'] <=> $b['dias_faltan']);

        return view('cumpleanos/index', ['cumpleanos' => $lista]);
    }

    /**
     * Silenciar desde el panel (POST, requiere auth).
     */
    public function silenciarPanel($id)
    {
        $u = $this->userModel->find($id);
        if (!$u || empty($u['fecha_nacimiento'])) {
            return redirect()->to('/cumpleanos')->with('error', 'Usuario no valido.');
        }
        $proximo = $this->proximoCumple($u['fecha_nacimiento'], time());
        $fechaHasta = date('Y-m-d', $proximo);
        $this->userModel->update($id, ['cumple_silenciado_hasta' => $fechaHasta]);

        return redirect()->to('/cumpleanos')
            ->with('success', "Recordatorio de {$u['nombre_completo']} silenciado hasta el {$fechaHasta}. Se reactivara solo el proximo ano.");
    }

    /**
     * Reactivar desde el panel (POST, requiere auth).
     */
    public function reactivar($id)
    {
        $u = $this->userModel->find($id);
        if (!$u) return redirect()->to('/cumpleanos')->with('error', 'Usuario no valido.');
        $this->userModel->update($id, ['cumple_silenciado_hasta' => null]);
        return redirect()->to('/cumpleanos')
            ->with('success', "Recordatorio de {$u['nombre_completo']} reactivado.");
    }

    /**
     * Silenciar desde el email (público, validado por token).
     */
    public function silenciar($userId, $anio, $token)
    {
        $notif = new NotificadorCumpleanos();
        $esperado = $notif->generarTokenSilenciar((int)$userId, (int)$anio);
        if (!hash_equals($esperado, $token)) {
            return view('cumpleanos/silenciar_resultado', [
                'ok' => false,
                'mensaje' => 'Enlace no valido o expirado.',
            ]);
        }

        $u = $this->userModel->find($userId);
        if (!$u || empty($u['fecha_nacimiento'])) {
            return view('cumpleanos/silenciar_resultado', [
                'ok' => false,
                'mensaje' => 'Usuario no encontrado.',
            ]);
        }

        // Silenciar hasta la fecha del cumpleaños del año indicado
        $mes = (int) date('n', strtotime($u['fecha_nacimiento']));
        $dia = (int) date('j', strtotime($u['fecha_nacimiento']));
        if ($mes === 2 && $dia === 29 && !checkdate(2, 29, (int)$anio)) $dia = 28;
        $fechaHasta = sprintf('%04d-%02d-%02d', (int)$anio, $mes, $dia);

        $this->userModel->update($userId, ['cumple_silenciado_hasta' => $fechaHasta]);

        return view('cumpleanos/silenciar_resultado', [
            'ok' => true,
            'mensaje' => "Listo. El recordatorio del cumpleanos de {$u['nombre_completo']} fue silenciado. No se enviaran mas correos de este cumpleanos.",
        ]);
    }

    /**
     * Timestamp del próximo cumpleaños a partir de una fecha base.
     */
    private function proximoCumple(string $fechaNacimiento, int $hoyTs): ?int
    {
        $mes = (int) date('n', strtotime($fechaNacimiento));
        $dia = (int) date('j', strtotime($fechaNacimiento));
        if (!$mes || !$dia) return null;

        $anioHoy = (int) date('Y', $hoyTs);
        $diaAj = $dia;
        if ($mes === 2 && $dia === 29 && !checkdate(2, 29, $anioHoy)) $diaAj = 28;
        $esteAnio = strtotime(sprintf('%04d-%02d-%02d', $anioHoy, $mes, $diaAj));

        if ($esteAnio < $hoyTs) {
            $anioSig = $anioHoy + 1;
            $diaSig = $dia;
            if ($mes === 2 && $dia === 29 && !checkdate(2, 29, $anioSig)) $diaSig = 28;
            return strtotime(sprintf('%04d-%02d-%02d', $anioSig, $mes, $diaSig));
        }
        return $esteAnio;
    }
}
