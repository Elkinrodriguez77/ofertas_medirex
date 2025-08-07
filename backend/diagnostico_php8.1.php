<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico Completo PHP 8.1 - Medirex App</h1>";

// 1. Información del sistema
echo "<h2>1. Información del Sistema</h2>";
echo "<p><strong>Versión PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Versión requerida:</strong> 8.1.0 o superior</p>";
echo "<p><strong>SAPI:</strong> " . php_sapi_name() . "</p>";
echo "<p><strong>Directorio actual:</strong> " . __DIR__ . "</p>";

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP 8.1+ detectado - Compatible</p>";
} else {
    echo "<p style='color: red;'>❌ Versión de PHP incompatible</p>";
}

// 2. Extensiones requeridas
echo "<h2>2. Extensiones Requeridas</h2>";
$extensiones_requeridas = [
    'zip' => 'Para leer archivos Excel',
    'xml' => 'Para procesar XML',
    'mbstring' => 'Para manejo de caracteres',
    'gd' => 'Para FPDF',
    'iconv' => 'Para conversión de caracteres',
    'xmlreader' => 'Para PhpSpreadsheet',
    'zlib' => 'Para compresión',
    'ctype' => 'Para PhpSpreadsheet',
    'dom' => 'Para PhpSpreadsheet',
    'fileinfo' => 'Para PhpSpreadsheet',
    'libxml' => 'Para PhpSpreadsheet',
    'simplexml' => 'Para PhpSpreadsheet',
    'xmlwriter' => 'Para PhpSpreadsheet'
];

$extensiones_faltantes = [];
foreach ($extensiones_requeridas as $ext => $descripcion) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ $ext - $descripcion</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext - $descripcion (FALTANTE)</p>";
        $extensiones_faltantes[] = $ext;
    }
}

// 3. Composer y dependencias
echo "<h2>3. Composer y Dependencias</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Autoload de Composer encontrado</p>";
    
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // Verificar PhpSpreadsheet
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            echo "<p style='color: green;'>✅ PhpSpreadsheet cargado correctamente</p>";
            
            // Probar lectura de Excel
            try {
                $testFile = __DIR__ . '/../Recursos/Listado_clientes.xlsx';
                if (file_exists($testFile)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testFile);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $highestRow = $worksheet->getHighestRow();
                    echo "<p style='color: green;'>✅ Lectura de Excel exitosa - Filas: $highestRow</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Archivo de prueba no encontrado</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error al leer Excel: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ PhpSpreadsheet no disponible</p>";
        }
        
        // Verificar FPDI
        if (class_exists('setasign\Fpdi\Fpdi')) {
            echo "<p style='color: green;'>✅ FPDI cargado correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ FPDI no disponible</p>";
        }
        
        // Verificar FPDF
        if (class_exists('FPDF')) {
            echo "<p style='color: green;'>✅ FPDF cargado correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ FPDF no disponible</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar dependencias: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Autoload de Composer no encontrado</p>";
}

// 4. Archivos Excel
echo "<h2>4. Archivos Excel</h2>";
$archivos_excel = [
    '../Recursos/Listado_clientes.xlsx' => 'Lista de clientes',
    '../Recursos/Listado_Categorias_Y_Otros.xlsx' => 'Categorías y otros',
    '../Recursos/Listado_Precios_Full.xlsx' => 'Precios Full',
    '../Recursos/Listado_Precios_Especiales.xlsx' => 'Precios Especiales'
];

foreach ($archivos_excel as $ruta => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $ruta;
    if (file_exists($ruta_completa)) {
        $tamano = filesize($ruta_completa);
        $permisos = substr(sprintf('%o', fileperms($ruta_completa)), -4);
        echo "<p style='color: green;'>✅ $descripcion encontrado (Tamaño: " . number_format($tamano) . " bytes, Permisos: $permisos)</p>";
    } else {
        echo "<p style='color: red;'>❌ $descripcion no encontrado en: $ruta_completa</p>";
    }
}

// 5. Permisos de directorios
echo "<h2>5. Permisos de Directorios</h2>";
$directorios = [
    'temp' => 'Directorio temporal',
    '../temp' => 'Directorio temporal raíz',
    '../Recursos' => 'Directorio de recursos'
];

foreach ($directorios as $dir => $descripcion) {
    $ruta_completa = __DIR__ . '/' . $dir;
    if (is_dir($ruta_completa)) {
        $permisos = substr(sprintf('%o', fileperms($ruta_completa)), -4);
        $escribible = is_writable($ruta_completa) ? 'Sí' : 'No';
        echo "<p style='color: green;'>✅ $descripcion existe (Permisos: $permisos, Escribible: $escribible)</p>";
    } else {
        echo "<p style='color: red;'>❌ $descripcion no existe en: $ruta_completa</p>";
    }
}

// 6. Prueba de APIs
echo "<h2>6. Prueba de APIs</h2>";

// Simular request a clientes.php
echo "<h3>Prueba de API Clientes:</h3>";
try {
    $clientes_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/clientes.php';
    echo "<p>URL de prueba: $clientes_url</p>";
    
    // Crear contexto para simular request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($clientes_url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "<p style='color: green;'>✅ API Clientes funciona correctamente</p>";
                echo "<p>Clientes encontrados: " . ($data['total'] ?? 0) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ API Clientes falló: " . ($data['message'] ?? 'Error desconocido') . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Respuesta de API no válida</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se pudo acceder a la API Clientes</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al probar API: " . $e->getMessage() . "</p>";
}

// 7. Configuración PHP
echo "<h2>7. Configuración PHP</h2>";
$configuraciones = [
    'memory_limit' => 'Límite de memoria',
    'max_execution_time' => 'Tiempo máximo de ejecución',
    'upload_max_filesize' => 'Tamaño máximo de upload',
    'post_max_size' => 'Tamaño máximo de POST',
    'max_input_vars' => 'Variables máximas de entrada'
];

foreach ($configuraciones as $config => $descripcion) {
    $valor = ini_get($config);
    echo "<p><strong>$descripcion:</strong> $valor</p>";
}

// 8. Resumen y recomendaciones
echo "<h2>8. Resumen y Recomendaciones</h2>";

if (empty($extensiones_faltantes)) {
    echo "<p style='color: green;'>✅ Todas las extensiones requeridas están instaladas</p>";
} else {
    echo "<p style='color: red;'>❌ Extensiones faltantes: " . implode(', ', $extensiones_faltantes) . "</p>";
    echo "<p><strong>Recomendación:</strong> Contacta a tu proveedor de hosting para instalar las extensiones faltantes.</p>";
}

echo "<h3>Próximos pasos:</h3>";
echo "<ol>";
echo "<li>Si hay extensiones faltantes, solicítalas a tu hosting</li>";
echo "<li>Verifica que PHP 8.1 esté activo en cPanel</li>";
echo "<li>Ejecuta: <code>composer install</code> en la carpeta backend</li>";
echo "<li>Configura permisos 755 para directorios y 644 para archivos</li>";
echo "</ol>";

echo "<h3>Comando para reinstalar dependencias:</h3>";
echo "<pre>cd backend && composer install --no-dev --optimize-autoloader</pre>";
?>
