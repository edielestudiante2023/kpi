<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * Filtro de SOLO LECTURA para el módulo Conciliaciones.
 *
 * Bloquea verbos HTTP de escritura (POST/PUT/DELETE/PATCH) cuando el
 * usuario tiene un rol marcado como solo-lectura.
 *
 * Excepción: rutas del widget de OTTO (asesoria-ia/widget/...) y los
 * endpoints AJAX que el chat necesita, porque generar análisis es una
 * funcionalidad explícitamente autorizada para el rol contador.
 *
 * Roles solo-lectura: 5 (contador externo).
 */
class ReadOnlyConciliaciones implements FilterInterface
{
    /** Roles considerados solo-lectura */
    private const ROLES_SOLO_LECTURA = [5];

    /** Verbos HTTP de escritura a bloquear */
    private const VERBOS_ESCRITURA = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /** Rutas exceptuadas (OTTO necesita POST para conversar) */
    private const EXCEPCIONES_POST = [
        'conciliaciones/asesoria-ia/widget/iniciar',
        'conciliaciones/asesoria-ia/widget/enviar',
        'conciliaciones/asesoria-ia/analizar',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $rolId = (int) (session()->get('id_roles') ?? 0);
        if (! in_array($rolId, self::ROLES_SOLO_LECTURA, true)) {
            return; // rol con permisos normales, sigue normal
        }

        $metodo = strtoupper($request->getMethod());
        if (! in_array($metodo, self::VERBOS_ESCRITURA, true)) {
            return; // GET / HEAD / OPTIONS pasan
        }

        $uri = $request->getUri()->getPath();
        // Quitar barra inicial y prefijo index.php si lo tuviera
        $uri = ltrim($uri, '/');
        if (str_starts_with($uri, 'index.php/')) {
            $uri = substr($uri, strlen('index.php/'));
        }

        // Excepción: OTTO necesita POST para conversar
        foreach (self::EXCEPCIONES_POST as $allowed) {
            if (str_starts_with($uri, $allowed)) {
                return;
            }
        }

        // Detectar AJAX (header X-Requested-With o accept JSON)
        $xrw = $request->getHeaderLine('X-Requested-With');
        $accept = $request->getHeaderLine('Accept');
        $esAjax = strcasecmp($xrw, 'XMLHttpRequest') === 0
               || stripos($accept, 'application/json') !== false;

        if ($esAjax) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'ok' => false,
                    'error' => 'Acceso de solo lectura. No tienes permisos para realizar esta acción.',
                ]);
        }
        return redirect()->back()
            ->with('errors', ['Tu rol es de solo lectura: no puedes modificar registros.']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nada
    }
}
