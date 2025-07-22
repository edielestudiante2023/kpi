<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function testConexion()
{
    $db = \Config\Database::connect();
    if ($db->connID) {
        echo "✅ Conexión exitosa a la base de datos.";
    } else {
        echo "❌ No se pudo conectar.";
    }
}

}
