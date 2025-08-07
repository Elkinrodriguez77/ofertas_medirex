<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'full';
$grupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
$portafolio = isset($_GET['portafolio']) ? trim($_GET['portafolio']) : '';
$nit = isset($_GET['nit']) ? trim($_GET['nit']) : '';

function normalizar_nit($nit) {
    // Quitar puntos, guiones, espacios y ceros a la izquierda
    $nit = preg_replace('/[.\-\s]/', '', $nit);
    $nit = ltrim($nit, '0');
    return (string)$nit;
}

$productos = [];

if (!$grupo || !$portafolio || ($tipo === 'especial' && !$nit)) {
    echo json_encode([
        'success' => false,
        'productos' => [],
        'message' => 'Faltan parámetros de grupo, portafolio o NIT.'
    ]);
    exit;
}

if ($tipo === 'especial') {
    $archivo = __DIR__ . '/../Recursos/Listado_Precios_Especiales.xlsx';
    if (file_exists($archivo)) {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $rows = $hoja->toArray(null, true, true, true);
            $nit_normalizado = normalizar_nit($nit);
            foreach ($rows as $i => $fila) {
                if ($i == 1) continue; // Saltar encabezados
                $grupoFila = isset($fila['I']) ? trim($fila['I']) : '';
                $portafolioFila = isset($fila['G']) ? trim($fila['G']) : '';
                $nitFila = isset($fila['J']) ? normalizar_nit($fila['J']) : '';
                if ($grupoFila === $grupo && $portafolioFila === $portafolio && $nitFila === $nit_normalizado) {
                    $precio = isset($fila['F']) ? floatval($fila['F']) : 0;
                    $productos[] = [
                        'id_articulo' => isset($fila['C']) ? trim($fila['C']) : '',
                        'descripcion' => isset($fila['D']) ? trim($fila['D']) : '',
                        'precio' => number_format($precio, 0, '.', ','),
                        'precio_con_iva' => number_format($precio, 0, '.', ','),
                        'iva' => 0
                    ];
                }
            }
            echo json_encode([
                'success' => true,
                'productos' => $productos,
                'total' => count($productos),
                'message' => 'Productos especiales cargados desde Excel exitosamente.'
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'productos' => [],
                'message' => 'Error al leer el archivo Excel: ' . $e->getMessage()
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'productos' => [],
            'message' => 'Archivo de productos especiales no encontrado.'
        ]);
        exit;
    }
} else {
    // Lógica de precios full (ya implementada)
    $archivo = __DIR__ . '/../Recursos/Listado_Precios_Full.xlsx';
    if (file_exists($archivo)) {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $rows = $hoja->toArray(null, true, true, true);
            foreach ($rows as $i => $fila) {
                if ($i == 1) continue; // Saltar encabezados
                $grupoFila = isset($fila['E']) ? trim($fila['E']) : '';
                $portafolioFila = isset($fila['G']) ? trim($fila['G']) : '';
                if ($grupoFila === $grupo && $portafolioFila === $portafolio) {
                    $precioBase = isset($fila['D']) ? floatval($fila['D']) : 0;
                    $iva = isset($fila['F']) ? floatval($fila['F']) : 0;
                    $precioConIva = $precioBase;
                    if ($iva > 0) {
                        $precioConIva = ($precioBase * $iva) + $precioBase;
                    }
                    $productos[] = [
                        'id_articulo' => isset($fila['A']) ? trim($fila['A']) : '',
                        'descripcion' => isset($fila['B']) ? trim($fila['B']) : '',
                        'precio' => number_format($precioBase, 0, '.', ','),
                        'precio_con_iva' => number_format($precioConIva, 0, '.', ','),
                        'iva' => $iva
                    ];
                }
            }
            echo json_encode([
                'success' => true,
                'productos' => $productos,
                'total' => count($productos),
                'message' => 'Productos cargados desde Excel exitosamente.'
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'productos' => [],
                'message' => 'Error al leer el archivo Excel: ' . $e->getMessage()
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'productos' => [],
            'message' => 'Archivo de productos no encontrado.'
        ]);
        exit;
    }
} 