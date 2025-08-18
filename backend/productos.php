<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'full';

// Soporte para multiselección (coma-separado). Mantener compat con parámetros antiguos
$gruposParam = isset($_GET['grupos']) ? trim($_GET['grupos']) : '';
$portafoliosParam = isset($_GET['portafolios']) ? trim($_GET['portafolios']) : '';
$especialidadesParam = isset($_GET['especialidades']) ? trim($_GET['especialidades']) : '';

$grupoLegacy = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
$portafolioLegacy = isset($_GET['portafolio']) ? trim($_GET['portafolio']) : '';
$nit = isset($_GET['nit']) ? trim($_GET['nit']) : '';

// Normalizar a arrays
$selectedGrupos = array_values(array_filter(array_map('trim', explode(',', $gruposParam ?: $grupoLegacy)), fn($v) => $v !== ''));
$selectedPortafolios = array_values(array_filter(array_map('trim', explode(',', $portafoliosParam ?: $portafolioLegacy)), fn($v) => $v !== ''));
$selectedEspecialidades = array_values(array_filter(array_map('trim', explode(',', $especialidadesParam)), fn($v) => $v !== ''));

function normalizar_nit($nit) {
    // Quitar puntos, guiones, espacios y ceros a la izquierda
    $nit = preg_replace('/[.\-\s]/', '', $nit);
    $nit = ltrim($nit, '0');
    return (string)$nit;
}

$productos = [];

if (empty($selectedGrupos) || empty($selectedPortafolios) || ($tipo === 'especial' && !$nit)) {
    echo json_encode([
        'success' => false,
        'productos' => [],
        'message' => 'Faltan parámetros: al menos un grupo, un portafolio o NIT (para precios especiales).'
    ]);
    exit;
}

// Construir conjunto de pares (grupo, portafolio) válidos según el árbol y especialidades
function construirParesValidos($selectedGrupos, $selectedPortafolios, $selectedEspecialidades) {
    $paresValidos = [];
    $archivoCategorias = __DIR__ . '/../Recursos/Listado_Categorias_Y_Otros.xlsx';
    if (file_exists($archivoCategorias)) {
        try {
            $readerCat = IOFactory::createReader('Xlsx');
            $readerCat->setReadDataOnly(true);
            $spreadsheetCat = $readerCat->load($archivoCategorias);
            $hojaCat = $spreadsheetCat->getActiveSheet();
            $rowsCat = $hojaCat->toArray(null, true, true, true);
            foreach ($rowsCat as $i => $filaCat) {
                if ($i == 1) continue; // encabezados
                $grupoFilaCat = isset($filaCat['A']) ? trim($filaCat['A']) : '';
                $portafolioFilaCat = isset($filaCat['B']) ? trim($filaCat['B']) : '';
                $especialidadFilaCat = isset($filaCat['C']) ? trim($filaCat['C']) : '';
                if ($grupoFilaCat === '' || $portafolioFilaCat === '') continue;
                if (!in_array($grupoFilaCat, $selectedGrupos, true)) continue;
                if (!in_array($portafolioFilaCat, $selectedPortafolios, true)) continue;
                if (!empty($selectedEspecialidades) && $especialidadFilaCat !== '' && !in_array($especialidadFilaCat, $selectedEspecialidades, true)) continue;
                $clave = $grupoFilaCat . '||' . $portafolioFilaCat;
                $paresValidos[$clave] = true;
            }
        } catch (Exception $e) {
            // Si falla la lectura, permitir al menos la combinación básica grupo+portafolio
            foreach ($selectedGrupos as $g) {
                foreach ($selectedPortafolios as $p) {
                    $paresValidos[$g . '||' . $p] = true;
                }
            }
        }
    } else {
        // Si no existe el archivo, permitir combinaciones directas
        foreach ($selectedGrupos as $g) {
            foreach ($selectedPortafolios as $p) {
                $paresValidos[$g . '||' . $p] = true;
            }
        }
    }
    return $paresValidos;
}

$paresValidos = construirParesValidos($selectedGrupos, $selectedPortafolios, $selectedEspecialidades);

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
                $clave = $grupoFila . '||' . $portafolioFila;
                if (isset($paresValidos[$clave]) && $nitFila === $nit_normalizado) {
                    $precio = isset($fila['F']) ? floatval($fila['F']) : 0;
                    $productos[] = [
                        'id_articulo' => isset($fila['C']) ? trim($fila['C']) : '',
                        'descripcion' => isset($fila['D']) ? trim($fila['D']) : '',
                        'precio' => number_format($precio, 0, '.', ','),
                        'precio_con_iva' => number_format($precio, 0, '.', ','),
                        'iva' => 0,
                        'grupo' => $grupoFila,
                        'portafolio' => $portafolioFila
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
                $clave = $grupoFila . '||' . $portafolioFila;
                if (isset($paresValidos[$clave])) {
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
                        'iva' => $iva,
                        'grupo' => $grupoFila,
                        'portafolio' => $portafolioFila
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