<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de PHP - Medirex App</h1>";
echo "<p><strong>Versión PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Directorio actual:</strong> " . __DIR__ . "</p>";

// Verificar si existe el archivo de diagnóstico
$diagnostico_path = __DIR__ . '/backend/diagnostico_php8.1.php';
if (file_exists($diagnostico_path)) {
    echo "<p style='color: green;'>✅ Archivo de diagnóstico encontrado</p>";
    echo "<p><a href='backend/diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ejecutar Diagnóstico Completo</a></p>";
} else {
    echo "<p style='color: red;'>❌ Archivo de diagnóstico no encontrado en: $diagnostico_path</p>";
}

// Verificar si existe el archivo de reinstalación
$reinstalar_path = __DIR__ . '/backend/reinstalar_dependencias.php';
if (file_exists($reinstalar_path)) {
    echo "<p style='color: green;'>✅ Archivo de reinstalación encontrado</p>";
    echo "<p><a href='backend/reinstalar_dependencias.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reinstalar Dependencias</a></p>";
} else {
    echo "<p style='color: red;'>❌ Archivo de reinstalación no encontrado</p>";
}

// Verificar estructura de carpetas
echo "<h2>Estructura de Carpetas:</h2>";
$carpetas = ['backend', 'Recursos', 'temp', 'css', 'js'];
foreach ($carpetas as $carpeta) {
    if (is_dir(__DIR__ . '/' . $carpeta)) {
        echo "<p style='color: green;'>✅ Carpeta $carpeta existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Carpeta $carpeta no existe</p>";
    }
}

// Verificar archivos Excel
echo "<h2>Archivos Excel:</h2>";
$archivos_excel = [
    'Recursos/Listado_clientes.xlsx',
    'Recursos/Listado_Categorias_Y_Otros.xlsx',
    'Recursos/Listado_Precios_Full.xlsx',
    'Recursos/Listado_Precios_Especiales.xlsx'
];

foreach ($archivos_excel as $archivo) {
    if (file_exists(__DIR__ . '/' . $archivo)) {
        $tamano = filesize(__DIR__ . '/' . $archivo);
        echo "<p style='color: green;'>✅ $archivo encontrado (Tamaño: " . number_format($tamano) . " bytes)</p>";
    } else {
        echo "<p style='color: red;'>❌ $archivo no encontrado</p>";
    }
}

// Verificar Composer
echo "<h2>Composer y Dependencias:</h2>";
if (file_exists(__DIR__ . '/backend/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Autoload de Composer encontrado</p>";
    
    try {
        require_once __DIR__ . '/backend/vendor/autoload.php';
        
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            echo "<p style='color: green;'>✅ PhpSpreadsheet disponible</p>";
        } else {
            echo "<p style='color: red;'>❌ PhpSpreadsheet NO disponible</p>";
        }
        
        if (class_exists('setasign\Fpdi\Fpdi')) {
            echo "<p style='color: green;'>✅ FPDI disponible</p>";
        } else {
            echo "<p style='color: red;'>❌ FPDI NO disponible</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar dependencias: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Autoload de Composer NO encontrado</p>";
    echo "<p><strong>Este es el problema principal. Necesitas reinstalar las dependencias.</strong></p>";
}

// Prueba directa de lectura de Excel
echo "<h2>Prueba de Lectura de Excel:</h2>";
try {
    if (file_exists(__DIR__ . '/backend/vendor/autoload.php')) {
        require_once __DIR__ . '/backend/vendor/autoload.php';
        
        $testFile = __DIR__ . '/Recursos/Listado_clientes.xlsx';
        if (file_exists($testFile)) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testFile);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            echo "<p style='color: green;'>✅ Lectura de Excel exitosa - Filas: $highestRow</p>";
        } else {
            echo "<p style='color: red;'>❌ Archivo de prueba no encontrado</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se puede probar - Composer no está instalado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al leer Excel: " . $e->getMessage() . "</p>";
}

echo "<h2>Próximos Pasos:</h2>";
echo "<ol>";
echo "<li>Si Composer no está instalado, haz clic en 'Reinstalar Dependencias'</li>";
echo "<li>Si hay errores, ejecuta el 'Diagnóstico Completo'</li>";
echo "<li>Verifica que PHP 8.1 esté activo en cPanel</li>";
echo "</ol>";
?> 