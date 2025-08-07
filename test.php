<?php
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
        echo "<p style='color: green;'>✅ $archivo encontrado</p>";
    } else {
        echo "<p style='color: red;'>❌ $archivo no encontrado</p>";
    }
}
?> 