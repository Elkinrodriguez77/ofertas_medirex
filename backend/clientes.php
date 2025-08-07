<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = __DIR__ . '/../Recursos/Listado_clientes.xlsx';
$clientes = [];

if (file_exists($archivo)) {
    try {
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();
        $rows = $hoja->toArray(null, true, true, true);
        // Suponiendo que la primera fila son los encabezados
        foreach ($rows as $i => $fila) {
            if ($i == 1) continue; // Saltar encabezados
            $nombre = isset($fila['B']) ? trim($fila['B']) : '';
            $nit = isset($fila['C']) ? trim($fila['C']) : '';
            if ($nombre !== '' && $nit !== '') {
                $clientes[] = [
                    'nombre' => $nombre,
                    'nit' => $nit
                ];
            }
        }
        // Ordenar por nombre
        usort($clientes, function($a, $b) {
            return strcasecmp($a['nombre'], $b['nombre']);
        });
        echo json_encode([
            'success' => true,
            'clientes' => $clientes,
            'total' => count($clientes),
            'message' => 'Clientes cargados desde Excel exitosamente'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'clientes' => [],
            'total' => 0,
            'message' => 'Error al leer el archivo Excel: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false,
        'clientes' => [],
        'total' => 0,
        'message' => 'Archivo de clientes no encontrado.'
    ]);
    exit;
}
?> 