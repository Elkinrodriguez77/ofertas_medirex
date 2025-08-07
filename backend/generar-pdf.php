<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Recopilar datos del formulario
    $datos = [
        'cliente' => $_POST['cliente'] ?? '',
        'nit' => $_POST['nit'] ?? '',
        'dirigido_a' => $_POST['dirigido_a'] ?? '',
        'contacto_cargo' => $_POST['contacto_cargo'] ?? '',
        'ciudad' => $_POST['ciudad'] ?? '',
        'fecha_presentacion' => $_POST['fecha_presentacion'] ?? '',
        'fecha_vigencia' => $_POST['fecha_vigencia'] ?? '',
        'territorio' => $_POST['territorio'] ?? '',
        'firma_gerente' => $_POST['firma_gerente'] ?? '',
        'cargo' => 'Gerente de Territorio',
        'productos' => json_decode($_POST['productos'] ?? '[]', true)
    ];
    
    // Validar datos requeridos
    $camposRequeridos = ['cliente', 'dirigido_a', 'contacto_cargo', 'ciudad', 'territorio', 'firma_gerente'];
    foreach ($camposRequeridos as $campo) {
        if (empty($datos[$campo])) {
            throw new Exception("Campo requerido faltante: $campo");
        }
    }
    
    if (empty($datos['productos'])) {
        throw new Exception('Debe seleccionar al menos un producto');
    }
    
    // Ruta a la plantilla PDF
    $plantillaPDF = '../Recursos/Plantilla_pdf.pdf';
    
    if (!file_exists($plantillaPDF)) {
        throw new Exception('Plantilla PDF no encontrada en: ' . $plantillaPDF);
    }
    
    // Crear directorio temporal si no existe
    $tempDir = 'temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Generar nombre único para el archivo
    $filename = 'oferta_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.pdf';
    $filepath = $tempDir . '/' . $filename;
    
    // Intentar usar FPDI, si falla usar HTML como fallback
    $pdfGenerado = false;
    
    try {
        // Verificar si FPDI está disponible
        if (file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
            
            if (class_exists('setasign\Fpdi\Fpdi') && class_exists('FPDF')) {
                // 1. Buscar la descripción del grupo y portafolio seleccionados en el Excel de categorías
                function obtenerDescripcionGrupo($grupo, $portafolio) {
                    $archivo = __DIR__ . '/../Recursos/Listado_Categorias_Y_Otros.xlsx';
                    if (!file_exists($archivo)) return '';
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
                    $hoja = $spreadsheet->getActiveSheet();
                    $rows = $hoja->toArray(null, true, true, true);
                    foreach ($rows as $i => $fila) {
                        if ($i == 1) continue; // Saltar encabezados
                        $grupoFila = isset($fila['A']) ? trim($fila['A']) : '';
                        $portafolioFila = isset($fila['B']) ? trim($fila['B']) : '';
                        $descripcion = isset($fila['D']) ? trim($fila['D']) : '';
                        if ($grupoFila === $grupo && $portafolioFila === $portafolio) {
                            return $descripcion;
                        }
                    }
                    return '';
                }
                // Justo antes de llamar a generarPDFConPlantilla:
                $datos['portafolio'] = $_POST['portafolio'] ?? '';
                $datos['grupo_articulo'] = $_POST['grupo_articulo'] ?? '';
                $datos['descripcion_grupo_articulo'] = obtenerDescripcionGrupo($datos['grupo_articulo'], $datos['portafolio']);
                // Generar PDF usando FPDI
                generarPDFConPlantilla($plantillaPDF, $datos, $filepath);
                $pdfGenerado = true;
            }
        }
    } catch (Exception $e) {
        error_log('Error con FPDI: ' . $e->getMessage());
        $pdfGenerado = false;
    }
    
    // Si FPDI falló, generar HTML como fallback
    if (!$pdfGenerado) {
        $html = generarHTMLOferta($datos);
        $filename = 'oferta_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
        $filepath = $tempDir . '/' . $filename;
        file_put_contents($filepath, $html);
    }
    
    // URL del archivo generado
    $pdfUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $filepath;
    
    echo json_encode([
        'success' => true,
        'pdf_url' => $pdfUrl,
        'message' => $pdfGenerado ? 'PDF generado exitosamente usando plantilla' : 'Oferta HTML generada (FPDI no disponible)',
        'datos_procesados' => $datos,
        'tipo_archivo' => $pdfGenerado ? 'pdf' : 'html'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar oferta: ' . $e->getMessage()
    ]);
}

function generarPDFConPlantilla($plantillaPath, $datos, $outputPath) {
    // Crear nuevo PDF
    $pdf = new \setasign\Fpdi\Fpdi();
    
    // Agregar la plantilla
    $pageCount = $pdf->setSourceFile($plantillaPath);
    
    // Procesar cada página de la plantilla
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $template = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($template);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        
        // Configurar fuente
        $pdf->SetFont('Arial', '', 10);
        
        // Mapeo de campos a reemplazar con posiciones aproximadas
        $campos = [
            '{{cliente}}' => ['valor' => $datos['cliente'], 'x' => 50, 'y' => 30],
            '{{nit}}' => ['valor' => $datos['nit'], 'x' => 50, 'y' => 40],
            '{{dirigido_a}}' => ['valor' => $datos['dirigido_a'], 'x' => 50, 'y' => 50],
            '{{contacto_cargo}}' => ['valor' => $datos['contacto_cargo'], 'x' => 50, 'y' => 60],
            '{{ciudad}}' => ['valor' => $datos['ciudad'], 'x' => 50, 'y' => 120],
            '{{fecha_presentacion}}' => ['valor' => $datos['fecha_presentacion'], 'x' => 50, 'y' => 80],
            '{{fecha_vigencia}}' => ['valor' => $datos['fecha_vigencia'], 'x' => 50, 'y' => 90],
            '{{territorio}}' => ['valor' => $datos['territorio'], 'x' => 50, 'y' => 100],
            '{{firma_gerente}}' => ['valor' => $datos['firma_gerente'], 'x' => 50, 'y' => 200],
            '{{cargo}}' => ['valor' => $datos['cargo'], 'x' => 50, 'y' => 210]
        ];
        
        // Si es la página 2, agregar los campos dinámicos de la página 2
        if ($pageNo == 2) {
            $campos['{{PORTAFOLIO}}'] = ['valor' => $datos['portafolio'], 'x' => 60, 'y' => 50];
            $campos['{{grupo_articulo}}'] = ['valor' => $datos['grupo_articulo'], 'x' => 60, 'y' => 70];
            $campos['{{descripcion_grupo_articulo}}'] = ['valor' => $datos['descripcion_grupo_articulo'], 'x' => 60, 'y' => 90];
        }
        
        // Escribir campos en el PDF
        escribirCamposEnPDF($pdf, $campos);
        
        // Si es la página 2 o más, agregar tabla de productos
        if ($pageNo == 2 && !empty($datos['productos'])) {
            agregarTablaProductos($pdf, $datos['productos']);
        }
    }
    
    // Guardar el PDF
    $pdf->Output($outputPath, 'F');
}

function escribirCamposEnPDF($pdf, $campos) {
    // Escribir cada campo en su posición
    foreach ($campos as $campo => $info) {
        $pdf->SetXY($info['x'], $info['y']);
        $pdf->Cell(100, 5, $info['valor'], 0, 0, 'L');
    }
}

function agregarTablaProductos($pdf, $productos) {
    // Posición inicial para la tabla
    $x = 20;
    $y = 120; // Ajusta la posición inicial según tu plantilla
    $lineHeight = 8;
    
    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($x, $y);
    $pdf->Cell(30, $lineHeight, 'ID Artículo', 1, 0, 'C');
    $pdf->Cell(80, $lineHeight, 'Descripción', 1, 0, 'C');
    $pdf->Cell(20, $lineHeight, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(30, $lineHeight, 'Precio', 1, 0, 'C');
    $pdf->Cell(30, $lineHeight, 'Precio con IVA', 1, 1, 'C');
    
    $y += $lineHeight;
    
    // Datos de productos
    $pdf->SetFont('Arial', '', 8);
    foreach ($productos as $producto) {
        if ($y > 250) { // Si se acerca al final de la página, crear nueva página
            $pdf->AddPage();
            $y = 20;
        }
        
        $pdf->SetXY($x, $y);
        $pdf->Cell(30, $lineHeight, $producto['id_articulo'], 1, 0, 'L');
        $pdf->Cell(80, $lineHeight, substr($producto['descripcion'], 0, 40), 1, 0, 'L');
        $pdf->Cell(20, $lineHeight, $producto['cantidad'], 1, 0, 'C');
        $pdf->Cell(30, $lineHeight, '$' . number_format(str_replace(',', '', $producto['precio'] ?? 0)), 1, 0, 'R');
        $pdf->Cell(30, $lineHeight, '$' . number_format(str_replace(',', '', $producto['precio_con_iva'] ?? 0)), 1, 1, 'R');
        
        $y += $lineHeight;
    }
}

function generarHTMLOferta($datos) {
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta Medirex</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #1B365D;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #1B365D;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h2 {
            color: #1B365D;
            border-bottom: 2px solid #6DC067;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #1B365D;
        }
        .info-value {
            color: #333;
        }
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .productos-table th,
        .productos-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .productos-table th {
            background-color: #1B365D;
            color: white;
        }
        .productos-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 2px solid #1B365D;
            padding-top: 20px;
        }
        .firma {
            margin-top: 30px;
            text-align: right;
        }
        .firma-line {
            border-top: 1px solid #333;
            width: 200px;
            margin-top: 50px;
            display: inline-block;
        }
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MEDIREX - SOLUCIONES MÉDICAS INNOVADORAS</h1>
        <p>Generador de Ofertas</p>
        <p>Fecha de Presentación: ' . $datos['fecha_presentacion'] . '</p>
    </div>
    
    <div class="info-section">
        <h2>Información del Cliente</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Cliente:</span>
                <span class="info-value">' . htmlspecialchars($datos['cliente']) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">NIT:</span>
                <span class="info-value">' . htmlspecialchars($datos['nit']) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dirigido a:</span>
                <span class="info-value">' . htmlspecialchars($datos['dirigido_a']) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Cargo:</span>
                <span class="info-value">' . htmlspecialchars($datos['contacto_cargo']) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Ciudad:</span>
                <span class="info-value">' . htmlspecialchars($datos['ciudad']) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">Territorio:</span>
                <span class="info-value">' . htmlspecialchars($datos['territorio']) . '</span>
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>Productos Cotizados</h2>
        <table class="productos-table">
            <thead>
                <tr>
                    <th>ID Artículo</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($datos['productos'] as $producto) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($producto['id_articulo']) . '</td>
                    <td>' . htmlspecialchars($producto['descripcion']) . '</td>
                    <td>' . htmlspecialchars($producto['cantidad']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p><strong>Vigencia de la oferta:</strong> ' . $datos['fecha_vigencia'] . '</p>
        <p><strong>Territorio:</strong> ' . htmlspecialchars($datos['territorio']) . '</p>
    </div>
    
    <div class="firma">
        <div class="firma-line"></div>
        <p><strong>' . htmlspecialchars($datos['firma_gerente']) . '</strong><br>
        ' . $datos['cargo'] . '</p>
    </div>
</body>
</html>';
    
    return $html;
}
?> 