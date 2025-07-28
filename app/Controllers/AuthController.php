<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\I18n\Time;

use SendGrid\Mail\Mail;



require __DIR__ . '/../../vendor/autoload.php';

class AuthController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Mostrar formulario de login
    public function index()
    {
        return view('login');
    }

    // Procesar login y redirección
    public function login()
    {
        $session = session();
        $correo  = $this->request->getVar('correo');
        $password = $this->request->getVar('password');

        $usuario = $this->userModel->where('correo', $correo)->first();
        if (!$usuario || $usuario['activo'] == 0 || !password_verify($password, $usuario['password'])) {
            $msg = !$usuario ? 'Correo no registrado.' : ($usuario['activo'] == 0 ? 'Cuenta inactiva.' : 'Contraseña incorrecta.');
            return redirect()->to('/login')->with('error', $msg);
        }

        // Si es primer login, lo enviamos a cambiar clave
        if (password_verify($password, $usuario['password'])) {
            if ($usuario['primer_login'] == 1) {
                session()->set(['id_users' => $usuario['id_users']]);
                return redirect()->to('cambiarclave');
            }

            // ... resto de la carga de sesión y redirección por rol
        }


        // Crear sesión normal
        $sessionData = [
            'id_users'        => $usuario['id_users'],
            'nombre_completo' => $usuario['nombre_completo'],
            'correo'          => $usuario['correo'],
            'id_roles'        => $usuario['id_roles'],
            'id_perfil_cargo' => $usuario['id_perfil_cargo'],
            'logged_in'       => true
        ];
        $session->set($sessionData);

        // Redirigir según rol
        switch ($usuario['id_roles']) {
            case 1:
                return redirect()->to('superadmin/superadmindashboard');
            case 2:
                return redirect()->to('admin/admindashboard');
            case 3:
                return redirect()->to('jefatura/jefaturadashboard');
            case 4:
                return redirect()->to('trabajador/trabajadordashboard');
            default:
                return redirect()->to('/');
        }
    }

    // Logout
    public function logout()
    {
        session()->destroy();
        return redirect()->to('login')->with('success', 'Has cerrado sesión correctamente.');
    }

    // 1. Mostrar formulario primer login
    public function formPrimerLogin()
    {
        if (! session()->get('id_users')) {
            return redirect()->to('login');
        }
        return view('auth/form_primer_login');
    }

    // Procesar cambio de contraseña en primer login
    public function procesarPrimerLogin()
    {
        $userId    = session()->get('id_users');
        $clave     = $this->request->getPost('password');
        $confirm   = $this->request->getPost('password_confirm');

        if ($clave !== $confirm) {
            return redirect()->back()->with('error', 'Las contraseñas no coinciden.');
        }

        $this->userModel->update($userId, [
            'password'     => password_hash($clave, PASSWORD_DEFAULT),
            'primer_login' => 0
        ]);

        session()->setFlashdata('success', 'Contraseña actualizada. Por favor ingresa de nuevo.');
        return redirect()->to('login');
    }

    // Mostrar formulario de solicitar recuperación
    public function formRecuperar()
    {
        return view('auth/form_recuperar');
    }

    public function procesarRecuperar()
    {
        helper(['url', 'form', 'session']);

        $correo = $this->request->getPost('correo');
        if (empty($correo)) {
            return redirect()->back()->with('error', 'Por favor ingresa tu correo electrónico.');
        }

        $userModel = new \App\Models\UserModel();
        $usuario = $userModel->where('correo', $correo)->first();

        if (!$usuario) {
            return redirect()->back()->with('error', 'No se encontró una cuenta con ese correo.');
        }

        // Generar token único
        $token = bin2hex(random_bytes(32));

        // Guardar token en DB y/o en cache/temporal
        $userModel->update($usuario['id_users'], [
            'reset_token' => $token,
            'reset_token_expira' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);

        // Enviar correo
        if ($this->enviarCorreoRecuperacion($usuario, $token)) {
            return redirect()->to('/login')->with('success', 'Te hemos enviado un enlace de recuperación a tu correo.');
        } else {
            return redirect()->back()->with('error', 'Hubo un error al enviar el correo. Intenta más tarde.');
        }
    }

    private function enviarCorreoRecuperacion($usuario, $token)
    {
        helper('url');
        $link = base_url('resetear/' . $token);

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom("notificacion.cycloidtalent@cycloidtalent.com", "Afilogro");
        $email->setSubject("Recuperación de contraseña – Afilogro");
        $email->addTo($usuario['correo'], $usuario['nombre_completo']);

        $contenidoHTML = "
        <p>Hola <strong>{$usuario['nombre_completo']}</strong>,</p>
        <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
        <p>Haz clic en el botón a continuación para crear una nueva contraseña:</p>
        <p style='text-align: center; margin: 30px 0;'>
            <a href='{$link}' target='_blank' style='padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 6px;'>
                Restablecer Contraseña
            </a>
        </p>
        <p>Este enlace estará activo durante 1 hora. Si no solicitaste este cambio, puedes ignorar este correo.</p>
        <br>
        <p style='color: #6c757d; font-size: 0.9em;'>Equipo Afilogro – Cycloid Talent SAS</p>
    ";

        $email->addContent("text/html", $contenidoHTML);

        try {
            $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
            $response = $sendgrid->send($email);
            return $response->statusCode() >= 200 && $response->statusCode() < 300;
        } catch (\Exception $e) {
            log_message('error', 'Error al enviar correo de recuperación: ' . $e->getMessage());
            return false;
        }
    }





    // Mostrar formulario para resetear via token
    public function formResetear($token = null)
    {
        if (!$token) {
            return redirect()->to('/login')->with('error', 'Token de recuperación no válido.');
        }

        $userModel = new \App\Models\UserModel();
        $usuario = $userModel->where('reset_token', $token)->first();

        if (!$usuario) {
            return redirect()->to('/login')->with('error', 'Token inválido o ya usado.');
        }

        // Verifica si el token expiró
        $fechaExpira = Time::parse($usuario['reset_token_expira']);
        if ($fechaExpira->isBefore(Time::now())) {
            return redirect()->to('/login')->with('error', 'El enlace de recuperación ha expirado.');
        }

        return view('auth/resetear', ['token' => $token]);

    }


    // Procesar el reseteo de contraseña
    public function procesarResetear()
    {
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $password_confirm = $this->request->getPost('password_confirm');

        if (!$token || !$password || !$password_confirm) {
            return redirect()->back()->with('error', 'Todos los campos son obligatorios.');
        }

        // Validar que las contraseñas coincidan
        if ($password !== $password_confirm) {
            return redirect()->back()->with('error', 'Las contraseñas no coinciden.');
        }

        $userModel = new \App\Models\UserModel();
        $usuario = $userModel->where('reset_token', $token)->first();

        if (!$usuario) {
            return redirect()->to('/login')->with('error', 'Token inválido.');
        }

        // Validar expiración
        $fechaExpira = Time::parse($usuario['reset_token_expira']);
        if ($fechaExpira->isBefore(Time::now())) {
            return redirect()->to('/login')->with('error', 'El enlace ha expirado.');
        }

        // Actualiza la contraseña y limpia el token
        $userModel->update($usuario['id_users'], [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expira' => null,
            'primer_login' => 0 // opcional: marca como ya reestablecido
        ]);

        return redirect()->to('/login')->with('success', 'Tu contraseña fue actualizada con éxito.');
    }
}
