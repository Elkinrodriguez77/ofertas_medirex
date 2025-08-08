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
    $plantillaPDF = '../Recursos/Plantilla_pdf_2.pdf';
    
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
        if (file_exists('../vendor/autoload.php')) {
            require_once '../vendor/autoload.php';
            
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
    // Usar HTTPS si está disponible, sino HTTP
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname($_SERVER['REQUEST_URI']);
    
    // En Render.com, asegurar que la URL sea correcta
    if (strpos($host, 'render.com') !== false || strpos($host, 'onrender.com') !== false) {
        // Para Render.com, usar la URL completa
        $pdfUrl = $protocol . '://' . $host . $basePath . '/' . $filepath;
    } else {
        // Para otros entornos
        $pdfUrl = $protocol . '://' . $host . $basePath . '/' . $filepath;
    }
    
    // Debug: Log de la URL generada
    error_log("URL del PDF generada: " . $pdfUrl);
    error_log("Protocolo: " . $protocol);
    error_log("Host: " . $host);
    error_log("BasePath: " . $basePath);
    error_log("FilePath: " . $filepath);
    
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
    
    // Configurar codificación UTF-8 para caracteres especiales
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->SetMargins(10, 10, 10);
    
    // Agregar la plantilla
    $pageCount = $pdf->setSourceFile($plantillaPath);
    
    // Procesar cada página de la plantilla
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $template = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($template);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        
        // Configurar fuente con soporte UTF-8
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        // Configurar campos según la página
        $campos = [];
        
        if ($pageNo == 1) {
            // PÁGINA 1: Solo datos del cliente y oferta
            $campos = [
                '{{cliente}}' => ['valor' => $datos['cliente'], 'x' => 40, 'y' => 21],
                '{{nit}}' => ['valor' => $datos['nit'], 'x' => 40, 'y' => 25],
                '{{dirigido_a}}' => ['valor' => $datos['dirigido_a'], 'x' => 40, 'y' => 29],
                '{{contacto_cargo}}' => ['valor' => $datos['contacto_cargo'], 'x' => 40, 'y' => 33],
                '{{ciudad}}' => ['valor' => $datos['ciudad'], 'x' => 40, 'y' => 37],
                '{{fecha_presentacion}}' => ['valor' => $datos['fecha_presentacion'], 'x' => 180, 'y' => 21],
                '{{fecha_vigencia}}' => ['valor' => $datos['fecha_vigencia'], 'x' => 180, 'y' => 26],
                '{{territorio}}' => ['valor' => $datos['territorio'], 'x' => 40, 'y' => 41],
                '{{firma_gerente}}' => ['valor' => $datos['firma_gerente'], 'x' => 8, 'y' => 151],
                '{{cargo}}' => ['valor' => $datos['cargo'], 'x' => 8, 'y' => 155]
            ];
        } elseif ($pageNo == 2) {
            // PÁGINA 2: Solo datos del portafolio y grupo (sin descripción, se calcula dinámicamente)
            $campos = [
                '{{PORTAFOLIO}}' => ['valor' => $datos['portafolio'], 'x' => 8, 'y' => 21, 'fuente' => 'Arial', 'tamaño' => 14, 'estilo' => 'B'],
                '{{grupo_articulo}}' => ['valor' => $datos['grupo_articulo'], 'x' => 8, 'y' => 30, 'fuente' => 'Arial', 'tamaño' => 12, 'estilo' => 'B']
            ];
        }
        
        // Escribir campos en el PDF
        escribirCamposEnPDF($pdf, $campos);
        
        // Si es la página 2, agregar descripción y tabla de productos con paginación
        if ($pageNo == 2 && !empty($datos['productos'])) {
            // Agregar descripción en la primera página
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY(8, 40);
            $pdf->MultiCell(180, 4, utf8_decode($datos['descripcion_grupo_articulo'] ?? ''), 0, 'L');
            
            // Agregar tabla de productos con paginación
            agregarTablaProductosConPaginacion($pdf, $datos['productos'], $datos);
        }
    }
    
    // Guardar el PDF
    $pdf->Output($outputPath, 'F');
}

function escribirCamposEnPDF($pdf, $campos) {
    // Escribir cada campo en su posición
    foreach ($campos as $campo => $info) {
        $pdf->SetXY($info['x'], $info['y']);
        // Convertir caracteres especiales para FPDF
        $texto = utf8_decode($info['valor']);
        
        // Configurar fuente según el campo
        if (isset($info['fuente']) && isset($info['tamaño']) && isset($info['estilo'])) {
            // Campo con configuración personalizada
            $pdf->SetFont($info['fuente'], $info['estilo'], $info['tamaño']);
        } elseif ($campo === '{{descripcion_grupo_articulo}}') {
            // Descripción del grupo - MultiCell con fuente pequeña
            $pdf->SetFont('Arial', '', 9);
            // Limitar el texto si es muy largo (opcional)
            if (strlen($texto) > 200) {
                $texto = substr($texto, 0, 200) . '...';
            }
            $pdf->MultiCell(180, 4, $texto, 0, 'L'); // 180mm de ancho, 4mm de altura por línea
            continue; // Saltar el Cell() de abajo
        } else {
            // Campos normales
            $pdf->SetFont('Arial', '', 10);
        }
        
        // Escribir el texto (excepto para descripción que usa MultiCell)
        if ($campo !== '{{descripcion_grupo_articulo}}') {
            $pdf->Cell(100, 5, $texto, 0, 0, 'L');
        }
    }
}

function agregarTablaProductosConPaginacion($pdf, $productos, $datos) {
    $totalProductos = count($productos);
    $productosRestantes = $productos;
    $pagina = 0;
    
    while (!empty($productosRestantes)) {
        // Si no es la primera página, crear nueva página
        if ($pagina > 0) {
            $pdf->AddPage();
        }
        
        // Calcular posición Y dinámica para la tabla
        $yInicial = 49; // Posición base
        
        if ($pagina == 0) {
            // Solo en la primera página calcular posición dinámica
            $yInicial = calcularPosicionYTabla($pdf, $datos);
        } else {
            // En páginas adicionales, posición fija
            $yInicial = 20;
        }
        
        // Calcular cuántos productos caben en esta página
        $productosDisponibles = calcularProductosDisponibles($pdf, $yInicial);
        $productosEstaPagina = array_slice($productosRestantes, 0, $productosDisponibles);
        
        // Agregar tabla de productos para esta página
        agregarTablaProductos($pdf, $productosEstaPagina, $pagina > 0, $yInicial);
        
        // Remover productos procesados
        $productosRestantes = array_slice($productosRestantes, $productosDisponibles);
        $pagina++;
    }
}

function calcularPosicionYTabla($pdf, $datos) {
    // Posición base después de los encabezados
    $yBase = 49;
    
    // Calcular altura de la descripción
    $descripcion = utf8_decode($datos['descripcion_grupo_articulo'] ?? '');
    if (!empty($descripcion)) {
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(8, 40);
        
        // Obtener la posición Y después de escribir la descripción
        $yAntes = $pdf->GetY();
        $pdf->MultiCell(180, 4, $descripcion, 0, 'L');
        $yDespues = $pdf->GetY();
        
        // Calcular altura de la descripción
        $alturaDescripcion = $yDespues - 40;
        
        // Nueva posición Y = posición base + altura de descripción + 4 espacios
        $yNueva = $yBase + $alturaDescripcion + 4;
        
        return $yNueva;
    }
    
    return $yBase;
}

function calcularProductosDisponibles($pdf, $yInicial) {
    $alturaPagina = 297; // Altura de página A4 en mm
    $margenInferior = 20; // Margen inferior
    $alturaFila = 8; // Altura de cada fila de producto
    $alturaEncabezado = 8; // Altura del encabezado de la tabla
    
    // Espacio disponible = altura de página - posición inicial - margen inferior
    $espacioDisponible = $alturaPagina - $yInicial - $margenInferior;
    
    // Número de filas que caben = espacio disponible / altura de fila
    $filasDisponibles = floor($espacioDisponible / $alturaFila);
    
    // Restar 1 por el encabezado de la tabla
    $productosDisponibles = max(1, $filasDisponibles - 1);
    
    return $productosDisponibles;
}

function agregarTablaProductos($pdf, $productos, $esPaginaAdicional = false, $yInicial = 49) {
    // Posición inicial para la tabla
    $x = 8;
    $y = $yInicial;
    $lineHeight = 8;
    
    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY($x, $y);
    $pdf->Cell(25, $lineHeight, utf8_decode('ID'), 1, 0, 'C');
    $pdf->Cell(75, $lineHeight, utf8_decode('Descripción'), 1, 0, 'C');
    $pdf->Cell(20, $lineHeight, utf8_decode('Cant.'), 1, 0, 'C');
    $pdf->Cell(25, $lineHeight, utf8_decode('Total'), 1, 0, 'C');
    $pdf->Cell(25, $lineHeight, utf8_decode('Total+IVA'), 1, 1, 'C');
    
    $y += $lineHeight;
    
    // Datos de productos
    $pdf->SetFont('Arial', '', 8);
    foreach ($productos as $producto) {
        $pdf->SetXY($x, $y);
        $pdf->Cell(25, $lineHeight, utf8_decode($producto['id_articulo'] ?? 'N/A'), 1, 0, 'C');
        $pdf->Cell(75, $lineHeight, utf8_decode(substr($producto['descripcion'] ?? '', 0, 35)), 1, 0, 'L');
        $pdf->Cell(20, $lineHeight, $producto['cantidad'] ?? '0', 1, 0, 'C');
        
        // Usar precios totales si están disponibles, sino calcular
        $precioTotal = $producto['precio_total'] ?? 0;
        $precioConIvaTotal = $producto['precio_con_iva_total'] ?? 0;
        
        // Debug: Log de los valores recibidos
        error_log("Producto: " . ($producto['id_articulo'] ?? 'N/A'));
        error_log("Precio total recibido: " . $precioTotal);
        error_log("Precio con IVA total recibido: " . $precioConIvaTotal);
        
        // Si no hay precios totales o son muy pequeños, calcularlos
        if (!$precioTotal || $precioTotal < 1000 || !$precioConIvaTotal || $precioConIvaTotal < 1000) {
            $precioUnitario = floatval(str_replace(',', '', $producto['precio'] ?? 0));
            $precioConIvaUnitario = floatval(str_replace(',', '', $producto['precio_con_iva'] ?? 0));
            $cantidad = intval($producto['cantidad'] ?? 1);
            
            $precioTotal = $precioUnitario * $cantidad;
            $precioConIvaTotal = $precioConIvaUnitario * $cantidad;
            
            error_log("Calculando: Unitario=$precioUnitario, Cantidad=$cantidad, Total=$precioTotal");
        }
        
        // Asegurar que los valores sean numéricos
        $precioTotal = floatval($precioTotal);
        $precioConIvaTotal = floatval($precioConIvaTotal);
        
        $pdf->Cell(25, $lineHeight, '$' . number_format($precioTotal), 1, 0, 'R');
        $pdf->Cell(25, $lineHeight, '$' . number_format($precioConIvaTotal), 1, 1, 'R');
        
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
            font-family: Montserrat, sans-serif;
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