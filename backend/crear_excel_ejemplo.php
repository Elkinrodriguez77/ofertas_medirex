<?php
// Script temporal para crear archivos Excel de ejemplo
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear directorio si no existe
if (!is_dir('../Recursos')) {
    mkdir('../Recursos', 0777, true);
}

// 1. Crear Listado_clientes.xlsx
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Codigo SN');
$sheet->setCellValue('B1', 'Nombre_cliente');
$sheet->setCellValue('C1', 'Nit');

// Datos de ejemplo
$clientes = [
    ['00964439', 'ESCAPARTE SAS', '900964439-0'],
    ['01526849', 'JK CAOBOS S.A.S', '901526849-2'],
    ['91900475', 'COOPERATIVA DE CAFETALEROS DEL NORTE DEL VALLE', '891900475-1'],
    ['1494587', 'AMAVI S.A.S.', '901494587-9'],
    ['062075091', 'CRUZ QUINA FRANCYNED', '1062075091-9'],
    ['12345678', 'HOSPITAL UNIVERSITARIO SAN VICENTE', '890.123.456-7'],
    ['87654321', 'CLÍNICA MEDELLÍN', '890.234.567-8'],
    ['11223344', 'FUNDACIÓN VALLE DEL LILI', '890.345.678-9']
];

$row = 2;
foreach ($clientes as $cliente) {
    $sheet->setCellValue('A' . $row, $cliente[0]);
    $sheet->setCellValue('B' . $row, $cliente[1]);
    $sheet->setCellValue('C' . $row, $cliente[2]);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$writer->save('../Recursos/Listado_clientes.xlsx');

// 2. Crear Listado_Categorias_Y_Otros.xlsx
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Grupo de articulo');
$sheet->setCellValue('B1', 'Portafolio');

// Datos de ejemplo
$categorias = [
    ['Biomateriales', 'Portafolio A'],
    ['Biomateriales', 'Portafolio B'],
    ['Biomodelos', 'Portafolio X'],
    ['Biomodelos', 'Portafolio Y'],
    ['Brocas autobloqueantes', 'Portafolio 1'],
    ['Brocas autobloqueantes', 'Portafolio 2'],
    ['Codman', 'Portafolio Codman A'],
    ['Codman', 'Portafolio Codman B'],
    ['Duraseal', 'Neurocirugia'],
    ['Fijador de craneo', 'Portafolio Fijador'],
    ['Injertos óseos', 'Portafolio Injertos'],
    ['Motores de alta revolución', 'Portafolio Motores'],
    ['Regeneración Dural', 'Portafolio Regeneración'],
    ['Set para fijación craneal', 'Portafolio Set']
];

$row = 2;
foreach ($categorias as $categoria) {
    $sheet->setCellValue('A' . $row, $categoria[0]);
    $sheet->setCellValue('B' . $row, $categoria[1]);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$writer->save('../Recursos/Listado_Categorias_Y_Otros.xlsx');

// 3. Crear Listado_Precios_Full.xlsx
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Portafolio');
$sheet->setCellValue('B1', 'Número de artículo');
$sheet->setCellValue('C1', 'Descripción');
$sheet->setCellValue('D1', 'Precio');
$sheet->setCellValue('E1', 'Precio con IVA');
$sheet->setCellValue('F1', 'url_imagen');

// Datos de ejemplo
$productos = [
    ['Neurocirugia', 'ART001', 'Producto Biomaterial Premium', '150000', '178500', 'https://via.placeholder.com/150x150?text=Producto+1'],
    ['Neurocirugia', 'ART002', 'Set de Brocas Autobloqueantes', '89000', '105910', 'https://via.placeholder.com/150x150?text=Producto+2'],
    ['Neurocirugia', 'ART003', 'Fijador Craneal Avanzado', '220000', '261800', 'https://via.placeholder.com/150x150?text=Producto+3'],
    ['Portafolio A', 'ART004', 'Biomaterial Especializado', '180000', '214200', 'https://via.placeholder.com/150x150?text=Producto+4'],
    ['Portafolio B', 'ART005', 'Material de Regeneración', '95000', '113050', 'https://via.placeholder.com/150x150?text=Producto+5']
];

$row = 2;
foreach ($productos as $producto) {
    $sheet->setCellValue('A' . $row, $producto[0]);
    $sheet->setCellValue('B' . $row, $producto[1]);
    $sheet->setCellValue('C' . $row, $producto[2]);
    $sheet->setCellValue('D' . $row, $producto[3]);
    $sheet->setCellValue('E' . $row, $producto[4]);
    $sheet->setCellValue('F' . $row, $producto[5]);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$writer->save('../Recursos/Listado_Precios_Full.xlsx');

// 4. Crear Listado_Precios_Especiales.xlsx
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Headers
$sheet->setCellValue('A1', 'Portafolio');
$sheet->setCellValue('B1', 'Número de artículo');
$sheet->setCellValue('C1', 'Descripción');
$sheet->setCellValue('D1', 'Precio');
$sheet->setCellValue('E1', 'Precio con IVA');

// Datos de ejemplo (precios más bajos)
$productosEspeciales = [
    ['Neurocirugia', 'ART001-ESP', 'Producto Biomaterial Premium (Especial)', '120000', '142800'],
    ['Neurocirugia', 'ART002-ESP', 'Set de Brocas Autobloqueantes (Especial)', '71000', '84490'],
    ['Portafolio A', 'ART004-ESP', 'Biomaterial Especializado (Especial)', '144000', '171360']
];

$row = 2;
foreach ($productosEspeciales as $producto) {
    $sheet->setCellValue('A' . $row, $producto[0]);
    $sheet->setCellValue('B' . $row, $producto[1]);
    $sheet->setCellValue('C' . $row, $producto[2]);
    $sheet->setCellValue('D' . $row, $producto[3]);
    $sheet->setCellValue('E' . $row, $producto[4]);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$writer->save('../Recursos/Listado_Precios_Especiales.xlsx');

echo "Archivos Excel creados exitosamente en la carpeta Recursos/";
?> 