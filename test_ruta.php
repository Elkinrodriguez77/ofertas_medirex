<?php
echo __DIR__ . '/Recursos/Listado_clientes.xlsx';
echo '<br>';
if (file_exists(__DIR__ . '/Recursos/Listado_clientes.xlsx')) {
    echo 'El archivo SÍ existe y es accesible desde PHP.';
} else {
    echo 'El archivo NO existe o la ruta es incorrecta.';
}
?>