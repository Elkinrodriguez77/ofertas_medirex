<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $filename = $_GET['file'] ?? '';
    
    if (empty($filename)) {
        throw new Exception('Nombre de archivo no especificado');
    }
    
    // Validar que el archivo existe y está en la carpeta temp
    $filepath = 'temp/' . basename($filename);
    
    if (!file_exists($filepath)) {
        throw new Exception('Archivo no encontrado');
    }
    
    // Verificar que es un archivo PDF o HTML
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'html'])) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    // Configurar headers para descarga
    $contentType = $extension === 'pdf' ? 'application/pdf' : 'text/html';
    $disposition = 'attachment; filename="' . basename($filename) . '"';
    
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: ' . $disposition);
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Leer y enviar el archivo
    readfile($filepath);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al descargar archivo: ' . $e->getMessage()
    ]);
}
?>
