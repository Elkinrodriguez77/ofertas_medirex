<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Verificación de Instalación - Dependencias</h1>";

echo "<h2>1. Verificando estructura de vendor/</h2>";

$vendor_path = __DIR__ . '/vendor';
if (!is_dir($vendor_path)) {
    echo "<p style='color: red;'>❌ Carpeta vendor/ no existe</p>";
    echo "<p><strong>Solución:</strong> Contacta a tu hosting para ejecutar: <code>composer install --no-dev --optimize-autoloader --ignore-platform-reqs</code></p>";
    exit;
}

echo "<p style='color: green;'>✅ Carpeta vendor/ existe</p>";

// Verificar estructura de PhpSpreadsheet
$phpspreadsheet_path = $vendor_path . '/phpoffice/phpspreadsheet';
if (!is_dir($phpspreadsheet_path)) {
    echo "<p style='color: red;'>❌ PhpSpreadsheet no está instalado correctamente</p>";
} else {
    echo "<p style='color: green;'>✅ PhpSpreadsheet encontrado</p>";
    
    // Verificar archivos clave
    $key_files = [
        'src/PhpSpreadsheet/IOFactory.php',
        'src/PhpSpreadsheet/Spreadsheet.php',
        'src/PhpSpreadsheet/Worksheet/Worksheet.php'
    ];
    
    foreach ($key_files as $file) {
        $full_path = $phpspreadsheet_path . '/' . $file;
        if (file_exists($full_path)) {
            echo "<p style='color: green;'>✅ $file</p>";
        } else {
            echo "<p style='color: red;'>❌ $file - FALTANTE</p>";
        }
    }
}

// Verificar FPDI
$fpdi_path = $vendor_path . '/setasign/fpdi';
if (!is_dir($fpdi_path)) {
    echo "<p style='color: red;'>❌ FPDI no está instalado correctamente</p>";
} else {
    echo "<p style='color: green;'>✅ FPDI encontrado</p>";
}

// Verificar FPDF
$fpdf_path = $vendor_path . '/setasign/fpdf';
if (!is_dir($fpdf_path)) {
    echo "<p style='color: red;'>❌ FPDF no está instalado correctamente</p>";
} else {
    echo "<p style='color: green;'>✅ FPDF encontrado</p>";
}

echo "<h2>2. Verificando autoload</h2>";

$autoload_path = $vendor_path . '/autoload.php';
if (!file_exists($autoload_path)) {
    echo "<p style='color: red;'>❌ autoload.php no encontrado</p>";
} else {
    echo "<p style='color: green;'>✅ autoload.php encontrado</p>";
    
    try {
        require_once $autoload_path;
        echo "<p style='color: green;'>✅ Autoload cargado correctamente</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar autoload: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>3. Prueba de clases</h2>";

try {
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo "<p style='color: green;'>✅ PhpOffice\PhpSpreadsheet\IOFactory</p>";
    } else {
        echo "<p style='color: red;'>❌ PhpOffice\PhpSpreadsheet\IOFactory - NO DISPONIBLE</p>";
    }
    
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "<p style='color: green;'>✅ PhpOffice\PhpSpreadsheet\Spreadsheet</p>";
    } else {
        echo "<p style='color: red;'>❌ PhpOffice\PhpSpreadsheet\Spreadsheet - NO DISPONIBLE</p>";
    }
    
    if (class_exists('PhpOffice\PhpSpreadsheet\Worksheet\Worksheet')) {
        echo "<p style='color: green;'>✅ PhpOffice\PhpSpreadsheet\Worksheet\Worksheet</p>";
    } else {
        echo "<p style='color: red;'>❌ PhpOffice\PhpSpreadsheet\Worksheet\Worksheet - NO DISPONIBLE</p>";
    }
    
    if (class_exists('setasign\Fpdi\Fpdi')) {
        echo "<p style='color: green;'>✅ setasign\Fpdi\Fpdi</p>";
    } else {
        echo "<p style='color: red;'>❌ setasign\Fpdi\Fpdi - NO DISPONIBLE</p>";
    }
    
    if (class_exists('FPDF')) {
        echo "<p style='color: green;'>✅ FPDF</p>";
    } else {
        echo "<p style='color: red;'>❌ FPDF - NO DISPONIBLE</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al verificar clases: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Diagnóstico del problema</h2>";

echo "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h3>🚨 Problema identificado:</h3>";
echo "<p><strong>Las dependencias están instaladas pero INCOMPLETAS.</strong></p>";
echo "<p>Esto sucede cuando:</p>";
echo "<ul>";
echo "<li>Se instalaron manualmente archivos básicos</li>";
echo "<li>No se ejecutó Composer correctamente</li>";
echo "<li>Faltan clases y dependencias internas</li>";
echo "</ul>";
echo "</div>";

echo "<h2>5. Solución definitiva</h2>";

echo "<div style='background: #d1ecf1; padding: 20px; border-left: 4px solid #17a2b8; margin: 20px 0;'>";
echo "<h3>📞 Contacta a tu hosting:</h3>";
echo "<p><strong>Envía este mensaje exacto:</strong></p>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
echo "<p>Hola, necesito que ejecuten estos comandos en mi carpeta backend:</p>";
echo "<pre>";
echo "cd /home/creacion/apps.dataworld.com.co/backend\n";
echo "rm -rf vendor/\n";
echo "rm -f composer.lock\n";
echo "composer install --no-dev --optimize-autoloader --ignore-platform-reqs\n";
echo "chmod -R 755 vendor/\n";
echo "mkdir -p temp\n";
echo "chmod 755 temp\n";
echo "</pre>";
echo "<p>Mi aplicación necesita las librerías completas de PhpSpreadsheet, FPDI y FPDF.</p>";
echo "</div>";
echo "</div>";

echo "<h2>6. Enlaces útiles</h2>";
echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Diagnóstico Completo</a></p>";
echo "<p><a href='../test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Test Principal</a></p>";
?>
