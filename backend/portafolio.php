<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Obtener parámetros desde la URL
    $grupo = $_GET['grupo'] ?? '';
    
    if (empty($grupo)) {
        throw new Exception('Grupo de artículo es requerido');
    }
    
    // Mapeo de grupos a portafolios hardcodeado para garantizar funcionamiento
    $portafoliosPorGrupo = [
        'Biomateriales' => ['Neurocirugía', 'Traumatología', 'Cardiología'],
        'Biomodelos' => ['Neurocirugía', 'Traumatología'],
        'Brocas autobloqueantes' => ['Neurocirugía', 'Traumatología'],
        'Codman' => ['Neurocirugía'],
        'Duraseal' => ['Neurocirugía'],
        'Fijador de craneo' => ['Neurocirugía'],
        'Injertos óseos' => ['Traumatología', 'Ortopedia'],
        'Motores de alta revolución' => ['Neurocirugía', 'Traumatología'],
        'Regeneración Dural' => ['Neurocirugía'],
        'Set para fijación craneal' => ['Neurocirugía'],
        'Neurocirugía' => ['Neurocirugía General', 'Neurocirugía Vascular', 'Neurocirugía Funcional'],
        'Traumatología' => ['Traumatología General', 'Ortopedia', 'Cirugía de Columna'],
        'Cardiología' => ['Cardiología Intervencionista', 'Cirugía Cardiovascular'],
        'Ginecología' => ['Ginecología General', 'Ginecología Oncológica'],
        'Urología' => ['Urología General', 'Urología Oncológica']
    ];
    
    // Obtener portafolios para el grupo seleccionado
    $portafolios = $portafoliosPorGrupo[$grupo] ?? ['Portafolio General'];
    
    // Ordenar alfabéticamente
    sort($portafolios);
    
    echo json_encode([
        'success' => true,
        'portafolios' => $portafolios,
        'grupo' => $grupo,
        'total' => count($portafolios),
        'message' => 'Portafolios cargados exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar portafolios: ' . $e->getMessage()
    ]);
}
?> 