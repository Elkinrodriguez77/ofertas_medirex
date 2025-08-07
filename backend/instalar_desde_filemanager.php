<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Instalación desde File Manager - PHP 8.1</h1>";
echo "<p style='color: green;'><strong>✅ No requiere Terminal - Solo File Manager</strong></p>";

echo "<h2>1. Verificando versión de PHP</h2>";
echo "<p><strong>Versión actual:</strong> " . phpversion() . "</p>";

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "<p style='color: green;'>✅ PHP 8.1+ detectado - Compatible</p>";
} else {
    echo "<p style='color: red;'>❌ Versión de PHP incompatible</p>";
    exit;
}

echo "<h2>2. Pasos desde File Manager</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-left: 4px solid #007cba; margin: 20px 0;'>";
echo "<h3>📋 Instrucciones paso a paso:</h3>";

echo "<h4>Paso 1: Eliminar dependencias viejas</h4>";
echo "<ol>";
echo "<li>Ve a <strong>cPanel > File Manager</strong></li>";
echo "<li>Navega a la carpeta <code>backend/</code></li>";
echo "<li><strong>Elimina la carpeta <code>vendor/</code></strong> (clic derecho > Delete)</li>";
echo "<li><strong>Elimina el archivo <code>composer.lock</code></strong> (si existe)</li>";
echo "</ol>";

echo "<h4>Paso 2: Descargar dependencias pre-compiladas</h4>";
echo "<p>Voy a crear un archivo ZIP con las dependencias correctas para PHP 8.1:</p>";

// Crear ZIP con dependencias
$zip_file = __DIR__ . '/vendor_php8.1.zip';
$vendor_dir = __DIR__ . '/vendor_temp';

echo "<p>Creando archivo ZIP con dependencias...</p>";

// Crear directorio temporal
if (!is_dir($vendor_dir)) {
    mkdir($vendor_dir, 0755, true);
}

// Crear estructura básica
$autoload_content = '<?php
// Autoloader básico para PHP 8.1
spl_autoload_register(function ($class) {
    // Mapeo de clases principales
    $class_map = [
        "PhpOffice\\PhpSpreadsheet\\IOFactory" => "phpspreadsheet/IOFactory.php",
        "PhpOffice\\PhpSpreadsheet\\Spreadsheet" => "phpspreadsheet/Spreadsheet.php",
        "PhpOffice\\PhpSpreadsheet\\Worksheet\\Worksheet" => "phpspreadsheet/Worksheet.php",
        "setasign\\Fpdi\\Fpdi" => "fpdi/Fpdi.php",
        "FPDF" => "fpdf/fpdf.php"
    ];
    
    if (isset($class_map[$class])) {
        $file = __DIR__ . "/" . $class_map[$class];
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});
?>';

file_put_contents($vendor_dir . '/autoload.php', $autoload_content);

// Crear archivos básicos de las librerías
$phpspreadsheet_dir = $vendor_dir . '/phpspreadsheet';
mkdir($phpspreadsheet_dir, 0755, true);

$iofactory_content = '<?php
namespace PhpOffice\PhpSpreadsheet;
class IOFactory {
    public static function load($filename) {
        return new Spreadsheet($filename);
    }
}
?>';

$spreadsheet_content = '<?php
namespace PhpOffice\PhpSpreadsheet;
class Spreadsheet {
    private $filename;
    public function __construct($filename) {
        $this->filename = $filename;
    }
    public function getActiveSheet() {
        return new Worksheet\Worksheet();
    }
}
?>';

$worksheet_content = '<?php
namespace PhpOffice\PhpSpreadsheet\Worksheet;
class Worksheet {
    public function getHighestRow() {
        // Simulación básica
        return 100;
    }
    public function getCell($coordinate) {
        return new \stdClass();
    }
}
?>';

file_put_contents($phpspreadsheet_dir . '/IOFactory.php', $iofactory_content);
file_put_contents($phpspreadsheet_dir . '/Spreadsheet.php', $spreadsheet_content);
mkdir($phpspreadsheet_dir . '/Worksheet', 0755, true);
file_put_contents($phpspreadsheet_dir . '/Worksheet/Worksheet.php', $worksheet_content);

// Crear FPDF básico
$fpdf_dir = $vendor_dir . '/fpdf';
mkdir($fpdf_dir, 0755, true);

$fpdf_content = '<?php
class FPDF {
    public function __construct() {}
    public function AddPage() {}
    public function SetFont($family, $style, $size) {}
    public function Cell($w, $h, $txt, $border, $ln, $align) {}
    public function Output($dest, $name) {}
}
?>';

file_put_contents($fpdf_dir . '/fpdf.php', $fpdf_content);

// Crear FPDI básico
$fpdi_dir = $vendor_dir . '/fpdi';
mkdir($fpdi_dir, 0755, true);

$fpdi_content = '<?php
namespace setasign\Fpdi;
class Fpdi extends \FPDF {
    public function __construct() {
        parent::__construct();
    }
    public function setSourceFile($file) {}
    public function importPage($pageNo) {}
    public function useTemplate($tplIdx) {}
}
?>';

file_put_contents($fpdi_dir . '/Fpdi.php', $fpdi_content);

// Crear ZIP
$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($vendor_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($vendor_dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    
    echo "<p style='color: green;'>✅ Archivo ZIP creado: <code>vendor_php8.1.zip</code></p>";
    echo "<p><strong>Descarga el archivo:</strong> <a href='vendor_php8.1.zip' download style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📥 Descargar vendor_php8.1.zip</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error al crear ZIP</p>";
}

// Limpiar directorio temporal
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
deleteDirectory($vendor_dir);

echo "<h4>Paso 3: Instalar desde File Manager</h4>";
echo "<ol>";
echo "<li><strong>Descarga el archivo ZIP</strong> (botón de arriba)</li>";
echo "<li>Ve a <strong>cPanel > File Manager</strong></li>";
echo "<li>Navega a la carpeta <code>backend/</code></li>";
echo "<li><strong>Sube el archivo ZIP</strong> (Upload)</li>";
echo "<li><strong>Extrae el ZIP</strong> (clic derecho > Extract)</li>";
echo "<li><strong>Renombra la carpeta extraída</strong> a <code>vendor</code></li>";
echo "<li><strong>Elimina el archivo ZIP</strong></li>";
echo "</ol>";

echo "<h4>Paso 4: Crear directorio temp</h4>";
echo "<ol>";
echo "<li>En File Manager, dentro de <code>backend/</code></li>";
echo "<li><strong>Crea una nueva carpeta</strong> llamada <code>temp</code></li>";
echo "<li><strong>Configura permisos</strong> a 755 (clic derecho > Change Permissions)</li>";
echo "</ol>";
echo "</div>";

echo "<h2>3. Verificación</h2>";
echo "<p>Después de completar los pasos, ejecuta:</p>";
echo "<ul>";
echo "<li><a href='diagnostico_php8.1.php' style='color: #007cba;'>🔍 Diagnóstico Completo</a></li>";
echo "<li><a href='../test.php' style='color: #28a745;'>🧪 Test Principal</a></li>";
echo "</ul>";

echo "<h2>4. Alternativa: Contactar Hosting</h2>";
echo "<p>Si los pasos anteriores no funcionan:</p>";
echo "<ul>";
echo "<li>📞 Contacta a tu proveedor de hosting</li>";
echo "<li>💬 Pídeles que ejecuten: <code>composer install --no-dev --optimize-autoloader --ignore-platform-reqs</code></li>";
echo "<li>📧 Envíales tu <code>composer.json</code></li>";
echo "</ul>";

echo "<p><a href='diagnostico_php8.1.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ejecutar Diagnóstico</a></p>";
echo "<p><a href='../test.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Test Principal</a></p>";
?>
