<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Reinstalación de Dependencias - Medirex App</h1>";

// Verificar si estamos en el directorio correcto
if (!file_exists(__DIR__ . '/composer.json')) {
    echo "<p style='color: red;'>❌ Error: composer.json no encontrado. Ejecuta este script desde la carpeta backend/</p>";
    exit;
}

echo "<h2>1. Limpiando dependencias anteriores</h2>";

// Eliminar vendor si existe
if (is_dir(__DIR__ . '/vendor')) {
    echo "<p>Eliminando carpeta vendor/...</p>";
    $result = shell_exec('rm -rf vendor/ 2>&1');
    if ($result === null) {
        echo "<p style='color: green;'>✅ Carpeta vendor/ eliminada</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Error al eliminar vendor/: $result</p>";
    }
}

// Eliminar composer.lock si existe
if (file_exists(__DIR__ . '/composer.lock')) {
    echo "<p>Eliminando composer.lock...</p>";
    if (unlink(__DIR__ . '/composer.lock')) {
        echo "<p style='color: green;'>✅ composer.lock eliminado</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Error al eliminar composer.lock</p>";
    }
}

echo "<h2>2. Verificando Composer</h2>";

// Verificar si composer está disponible
$composer_version = shell_exec('composer --version 2>&1');
if (strpos($composer_version, 'Composer version') !== false) {
    echo "<p style='color: green;'>✅ Composer disponible: " . trim($composer_version) . "</p>";
} else {
    echo "<p style='color: red;'>❌ Composer no está disponible</p>";
    echo "<p><strong>Solución:</strong> Instala Composer o contacta a tu proveedor de hosting</p>";
    exit;
}

echo "<h2>3. Instalando dependencias</h2>";

// Instalar dependencias
echo "<p>Ejecutando: composer install --no-dev --optimize-autoloader</p>";
$install_result = shell_exec('composer install --no-dev --optimize-autoloader 2>&1');

if (strpos($install_result, 'Generating autoload files') !== false) {
    echo "<p style='color: green;'>✅ Dependencias instaladas correctamente</p>";
} else {
    echo "<p style='color: red;'>❌ Error al instalar dependencias</p>";
    echo "<pre>$install_result</pre>";
    exit;
}

echo "<h2>4. Verificando instalación</h2>";

// Verificar que las dependencias se instalaron correctamente
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Autoload de Composer creado</p>";
    
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        // Verificar PhpSpreadsheet
        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            echo "<p style='color: green;'>✅ PhpSpreadsheet instalado correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ PhpSpreadsheet no disponible</p>";
        }
        
        // Verificar FPDI
        if (class_exists('setasign\Fpdi\Fpdi')) {
            echo "<p style='color: green;'>✅ FPDI instalado correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ FPDI no disponible</p>";
        }
        
        // Verificar FPDF
        if (class_exists('FPDF')) {
            echo "<p style='color: green;'>✅ FPDF instalado correctamente</p>";
        } else {
            echo "<p style='color: red;'>❌ FPDF no disponible</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al cargar dependencias: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Autoload de Composer no encontrado</p>";
}

echo "<h2>5. Configurando permisos</h2>";

// Configurar permisos
$permissions_result = shell_exec('chmod 755 vendor/ 2>&1');
if ($permissions_result === null) {
    echo "<p style='color: green;'>✅ Permisos de vendor/ configurados</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Error al configurar permisos: $permissions_result</p>";
}

echo "<h2>6. Prueba de lectura de Excel</h2>";

// Probar lectura de Excel
try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $testFile = __DIR__ . '/../Recursos/Listado_clientes.xlsx';
    if (file_exists($testFile)) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($testFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        echo "<p style='color: green;'>✅ Lectura de Excel exitosa - Filas: $highestRow</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Archivo de prueba no encontrado en: $testFile</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al leer Excel: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Resumen</h2>";
echo "<p style='color: green;'>✅ Reinstalación completada</p>";
echo "<p><strong>Próximos pasos:</strong></p>";
echo "<ol>";
echo "<li>Ejecuta <code>diagnostico_php8.1.php</code> para verificar todo</li>";
echo "<li>Prueba tu aplicación</li>";
echo "<li>Si hay errores, revisa los logs de cPanel</li>";
echo "</ol>";

echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ejecutar Diagnóstico Completo</a></p>";
?>
