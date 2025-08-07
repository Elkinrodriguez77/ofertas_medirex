<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧹 Limpieza Completa - Empezar desde Cero</h1>";
echo "<p style='color: orange;'><strong>⚠️ Esto eliminará todas las dependencias para empezar limpio</strong></p>";

echo "<h2>1. Estado actual del servidor</h2>";
echo "<p><strong>Versión PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Directorio actual:</strong> " . __DIR__ . "</p>";

echo "<h2>2. Archivos y carpetas a eliminar</h2>";

$items_to_delete = [
    'vendor',
    'vendor_temp',
    'composer.lock',
    'vendor_php8.1.zip',
    'temp'
];

echo "<ul>";
foreach ($items_to_delete as $item) {
    $path = __DIR__ . '/' . $item;
    if (is_dir($path)) {
        echo "<li>📁 <strong>$item/</strong> - Carpeta encontrada</li>";
    } elseif (file_exists($path)) {
        echo "<li>📄 <strong>$item</strong> - Archivo encontrado</li>";
    } else {
        echo "<li>❌ <strong>$item</strong> - No existe</li>";
    }
}
echo "</ul>";

echo "<h2>3. Instrucciones para limpiar desde File Manager</h2>";

echo "<div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0;'>";
echo "<h3>📋 Pasos en File Manager:</h3>";
echo "<ol>";
echo "<li><strong>Ve a cPanel > File Manager</strong></li>";
echo "<li><strong>Navega a la carpeta backend/</strong></li>";
echo "<li><strong>Elimina estas carpetas/archivos:</strong>";
echo "<ul>";
echo "<li>📁 <code>vendor/</code> (si existe)</li>";
echo "<li>📁 <code>vendor_temp/</code> (si existe)</li>";
echo "<li>📄 <code>composer.lock</code> (si existe)</li>";
echo "<li>📄 <code>vendor_php8.1.zip</code> (si existe)</li>";
echo "<li>📁 <code>temp/</code> (si existe)</li>";
echo "</ul></li>";
echo "<li><strong>Deja solo estos archivos:</strong>";
echo "<ul>";
echo "<li>✅ <code>composer.json</code></li>";
echo "<li>✅ <code>clientes.php</code></li>";
echo "<li>✅ <code>grupos.php</code></li>";
echo "<li>✅ <code>productos.php</code></li>";
echo "<li>✅ <code>generar-pdf.php</code></li>";
echo "<li>✅ <code>.htaccess</code></li>";
echo "<li>✅ <code>diagnostico_php8.1.php</code></li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<h2>4. Verificar composer.json</h2>";

if (file_exists(__DIR__ . '/composer.json')) {
    $composer_content = file_get_contents(__DIR__ . '/composer.json');
    $composer_data = json_decode($composer_content, true);
    
    if ($composer_data) {
        echo "<p style='color: green;'>✅ composer.json válido</p>";
        echo "<p><strong>PHP requerido:</strong> " . ($composer_data['require']['php'] ?? 'No especificado') . "</p>";
        if (isset($composer_data['config']['platform']['php'])) {
            echo "<p><strong>Plataforma PHP:</strong> " . $composer_data['config']['platform']['php'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ composer.json corrupto</p>";
    }
} else {
    echo "<p style='color: red;'>❌ composer.json no encontrado</p>";
}

echo "<h2>5. Próximos pasos después de limpiar</h2>";

echo "<div style='background: #d1ecf1; padding: 20px; border-left: 4px solid #17a2b8; margin: 20px 0;'>";
echo "<h3>🎯 Después de limpiar todo:</h3>";
echo "<ol>";
echo "<li><strong>Ejecuta el diagnóstico:</strong> <a href='diagnostico_php8.1.php' style='color: #007cba;'>🔍 Diagnóstico</a></li>";
echo "<li><strong>Si necesitas instalar dependencias:</strong> Contacta a tu hosting</li>";
echo "<li><strong>O usa el instalador manual:</strong> <a href='instalar_desde_filemanager.php' style='color: #28a745;'>📦 Instalador</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>6. Comando para el hosting (si lo necesitas)</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
echo "<p><strong>Comando exacto para que ejecute tu hosting:</strong></p>";
echo "<pre>";
echo "cd /home/creacion/apps.dataworld.com.co/backend\n";
echo "composer install --no-dev --optimize-autoloader --ignore-platform-reqs\n";
echo "chmod -R 755 vendor/\n";
echo "mkdir -p temp\n";
echo "chmod 755 temp\n";
echo "</pre>";
echo "</div>";

echo "<h2>7. Enlaces útiles</h2>";
echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Ejecutar Diagnóstico</a></p>";
echo "<p><a href='instalar_desde_filemanager.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📦 Instalador Manual</a></p>";
echo "<p><a href='../test.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Test Principal</a></p>";
?>
