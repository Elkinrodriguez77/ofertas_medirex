<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Verificación de Compatibilidad PHP 8.1</h2>";

// Verificar versión de PHP
echo "<h3>Versión de PHP</h3>";
echo "<p><strong>Versión actual:</strong> " . phpversion() . "</p>";
echo "<p><strong>Versión requerida:</strong> 8.1.0 o superior</p>";

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP 8.1+ detectado - Compatible</p>";
} else {
    echo "<p style='color: red;'>❌ Versión de PHP incompatible</p>";
}

// Verificar extensiones requeridas
echo "<h3>Extensiones Requeridas</h3>";
$extensiones_requeridas = [
    'zip' => 'Para leer archivos Excel',
    'xml' => 'Para procesar XML',
    'mbstring' => 'Para manejo de caracteres',
    'gd' => 'Para FPDF',
    'iconv' => 'Para conversión de caracteres',
    'xmlreader' => 'Para PhpSpreadsheet',
    'zlib' => 'Para compresión'
];

foreach ($extensiones_requeridas as $ext => $descripcion) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ $ext - $descripcion</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext - $descripcion (FALTANTE)</p>";
    }
}

// Verificar Composer y dependencias
echo "<h3>Composer y Dependencias</h3>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Autoload de Composer encontrado</p>";
    
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // Verificar PhpSpreadsheet
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            echo "<p style='color: green;'>✅ PhpSpreadsheet cargado correctamente</p>";
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

// Verificar archivos Excel
echo "<h3>Archivos Excel</h3>";
$archivos_excel = [
    '../Recursos/Listado_clientes.xlsx' => 'Lista de clientes',
    '../Recursos/Listado_Categorias_Y_Otros.xlsx' => 'Categorías y otros',
    '../Recursos/Listado_Precios_Full.xlsx' => 'Precios Full',
    '../Recursos/Listado_Precios_Especiales.xlsx' => 'Precios Especiales'
];

foreach ($archivos_excel as $ruta => $descripcion) {
    if (file_exists(__DIR__ . '/' . $ruta)) {
        echo "<p style='color: green;'>✅ $descripcion encontrado</p>";
    } else {
        echo "<p style='color: red;'>❌ $descripcion no encontrado en: $ruta</p>";
    }
}

// Verificar plantilla PDF
echo "<h3>Plantilla PDF</h3>";
if (file_exists(__DIR__ . '/../Recursos/Plantilla_pdf.pdf')) {
    echo "<p style='color: green;'>✅ Plantilla PDF encontrada</p>";
} else {
    echo "<p style='color: red;'>❌ Plantilla PDF no encontrada</p>";
}

// Verificar directorio temporal
echo "<h3>Directorio Temporal</h3>";
$temp_dir = __DIR__ . '/temp';
if (is_dir($temp_dir)) {
    echo "<p style='color: green;'>✅ Directorio temporal existe</p>";
    if (is_writable($temp_dir)) {
        echo "<p style='color: green;'>✅ Directorio temporal es escribible</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Directorio temporal no es escribible</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Directorio temporal no existe (se creará automáticamente)</p>";
}

echo "<h3>Resumen</h3>";
echo "<p>Si todos los elementos muestran ✅ verde, tu aplicación está lista para PHP 8.1.</p>";
echo "<p>Si hay elementos ❌ rojos, necesitas instalarlos o configurarlos antes de subir a cPanel.</p>";
?>
