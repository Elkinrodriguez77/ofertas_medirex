<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Solucionando Compatibilidad PHP 8.1 - Medirex App</h1>";
echo "<p style='color: orange;'><strong>⚠️ shell_exec() está deshabilitado en este servidor</strong></p>";

echo "<h2>1. Verificando versión de PHP</h2>";
echo "<p><strong>Versión actual:</strong> " . phpversion() . "</p>";
echo "<p><strong>Versión requerida:</strong> 8.1.0 o superior</p>";

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP 8.1+ detectado - Compatible</p>";
} else {
    echo "<p style='color: red;'>❌ Versión de PHP incompatible</p>";
    exit;
}

echo "<h2>2. Verificando archivos actuales</h2>";

// Verificar si vendor existe
if (is_dir(__DIR__ . '/vendor')) {
    echo "<p style='color: orange;'>⚠️ Carpeta vendor/ existe - Necesita ser eliminada manualmente</p>";
} else {
    echo "<p style='color: green;'>✅ Carpeta vendor/ no existe</p>";
}

// Verificar composer.lock
if (file_exists(__DIR__ . '/composer.lock')) {
    echo "<p style='color: orange;'>⚠️ composer.lock existe - Necesita ser eliminado manualmente</p>";
} else {
    echo "<p style='color: green;'>✅ composer.lock no existe</p>";
}

// Verificar composer.json
if (file_exists(__DIR__ . '/composer.json')) {
    echo "<p style='color: green;'>✅ composer.json encontrado</p>";
    
    // Leer y mostrar configuración
    $composer_config = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
    if ($composer_config) {
        echo "<p><strong>Configuración PHP:</strong> " . ($composer_config['require']['php'] ?? 'No especificado') . "</p>";
        if (isset($composer_config['config']['platform']['php'])) {
            echo "<p><strong>Plataforma PHP:</strong> " . $composer_config['config']['platform']['php'] . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ composer.json no encontrado</p>";
}

echo "<h2>3. Instrucciones Manuales</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #007cba; margin: 20px 0;'>";
echo "<h3>📋 Pasos para Solucionar Manualmente:</h3>";
echo "<ol>";
echo "<li><strong>Eliminar dependencias anteriores:</strong>";
echo "<ul>";
echo "<li>Elimina la carpeta <code>vendor/</code> completa</li>";
echo "<li>Elimina el archivo <code>composer.lock</code></li>";
echo "</ul></li>";

echo "<li><strong>Instalar dependencias para PHP 8.1:</strong>";
echo "<ul>";
echo "<li>Abre SSH/Terminal en cPanel</li>";
echo "<li>Navega a la carpeta: <code>cd /home/creacion/apps.dataworld.com.co/backend</code></li>";
echo "<li>Ejecuta: <code>composer install --no-dev --optimize-autoloader --ignore-platform-reqs</code></li>";
echo "</ul></li>";

echo "<li><strong>Configurar permisos:</strong>";
echo "<ul>";
echo "<li>Ejecuta: <code>chmod -R 755 vendor/</code></li>";
echo "<li>Crea directorio temp: <code>mkdir -p temp && chmod 755 temp</code></li>";
echo "</ul></li>";
echo "</ol>";
echo "</div>";

echo "<h2>4. Alternativa: Instalación desde cPanel</h2>";

echo "<div style='background: #e7f3ff; padding: 20px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<h3>🎯 Si no tienes acceso SSH:</h3>";
echo "<ol>";
echo "<li>Ve a <strong>cPanel > Terminal</strong> (si está disponible)</li>";
echo "<li>O contacta a tu proveedor de hosting para que ejecute los comandos</li>";
echo "<li>O usa el <strong>File Manager</strong> de cPanel para eliminar manualmente vendor/ y composer.lock</li>";
echo "</ol>";
echo "</div>";

echo "<h2>5. Verificación después de la instalación</h2>";

echo "<p>Después de ejecutar los comandos, verifica que:</p>";
echo "<ul>";
echo "<li>✅ Existe <code>vendor/autoload.php</code></li>";
echo "<li>✅ Existe <code>vendor/phpoffice/phpspreadsheet</code></li>";
echo "<li>✅ Existe <code>vendor/setasign/fpdi</code></li>";
echo "<li>✅ Existe <code>vendor/setasign/fpdf</code></li>";
echo "</ul>";

echo "<h2>6. Prueba después de la instalación</h2>";

echo "<p>Una vez instaladas las dependencias, ejecuta:</p>";
echo "<ul>";
echo "<li><a href='diagnostico_php8.1.php' style='color: #007cba;'>🔍 Diagnóstico Completo</a></li>";
echo "<li><a href='../test.php' style='color: #28a745;'>🧪 Test Principal</a></li>";
echo "</ul>";

echo "<h2>7. Comandos exactos para copiar y pegar</h2>";

echo "<div style='background: #f1f1f1; padding: 15px; border-radius: 5px; font-family: monospace;'>";
echo "<p><strong>En SSH/Terminal de cPanel:</strong></p>";
echo "<pre>";
echo "cd /home/creacion/apps.dataworld.com.co/backend\n";
echo "rm -rf vendor/\n";
echo "rm -f composer.lock\n";
echo "composer install --no-dev --optimize-autoloader --ignore-platform-reqs\n";
echo "chmod -R 755 vendor/\n";
echo "mkdir -p temp\n";
echo "chmod 755 temp\n";
echo "</pre>";
echo "</div>";

echo "<h2>8. ¿Necesitas ayuda?</h2>";
echo "<p>Si no puedes ejecutar estos comandos:</p>";
echo "<ul>";
echo "<li>📞 Contacta a tu proveedor de hosting</li>";
echo "<li>💬 Pídeles que ejecuten los comandos de instalación</li>";
echo "<li>📧 Envíales el contenido de tu composer.json</li>";
echo "</ul>";

echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ejecutar Diagnóstico</a></p>";
echo "<p><a href='../test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Test Principal</a></p>";
?>
