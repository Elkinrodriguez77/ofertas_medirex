<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Solucionando Compatibilidad PHP 8.1 - Medirex App</h1>";

echo "<h2>1. Verificando versión de PHP</h2>";
echo "<p><strong>Versión actual:</strong> " . phpversion() . "</p>";
echo "<p><strong>Versión requerida:</strong> 8.1.0 o superior</p>";

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP 8.1+ detectado - Compatible</p>";
} else {
    echo "<p style='color: red;'>❌ Versión de PHP incompatible</p>";
    exit;
}

echo "<h2>2. Limpiando instalación anterior</h2>";

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

echo "<h2>3. Verificando Composer</h2>";

// Verificar si composer está disponible
$composer_version = shell_exec('composer --version 2>&1');
if (strpos($composer_version, 'Composer version') !== false) {
    echo "<p style='color: green;'>✅ Composer disponible: " . trim($composer_version) . "</p>";
} else {
    echo "<p style='color: red;'>❌ Composer no está disponible</p>";
    echo "<p><strong>Instalando Composer...</strong></p>";
    
    // Descargar Composer
    $composer_install = shell_exec('curl -sS https://getcomposer.org/installer | php 2>&1');
    if (strpos($composer_install, 'Composer successfully installed') !== false) {
        echo "<p style='color: green;'>✅ Composer instalado correctamente</p>";
        // Mover composer.phar a composer
        shell_exec('mv composer.phar composer');
        shell_exec('chmod +x composer');
    } else {
        echo "<p style='color: red;'>❌ Error al instalar Composer</p>";
        echo "<pre>$composer_install</pre>";
        exit;
    }
}

echo "<h2>4. Instalando dependencias para PHP 8.1</h2>";

// Instalar dependencias con configuración específica para PHP 8.1
echo "<p>Ejecutando: composer install --no-dev --optimize-autoloader --ignore-platform-reqs</p>";
$install_result = shell_exec('composer install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1');

if (strpos($install_result, 'Generating autoload files') !== false || strpos($install_result, 'Installing dependencies') !== false) {
    echo "<p style='color: green;'>✅ Dependencias instaladas correctamente</p>";
} else {
    echo "<p style='color: red;'>❌ Error al instalar dependencias</p>";
    echo "<pre>$install_result</pre>";
    
    // Intentar con composer.phar si existe
    if (file_exists(__DIR__ . '/composer.phar')) {
        echo "<p>Intentando con composer.phar...</p>";
        $install_result2 = shell_exec('php composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1');
        if (strpos($install_result2, 'Generating autoload files') !== false) {
            echo "<p style='color: green;'>✅ Dependencias instaladas con composer.phar</p>";
        } else {
            echo "<p style='color: red;'>❌ Error también con composer.phar</p>";
            echo "<pre>$install_result2</pre>";
        }
    }
}

echo "<h2>5. Verificando instalación</h2>";

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

echo "<h2>6. Configurando permisos</h2>";

// Configurar permisos
$permissions_result = shell_exec('chmod -R 755 vendor/ 2>&1');
if ($permissions_result === null) {
    echo "<p style='color: green;'>✅ Permisos de vendor/ configurados</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Error al configurar permisos: $permissions_result</p>";
}

// Crear directorio temp si no existe
if (!is_dir(__DIR__ . '/temp')) {
    echo "<p>Creando directorio temp...</p>";
    if (mkdir(__DIR__ . '/temp', 0755, true)) {
        echo "<p style='color: green;'>✅ Directorio temp creado</p>";
    } else {
        echo "<p style='color: red;'>❌ Error al crear directorio temp</p>";
    }
}

echo "<h2>7. Prueba de lectura de Excel</h2>";

// Probar lectura de Excel
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
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
    } else {
        echo "<p style='color: red;'>❌ No se puede probar - Composer no está instalado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al leer Excel: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Prueba de API Clientes</h2>";

// Probar API de clientes
try {
    $clientes_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/clientes.php';
    echo "<p>Probando API: $clientes_url</p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 30
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

echo "<h2>9. Resumen</h2>";
echo "<p style='color: green;'>✅ Solución completada</p>";
echo "<p><strong>Próximos pasos:</strong></p>";
echo "<ol>";
echo "<li>Prueba tu aplicación principal</li>";
echo "<li>Verifica que los datos de Excel se carguen correctamente</li>";
echo "<li>Si hay errores, revisa los logs de cPanel</li>";
echo "</ol>";

echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ejecutar Diagnóstico Completo</a></p>";
echo "<p><a href='../test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Test Principal</a></p>";
?>
