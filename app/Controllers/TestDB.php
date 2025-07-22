<?php

namespace App\Controllers;
use CodeIgniter\Controller;
use Config\Database;

class TestDB extends Controller
{
    public function index()
    {
        try {
            $db = Database::connect();
            $pdo = $db->connect(); // Para forzar el intento de conexión real
            echo "✅ Conexión exitosa.";
        } catch (\Throwable $e) {
            echo "<h2 style='color:red'>❌ No se pudo conectar:</h2>";
            echo "<pre>" . $e->getMessage() . "</pre>";
        }
    }
}
