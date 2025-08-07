<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$archivo = __DIR__ . '/../Recursos/Listado_Categorias_Y_Otros.xlsx';
$grupos = [];

if (file_exists($archivo)) {
    try {
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();
        $rows = $hoja->toArray(null, true, true, true);
        // Suponiendo que la primera fila son los encabezados
        foreach ($rows as $i => $fila) {
            if ($i == 1) continue; // Saltar encabezados
            $grupo = isset($fila['A']) ? trim($fila['A']) : '';
            $portafolio = isset($fila['B']) ? trim($fila['B']) : '';
            if ($grupo !== '' && $portafolio !== '') {
                $grupos[] = [
                    'grupo' => $grupo,
                    'portafolio' => $portafolio
                ];
            }
        }
        // Ordenar por grupo y portafolio
        usort($grupos, function($a, $b) {
            $cmp = strcasecmp($a['grupo'], $b['grupo']);
            if ($cmp === 0) {
                return strcasecmp($a['portafolio'], $b['portafolio']);
            }
            return $cmp;
        });
        echo json_encode([
            'success' => true,
            'grupos' => $grupos,
            'total' => count($grupos),
            'message' => 'Grupos y portafolios cargados desde Excel exitosamente'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'grupos' => [],
            'total' => 0,
            'message' => 'Error al leer el archivo Excel: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false,
        'grupos' => [],
        'total' => 0,
        'message' => 'Archivo de grupos no encontrado.'
    ]);
    exit;
}
?> 