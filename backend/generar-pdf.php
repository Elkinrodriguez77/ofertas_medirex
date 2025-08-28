<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Asegurar autoload para PhpSpreadsheet y demás dependencias
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

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
    
    // Plantilla: usar HTML base del sistema con Montserrat como estándar
    // Crear directorio temporal si no existe
    $tempDir = 'temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Generar nombre único para el archivo HTML
    $filename = 'oferta_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
    $filepath = $tempDir . '/' . $filename;
    // Adjuntar filtros al payload de render
    $datos['portafolios'] = $_POST['portafolios'] ?? '';
    $datos['grupos'] = $_POST['grupos'] ?? '';
    $datos['especialidades'] = $_POST['especialidades'] ?? '';
    // Render y escribir el archivo HTML
    $html = generarHTMLOferta($datos);
    file_put_contents($filepath, $html);
    
    // Preparar datos de filtros
    $datos['portafolios'] = $_POST['portafolios'] ?? '';
    $datos['grupos'] = $_POST['grupos'] ?? '';
    $datos['especialidades'] = $_POST['especialidades'] ?? '';

    // Helpers
    $toArray = function($csv) {
        return array_values(array_filter(array_map('trim', explode(',', (string)$csv)), fn($v) => $v !== ''));
    };
    $portSel = $toArray($datos['portafolios']);
    $gruSel = $toArray($datos['grupos']);
    $espSel = $toArray($datos['especialidades']);

    // Descargar logo (Dropbox: forzar descarga directa con dl=1)
    function descargarLogoMedirex($urlOriginal, $destPath) {
        try {
            if (empty($urlOriginal)) return false;
            $url = preg_replace('/[?&]dl=0/', '', $urlOriginal);
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'dl=1';
            $bin = @file_get_contents($url);
            if ($bin === false) return false;
            file_put_contents($destPath, $bin);
            return file_exists($destPath);
        } catch (\Throwable $t) {
            return false;
        }
    }

    // Obtener descripciones por selección
    function obtenerDescripcionesSeleccion($portSel, $gruSel, $espSel) {
        $descripciones = [];
        try {
                    $archivo = __DIR__ . '/../Recursos/Listado_Categorias_Y_Otros.xlsx';
            if (file_exists($archivo)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
                    $hoja = $spreadsheet->getActiveSheet();
                    $rows = $hoja->toArray(null, true, true, true);
                    foreach ($rows as $i => $fila) {
                    if ($i == 1) continue;
                    $g = isset($fila['A']) ? trim($fila['A']) : '';
                    $p = isset($fila['B']) ? trim($fila['B']) : '';
                    $e = isset($fila['C']) ? trim($fila['C']) : '';
                    $d = isset($fila['D']) ? trim($fila['D']) : '';
                    if ($g === '' || $p === '') continue;
                    if (!empty($portSel) && !in_array($p, $portSel, true)) continue;
                    if (!empty($gruSel) && !in_array($g, $gruSel, true)) continue;
                    if (!empty($espSel) && $e !== '' && !in_array($e, $espSel, true)) continue;
                    $descripciones[$p][$g][$e] = $d;
                }
            }
        } catch (\Throwable $ex) {
            // ignorar errores
        }
        return $descripciones;
    }

    // Construir PDF con FPDF
    if (false && class_exists('FPDF')) {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $marginLeft = 10; $contentWidth = 190 - $marginLeft;

        // Logo (omitido si falla)
        try {
            $logoPath = $tempDir . '/logo_medirex.png';
            $logoUrl = $_POST['logo_url'] ?? '';
            if ($logoUrl && !file_exists($logoPath)) {
                descargarLogoMedirex($logoUrl, $logoPath);
            }
            if ($logoUrl && file_exists($logoPath)) {
                // Validar que sea PNG/JPEG
                $info = @getimagesize($logoPath);
                if ($info && in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
                    $pdf->Image($logoPath, 10, 8, 38);
                }
            }
        } catch (\Throwable $t) { /* omitir logo */ }

        // Título
        $pdf->SetFont('Arial','B',14);
        $pdf->SetXY(10, 12);
        $pdf->Cell(0, 10, utf8_decode('Generador de Ofertas - MEDIREX'), 0, 1, 'R');
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0, 6, utf8_decode('Fecha de Presentación: ' . ($datos['fecha_presentacion'] ?? '')), 0, 1, 'R');
        $pdf->Ln(2);

        // Información del Cliente
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0, 8, utf8_decode('Información del Cliente'), 0, 1, 'L');
        $pdf->SetFont('Arial','',10);
        $info = [
            ['Cliente', $datos['cliente'] ?? ''],
            ['NIT', $datos['nit'] ?? ''],
            ['Dirigido a', $datos['dirigido_a'] ?? ''],
            ['Cargo', $datos['contacto_cargo'] ?? ''],
            ['Ciudad', $datos['ciudad'] ?? ''],
            ['Territorio', $datos['territorio'] ?? ''],
            ['Vigencia', $datos['fecha_vigencia'] ?? '']
        ];
        foreach ($info as [$label, $value]) {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(35, 6, utf8_decode($label . ':'), 0, 0, 'L');
            $pdf->SetFont('Arial','',10);
            $pdf->Cell(0, 6, utf8_decode((string)$value), 0, 1, 'L');
        }
        $pdf->Ln(3);

        // Texto corporativo (según imagen provista)
        $pdf->SetFont('Arial','',11);
        $corporativo = "Cordial Saludo,\n\n".
        "Medirex BIC S.A.S. es una empresa innovadora dedicada a la comercialización de dispositivos médicos, con un firme compromiso en mejorar el don de la vida. Nos especializamos en soluciones avanzadas para neurocirugía, ortopedia y otras especialidades médicas. Nuestra misión es proporcionar productos de alta calidad, cumpliendo con los más estrictos estándares de la industria, respaldados por un equipo de profesionales altamente capacitados y dedicados a la excelencia en el servicio.\n\n".
        "Con el firme propósito de apoyar su misión y fortalecer una relación comercial a largo plazo, hemos preparado una propuesta que incluye un descuento preferencial sobre cada referencia de nuestros productos.";
        $pdf->MultiCell(0, 6, utf8_decode($corporativo));
        $pdf->Ln(2);

        // Tabla de descuentos
        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(27,54,93); // azul
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(40, 8, utf8_decode('DESCUENTO'), 1, 0, 'C', true);
        $pdf->Cell(40, 8, utf8_decode('PLAZO'), 1, 1, 'C', true);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(40, 8, '5%', 1, 0, 'C');
        $pdf->Cell(40, 8, utf8_decode('30 días'), 1, 1, 'C');
        $pdf->Cell(40, 8, '3%', 1, 0, 'C');
        $pdf->Cell(40, 8, utf8_decode('60 días'), 1, 1, 'C');
        $pdf->Ln(3);

        $pdf->SetFont('Arial','',11);
        $parrafo2 = "Confiamos en que nuestros productos no solo continuarán mejorando la vida de los pacientes, sino que también superarán las expectativas de calidad de nuestros clientes. Nuestro compromiso incluye:";
        $pdf->MultiCell(0, 6, utf8_decode($parrafo2));
        $pdf->Ln(1);
        $bullets = [
            'Asesoría Comercial Personalizada',
            'Acompañamiento Quirúrgico Integral para productos de especial preparación',
            'Capacitaciones Continuas'
        ];
        foreach ($bullets as $b) {
            $pdf->Cell(5, 6, chr(149), 0, 0, 'L');
            $pdf->Cell(0, 6, utf8_decode($b), 0, 1, 'L');
        }
        $pdf->Ln(1);
        $cierre = "Esperamos que esta propuesta sea satisfactoria para su institución y quedamos atentos a sus comentarios para avanzar hacia una negociación efectiva y beneficiosa.";
        $pdf->MultiCell(0, 6, utf8_decode($cierre));
        $pdf->Ln(4);

        // Secciones por selección (sin el título 'Selección')
        $descripciones = obtenerDescripcionesSeleccion($portSel, $gruSel, $espSel);
        foreach ($descripciones as $portafolio => $grupos) {
            $pdf->SetFont('Arial','B',14); // H1
            $pdf->Cell(0, 8, utf8_decode($portafolio), 0, 1, 'L');
            foreach ($grupos as $grupo => $espMap) {
                $pdf->SetFont('Arial','B',12); // H2
                $pdf->Cell(0, 7, utf8_decode($grupo), 0, 1, 'L');
                foreach ($espMap as $esp => $desc) {
                    if ($esp !== '') {
                        $pdf->SetFont('Arial','B',11); // H3
                        $pdf->Cell(0, 6, utf8_decode($esp), 0, 1, 'L');
                    }
                    if (!empty($desc)) {
                        $pdf->SetFont('Arial','',10);
                        $pdf->MultiCell(0, 5.5, utf8_decode($desc));
                    }
                    $pdf->Ln(1);
                }
                $pdf->Ln(1);
            }
            $pdf->Ln(2);
        }

        // Tabla de productos
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0, 8, utf8_decode('Productos Cotizados'), 0, 1, 'L');
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(27,54,93);
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(25, 7, utf8_decode('ID'), 1, 0, 'C', true);
        $pdf->Cell(80, 7, utf8_decode('Descripción'), 1, 0, 'C', true);
        $pdf->Cell(20, 7, utf8_decode('Cant.'), 1, 0, 'C', true);
        $pdf->Cell(25, 7, utf8_decode('Precio'), 1, 0, 'C', true);
        $pdf->Cell(15, 7, utf8_decode('IVA'), 1, 0, 'C', true);
        $pdf->Cell(25, 7, utf8_decode('Precio+IVA'), 1, 1, 'C', true);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8.5);
        foreach ($datos['productos'] as $producto) {
            $id = $producto['id_articulo'] ?? 'N/A';
            $desc = $producto['descripcion'] ?? '';
            $cant = $producto['cantidad'] ?? '1';
            $pu = str_replace(',', '', $producto['precio_unitario'] ?? ($producto['precio'] ?? '0'));
            $piva = str_replace(',', '', $producto['precio_con_iva_unitario'] ?? ($producto['precio_con_iva'] ?? '0'));
            $iva = $producto['iva'] ?? 0;
            if (is_numeric($iva)) { $iva = intval(round(floatval($iva) * 100)) . '%'; }
            $pdf->Cell(25, 7, utf8_decode($id), 1, 0, 'C');
            // Descripción con recorte
            $maxDesc = 45;
            $descCorto = mb_substr($desc, 0, $maxDesc);
            $pdf->Cell(80, 7, utf8_decode($descCorto), 1, 0, 'L');
            $pdf->Cell(20, 7, utf8_decode((string)$cant), 1, 0, 'C');
            $pdf->Cell(25, 7, '$' . number_format(floatval($pu)), 1, 0, 'R');
            $pdf->Cell(15, 7, utf8_decode($iva), 1, 0, 'C');
            $pdf->Cell(25, 7, '$' . number_format(floatval($piva)), 1, 1, 'R');
        }

        // Firma
        $pdf->Ln(10);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0, 6, utf8_decode('Atentamente,'), 0, 1, 'L');
        $pdf->Ln(12);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 6, utf8_decode(($datos['firma_gerente'] ?? '')), 0, 1, 'L');
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0, 6, utf8_decode(($datos['cargo'] ?? '')), 0, 1, 'L');

        // Guardar
        $pdf->Output('F', $filepath);
    } else {
        // Si no está disponible FPDF o preferimos HTML, generamos HTML más arriba
        // No lanzamos excepción para mantener salida HTML
        // throw new Exception('Biblioteca FPDF no disponible en el servidor.');
    }
    
    // URL del archivo generado (HTML)
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
        'message' => 'Oferta HTML generada',
        'datos_procesados' => $datos,
        'tipo_archivo' => 'html'
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
        @font-face {
            font-family: "Montserrat";
            font-style: normal;
            font-weight: 400;
            src: local("Montserrat"), local("Montserrat-Regular"), url("https://fonts.gstatic.com/s/montserrat/v25/JTUSjIg1_i6t8kCHKm459WRhyzbi.woff2") format("woff2");
            unicode-range: U+000-5FF;
        }
        body {
            font-family: "Montserrat", Arial, sans-serif;
            margin: 0;
            padding: 8px;
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
        .productos-table thead tr {
            box-shadow: 0 2px 0 rgba(0,0,0,0.05);
        }
        .productos-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        h1 { margin-top: 28px; font-size: 22px; color: #1B365D; }
        h2 { margin-top: 16px; font-size: 18px; color: #2B5C9A; }
        h3 { margin-top: 10px; font-size: 16px; color: #6DC067; }
        .card {
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
            padding: 16px;
            margin: 12px 0 20px;
            background: #fff;
        }
        .page-frame {
            border: none;
            border-radius: 0;
            padding: 6px 8px;
            background: #ffffff;
            width: 100%;
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
        @page { size: A4; margin: 6mm; }
        @media print {
            body { margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .header { page-break-after: avoid; }
            .productos-table th { background-color: #1B365D !important; color: #ffffff !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
            .page-frame { border: none; padding: 4px 6px; }
        }
        .print-bar {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #fff;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .btn-print {
            background: #1B365D;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
        }
        @media print {
            .print-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="page-frame">
    <div class="print-bar">
        <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
    </div>
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
        <p><strong>Cordial Saludo,</strong></p>
        <p>Medirex BIC S.A.S. es una empresa innovadora dedicada a la comercialización de dispositivos médicos, con un firme compromiso en mejorar el don de la vida. Nos especializamos en soluciones avanzadas para neurocirugía, ortopedia y otras especialidades médicas. Nuestra misión es proporcionar productos de alta calidad, cumpliendo con los más estrictos estándares de la industria, respaldados por un equipo de profesionales altamente capacitados y dedicados a la excelencia en el servicio.</p>
        <p>Confiamos en que nuestros productos no solo continuarán mejorando la vida de los pacientes, sino que también superarán las expectativas de calidad de nuestros clientes. Nuestro compromiso incluye:</p>
        <ul>
            <li>Asesoría Comercial Personalizada</li>
            <li>Acompañamiento Quirúrgico Integral</li>
            <li>Capacitaciones Continuas</li>
        </ul>
        <p>Esperamos que esta propuesta sea satisfactoria para su institución y quedamos atentos a sus comentarios para avanzar hacia una negociación efectiva y beneficiosa.</p>
    </div>
    
    <div class="info-section">';
    // Encabezados por selección y descripciones
    $portSel = array_filter(array_map('trim', explode(',', $datos['portafolios'] ?? '')));
    $gruSel = array_filter(array_map('trim', explode(',', $datos['grupos'] ?? '')));
    $espSel = array_filter(array_map('trim', explode(',', $datos['especialidades'] ?? '')));

    // Cargar descripciones por (grupo, portafolio) y especialidad
    $descripciones = [];
    try {
        $archivo = __DIR__ . '/../Recursos/Listado_Categorias_Y_Otros.xlsx';
        if (file_exists($archivo)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
            $hoja = $spreadsheet->getActiveSheet();
            $rows = $hoja->toArray(null, true, true, true);
            foreach ($rows as $i => $fila) {
                if ($i == 1) continue;
                $g = isset($fila['A']) ? trim($fila['A']) : '';
                $p = isset($fila['B']) ? trim($fila['B']) : '';
                $e = isset($fila['C']) ? trim($fila['C']) : '';
                $d = isset($fila['D']) ? trim($fila['D']) : '';
                if ($g === '' || $p === '') continue;
                if (!empty($portSel) && !in_array($p, $portSel, true)) continue;
                if (!empty($gruSel) && !in_array($g, $gruSel, true)) continue;
                if (!empty($espSel) && $e !== '' && !in_array($e, $espSel, true)) continue;
                $descripciones[$p][$g][$e] = $d;
            }
        }
    } catch (\Throwable $ex) {
        // ignorar errores de descripción
    }

    // Agrupar productos por Portafolio > Grupo > Especialidad
    $productos = $datos['productos'] ?? [];
    $agrupados = [];
    foreach ($productos as $p) {
        $pf = $p['portafolio'] ?? 'Otros';
        $gr = $p['grupo'] ?? 'Sin grupo';
        $es = $p['especialidad'] ?? '';
        if (!isset($agrupados[$pf])) $agrupados[$pf] = [];
        if (!isset($agrupados[$pf][$gr])) $agrupados[$pf][$gr] = [];
        if (!isset($agrupados[$pf][$gr][$es])) $agrupados[$pf][$gr][$es] = [];
        $agrupados[$pf][$gr][$es][] = $p;
    }

    // Recorrer lo que realmente hay en productos para garantizar separación por especialidad
    foreach ($agrupados as $portafolio => $gruposProd) {
        $html .= '<div class="card">' . '<h1>' . htmlspecialchars($portafolio) . '</h1>';
        foreach ($gruposProd as $grupo => $espMapProd) {
            $html .= '<h2>' . htmlspecialchars($grupo) . '</h2>';
            foreach ($espMapProd as $esp => $filas) {
                $desc = $descripciones[$portafolio][$grupo][$esp] ?? '';
                if ($esp !== '') {
                    $html .= '<h3>' . htmlspecialchars($esp) . '</h3>';
                }
                if (!empty($desc)) {
                    $html .= '<p>' . nl2br(htmlspecialchars($desc)) . '</p>';
                }
                $html .= '<table class="productos-table"><thead><tr>'
                    . '<th>ID Artículo</th><th>Descripción</th><th>Cant.</th><th>Precio</th><th>IVA</th><th>Precio + IVA</th>'
                    . '</tr></thead><tbody>';
                $sumCant = 0; $sumTotal = 0.0; $sumTotalIva = 0.0;
                foreach ($filas as $prod) {
                    $cant = intval($prod['cantidad'] ?? 1);
                    $pu = floatval(str_replace(',', '', $prod['precio_unitario'] ?? ($prod['precio'] ?? '0')));
                    $pui = floatval(str_replace(',', '', $prod['precio_con_iva_unitario'] ?? ($prod['precio_con_iva'] ?? '0')));
                    $pt = isset($prod['precio_total']) ? floatval(str_replace(',', '', $prod['precio_total'])) : ($pu * $cant);
                    $pti = isset($prod['precio_con_iva_total']) ? floatval(str_replace(',', '', $prod['precio_con_iva_total'])) : ($pui * $cant);
                    $sumCant += $cant; $sumTotal += $pt; $sumTotalIva += $pti;
                    $html .= '<tr>'
                        . '<td>' . htmlspecialchars($prod['id_articulo'] ?? '') . '</td>'
                        . '<td>' . htmlspecialchars($prod['descripcion'] ?? '') . '</td>'
                        . '<td style="text-align:center;">' . $cant . '</td>'
                        . '<td style="text-align:right;">$' . number_format($pu) . '</td>'
                        . '<td style="text-align:center;">' . (isset($prod['iva']) ? (is_numeric($prod['iva']) ? (intval(round(floatval($prod['iva']) * 100)) . '%') : htmlspecialchars($prod['iva'])) : '0%') . '</td>'
                        . '<td style="text-align:right;">$' . number_format($pui) . '</td>'
                    . '</tr>';
                }
                if (empty($filas)) {
                    $html .= '<tr><td colspan="6" style="text-align:center;color:#666;">No hay productos para esta selección</td></tr>';
                } else {
                    $html .= '<tr class="totals-row" style="font-weight:600;background:#f3f6fb;">'
                        . '<td colspan="2">Totales</td>'
                        . '<td style="text-align:center;">' . $sumCant . '</td>'
                        . '<td style="text-align:right;">$' . number_format($sumTotal) . '</td>'
                        . '<td></td>'
                        . '<td style="text-align:right;">$' . number_format($sumTotalIva) . '</td>'
                    . '</tr>';
                }
                $html .= '</tbody></table>';
            }
        }
        $html .= '</div>';
    }
    
    $html .= '
    </div>
    
    <div class="firma">
        <div class="firma-line"></div>
        <p><strong>' . htmlspecialchars($datos['firma_gerente']) . '</strong><br>
        ' . $datos['cargo'] . '</p>
    </div>
    </div>
</body>
</html>';
    
    return $html;
}
?> 