// Variables globales
let currentStep = 1;
let clientes = [];
let productos = [];
let gruposArticulos = [];
let portafolios = [];
let especialidades = [];

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Función de inicialización
function initializeApp() {
    // Configurar fechas automáticas
    setFechasAutomaticas();
    
    // Cargar datos iniciales
    cargarClientes();
    cargarGruposArticulos();
    
    // Configurar eventos
    setupEventListeners();
    
    // Actualizar UI del wizard
    updateWizardUI();
}

// Configurar fechas automáticas
function setFechasAutomaticas() {
    const hoy = new Date();
    const vigencia = new Date(hoy);
    vigencia.setDate(hoy.getDate() + 30);
    
    document.getElementById('fecha_presentacion').value = hoy.toISOString().split('T')[0];
    document.getElementById('fecha_vigencia').value = vigencia.toISOString().split('T')[0];
}

// Configurar event listeners
function setupEventListeners() {
    // Autocompletado de cliente
    const clienteInput = document.getElementById('cliente');
    if (clienteInput) {
        clienteInput.addEventListener('input', handleClienteInput);
        clienteInput.addEventListener('focus', () => {
            if (clienteInput.value.trim().length > 0) {
                handleClienteInput({ target: clienteInput });
            }
        });
        clienteInput.addEventListener('blur', hideClienteResults);
        
        // Prevenir que se cierre al hacer clic en los resultados
        const resultsContainer = document.getElementById('cliente-results');
        if (resultsContainer) {
            resultsContainer.addEventListener('mousedown', (e) => {
                e.preventDefault();
            });
        }
    }
    
    // Validación de formularios
    document.getElementById('cliente-form').addEventListener('submit', handleClienteSubmit);
    document.getElementById('productos-form').addEventListener('submit', handleProductosSubmit);
}

// Navegación del wizard
function nextStep() {
    if (validateStep1()) {
        currentStep = 2;
        showStep(2);
        updateWizardUI();
    }
}

function prevStep() {
    currentStep = 1;
    showStep(1);
    updateWizardUI();
}

function showStep(step) {
    // Ocultar todos los pasos
    document.querySelectorAll('.step-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Mostrar el paso actual
    document.getElementById(`step${step}-content`).style.display = 'block';
}

function updateWizardUI() {
    // Actualizar indicadores de pasos
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    
    document.getElementById(`step${currentStep}`).classList.add('active');
}

// Validaciones
function validateStep1() {
    const form = document.getElementById('cliente-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--medirex-error)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--medirex-border)';
        }
    });
    
    if (!isValid) {
        showNotification('Por favor completa todos los campos obligatorios', 'error');
    }
    
    return isValid;
}

function validateStep2() {
    const form = document.getElementById('productos-form');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--medirex-error)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--medirex-border)';
        }
    });
    
    // Validar que se haya seleccionado al menos un producto
    const productosSeleccionados = document.querySelectorAll('.producto-item');
    if (productosSeleccionados.length === 0) {
        showNotification('Debes seleccionar al menos un producto', 'error');
        isValid = false;
    }
    
    if (!isValid) {
        showNotification('Por favor completa todos los campos obligatorios', 'error');
    }
    
    return isValid;
}

// Autocompletado de clientes
function handleClienteInput(event) {
    const query = event.target.value.toLowerCase().trim();
    console.log('Buscando clientes con query:', query);
    console.log('Clientes disponibles:', clientes);
    
    if (query.length === 0) {
        hideClienteResults();
        return;
    }
    
    const results = clientes.filter(cliente => 
        cliente.nombre.toLowerCase().includes(query)
    );
    
    console.log('Resultados encontrados:', results);
    showClienteResults(results);
}

function showClienteResults(results = []) {
    const resultsContainer = document.getElementById('cliente-results');
    
    if (!resultsContainer) {
        console.error('No se encontró el contenedor de resultados');
        return;
    }
    
    if (results.length === 0) {
        resultsContainer.style.display = 'none';
        return;
    }
    
    resultsContainer.innerHTML = '';
    results.forEach(cliente => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = cliente.nombre;
        item.addEventListener('click', () => selectCliente(cliente));
        resultsContainer.appendChild(item);
    });
    
    resultsContainer.style.display = 'block';
    console.log('Mostrando resultados de autocompletado:', results.length, 'elementos');
}

function hideClienteResults() {
    setTimeout(() => {
        const resultsContainer = document.getElementById('cliente-results');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }, 200);
}

function selectCliente(cliente) {
    console.log('Cliente seleccionado:', cliente);
    const clienteInput = document.getElementById('cliente');
    const nitInput = document.getElementById('nit');
    
    if (clienteInput && nitInput) {
        clienteInput.value = cliente.nombre;
        nitInput.value = cliente.nit;
        hideClienteResults();
        console.log('Cliente y NIT actualizados correctamente');
    } else {
        console.error('No se encontraron los campos de cliente o NIT');
    }
}

// Carga de datos desde el backend
async function cargarClientes() {
    try {
        showLoading();
        console.log('Cargando clientes...');
        
        const response = await fetch('backend/clientes.php');
        const data = await response.json();
        
        console.log('Respuesta de clientes:', data);
        
        if (data.success) {
            clientes = data.clientes;
            console.log(`Clientes cargados: ${clientes.length}`);
            showNotification(`Clientes cargados: ${clientes.length} registros`, 'success');
        } else {
            showNotification('Error al cargar clientes: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al cargar clientes', 'error');
        console.error('Error:', error);
    } finally {
        hideLoading();
    }
}

async function cargarGruposArticulos() {
    try {
        showLoading();
        console.log('Cargando grupos de artículos...');
        
        const response = await fetch('backend/grupos.php');
        const data = await response.json();
        
        console.log('Respuesta de grupos:', data);
        
        if (data.success) {
            gruposArticulos = data.grupos;
            if (Array.isArray(gruposArticulos)) {
                populatePortafoliosInicial();
                console.log(`Grupos cargados: ${gruposArticulos.length}`);
                showNotification(`Categorías cargadas: ${gruposArticulos.length}`, 'success');
            } else {
                showNotification('Datos de categorías inválidos', 'error');
            }
        } else {
            showNotification('Error al cargar grupos de artículos: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al cargar grupos', 'error');
        console.error('Error:', error);
    } finally {
        hideLoading();
    }
}

function populatePortafoliosInicial() {
    const container = document.getElementById('portafolios');
    if (!container) return;
    container.innerHTML = '';
    const portafoliosUnicos = [...new Set(gruposArticulos.map(g => g.portafolio).filter(Boolean))].sort();
    portafoliosUnicos.forEach(p => {
        const wrapper = document.createElement('label');
        wrapper.className = 'checkbox-item';
        wrapper.innerHTML = `<input type="checkbox" value="${p}" onchange="onPortafoliosChange()"><span>${p}</span>`;
        container.appendChild(wrapper);
    });
}

function onPortafoliosChange() {
    const portafoliosSel = Array.from(document.querySelectorAll('#portafolios input[type="checkbox"]:checked')).map(i => i.value);
    const pfCount = document.getElementById('pf-count');
    if (pfCount) pfCount.textContent = `${portafoliosSel.length} seleccionados`;
    populateGruposByPortafolios(portafoliosSel);
    populateEspecialidadesBy(portafoliosSel, []);
}

function populateGruposByPortafolios(portafoliosSel) {
    const container = document.getElementById('grupos');
    if (!container) return;
    container.innerHTML = '';
    if (!portafoliosSel || portafoliosSel.length === 0) return;
    const grupos = gruposArticulos
        .filter(g => portafoliosSel.includes(g.portafolio))
        .map(g => g.grupo)
        .filter(Boolean);
    const unicos = [...new Set(grupos)].sort();
    unicos.forEach(grupo => {
        const wrapper = document.createElement('label');
        wrapper.className = 'checkbox-item';
        wrapper.innerHTML = `<input type="checkbox" value="${grupo}" onchange="onGruposChange()"><span>${grupo}</span>`;
        container.appendChild(wrapper);
    });
}

function onGruposChange() {
    const portafoliosSel = Array.from(document.querySelectorAll('#portafolios input[type="checkbox"]:checked')).map(i => i.value);
    const gruposSel = Array.from(document.querySelectorAll('#grupos input[type="checkbox"]:checked')).map(i => i.value);
    const grCount = document.getElementById('gr-count');
    if (grCount) grCount.textContent = `${gruposSel.length} seleccionados`;
    populateEspecialidadesBy(portafoliosSel, gruposSel);
    // Si ya hay tipo de precio seleccionado, intentar cargar productos
    const tipoPrecio = document.querySelector('input[name="tipo_precio"]:checked')?.value;
    if (tipoPrecio && portafoliosSel.length > 0 && gruposSel.length > 0) {
        cargarProductos();
    }
}

function populateEspecialidadesBy(portafoliosSel, gruposSel) {
    const container = document.getElementById('especialidades');
    if (!container) return;
    container.innerHTML = '';
    if (!portafoliosSel || portafoliosSel.length === 0 || !gruposSel || gruposSel.length === 0) return;
    const esp = gruposArticulos
        .filter(g => portafoliosSel.includes(g.portafolio) && gruposSel.includes(g.grupo))
        .map(g => g.especialidad)
        .filter(Boolean);
    const unicos = [...new Set(esp)].sort();
    unicos.forEach(e => {
        const wrapper = document.createElement('label');
        wrapper.className = 'checkbox-item';
        wrapper.innerHTML = `<input type="checkbox" value="${e}" onchange="onEspecialidadesChange()"><span>${e}</span>`;
        container.appendChild(wrapper);
    });
}

function onEspecialidadesChange() {
    // Cargar productos en base a la selección actual
    const tipoPrecio = document.querySelector('input[name="tipo_precio"]:checked')?.value;
    const portafoliosSel = Array.from(document.querySelectorAll('#portafolios input[type="checkbox"]:checked')).map(i => i.value);
    const gruposSel = Array.from(document.querySelectorAll('#grupos input[type="checkbox"]:checked')).map(i => i.value);
    const esCount = document.getElementById('es-count');
    if (esCount) esCount.textContent = `${Array.from(document.querySelectorAll('#especialidades input[type="checkbox"]:checked')).length} seleccionadas`;
    if (tipoPrecio && portafoliosSel.length > 0 && gruposSel.length > 0) {
        cargarProductos();
    }
}

async function cargarProductos() {
    const tipoPrecio = document.querySelector('input[name="tipo_precio"]:checked')?.value;
    const portafoliosSel = Array.from(document.querySelectorAll('#portafolios input[type="checkbox"]:checked')).map(i => i.value);
    const gruposSel = Array.from(document.querySelectorAll('#grupos input[type="checkbox"]:checked')).map(i => i.value);
    const especialidadesSel = Array.from(document.querySelectorAll('#especialidades input[type="checkbox"]:checked')).map(i => i.value);
    let nitCliente = '';
    if (tipoPrecio === 'especial') {
        nitCliente = document.getElementById('nit')?.value?.trim() || '';
    }
    
    if (!tipoPrecio || portafoliosSel.length === 0 || gruposSel.length === 0 || (tipoPrecio === 'especial' && !nitCliente)) {
        showNotification('Selecciona tipo de precio, al menos un portafolio y un grupo, y cliente si es especial', 'warning');
        return;
    }
    
    try {
        showLoading();
        const qs = new URLSearchParams();
        qs.set('tipo', tipoPrecio === 'especial' ? 'especial' : 'full');
        qs.set('portafolios', portafoliosSel.join(','));
        qs.set('grupos', gruposSel.join(','));
        if (especialidadesSel.length > 0) qs.set('especialidades', especialidadesSel.join(','));
        if (tipoPrecio === 'especial') qs.set('nit', nitCliente);
        const url = `backend/productos.php?${qs.toString()}`;
        const response = await fetch(url);
        const data = await response.json();
        if (data.success) {
            productos = data.productos.map(p => ({ ...p, cantidad: 1 }));
            mostrarProductos();
            showNotification(`Productos cargados: ${productos.length} artículos`, 'success');
        } else {
            productos = [];
            mostrarProductos();
            showNotification('Error al cargar productos: ' + data.message, 'error');
        }
    } catch (error) {
        productos = [];
        mostrarProductos();
        showNotification('Error de conexión al cargar productos', 'error');
        console.error('Error:', error);
    } finally {
        hideLoading();
    }
}

function mostrarProductos() {
    const container = document.getElementById('productos-container');
    container.innerHTML = '';
    if (!productos || productos.length === 0) {
        container.innerHTML = '<p class="no-products">No hay productos disponibles para este portafolio</p>';
        return;
    }
    productos.forEach((producto, idx) => {
        const item = document.createElement('div');
        item.className = 'producto-item';
        item.innerHTML = `
            <div class="producto-info">
                <h4>${producto.descripcion || 'Sin descripción'}</h4>
                <p><strong>ID:</strong> ${producto.id_articulo || 'N/A'}</p>
                <p><strong>Precio:</strong> $${producto.precio || '0'}</p>
                <p><strong>Precio con IVA:</strong> $${producto.precio_con_iva || '0'}</p>
            </div>
            <div class="producto-cantidad">
                <label>Cantidad:</label>
                <input type="number" min="1" value="${producto.cantidad || 1}" onchange="actualizarCantidadProducto(${idx}, this.value)">
                <button type="button" class="btn-eliminar-producto" title="Eliminar" onclick="eliminarProducto(${idx})">🗑️</button>
            </div>
        `;
        container.appendChild(item);
    });
}

function actualizarCantidadProducto(idx, value) {
    const cantidad = parseInt(value, 10);
    if (!isNaN(cantidad) && cantidad > 0) {
        productos[idx].cantidad = cantidad;
    }
}

function eliminarProducto(idx) {
    productos.splice(idx, 1);
    mostrarProductos();
}

// Generación de PDF
async function generarPDF() {
    if (!validateStep2()) return;
    
    try {
        showLoading();
        
        // Recopilar datos del formulario
        const formData = new FormData();
        
        // Datos del cliente (step 1)
        const clienteForm = document.getElementById('cliente-form');
        const clienteData = new FormData(clienteForm);
        for (let [key, value] of clienteData.entries()) {
            formData.append(key, value);
        }
        
        // Datos de productos (step 2)
        const productosSeleccionados = [];
        document.querySelectorAll('.producto-item').forEach((item, index) => {
            const cantidad = item.querySelector('input[type="number"]').value;
            
            // Usar el índice para obtener el producto correcto de la lista
            const producto = productos[index];
            
            if (producto) {
                // Calcular precios totales por cantidad
                // Remover comas antes de convertir a número
                const precioUnitario = parseFloat((producto.precio || '0').replace(/,/g, ''));
                const precioConIvaUnitario = parseFloat((producto.precio_con_iva || '0').replace(/,/g, ''));
                const cantidadNum = parseInt(cantidad || 1);
                
                const precioTotal = precioUnitario * cantidadNum;
                const precioConIvaTotal = precioConIvaUnitario * cantidadNum;
                
                console.log('Cálculo de precios:', {
                    id: producto.id_articulo,
                    precioUnitario: producto.precio,
                    precioUnitarioLimpio: precioUnitario,
                    cantidad: cantidadNum,
                    precioTotal: precioTotal,
                    precioConIvaTotal: precioConIvaTotal
                });
                
                productosSeleccionados.push({
                    id_articulo: producto.id_articulo || 'N/A',
                    descripcion: producto.descripcion || 'Sin descripción',
                    cantidad: cantidad,
                    iva: (typeof producto.iva !== 'undefined' ? producto.iva : 0),
                    precio_unitario: producto.precio || '0',
                    precio_con_iva_unitario: producto.precio_con_iva || '0',
                    precio_total: precioTotal.toString(),
                    precio_con_iva_total: precioConIvaTotal.toString()
                });
            }
        });
        
        formData.append('productos', JSON.stringify(productosSeleccionados));
        // Agregar filtros seleccionados al FormData (listas CSV)
        formData.append('portafolios', Array.from(document.querySelectorAll('#portafolios input[type="checkbox"]:checked')).map(i => i.value).join(','));
        formData.append('grupos', Array.from(document.querySelectorAll('#grupos input[type="checkbox"]:checked')).map(i => i.value).join(','));
        formData.append('especialidades', Array.from(document.querySelectorAll('#especialidades input[type="checkbox"]:checked')).map(i => i.value).join(','));
        
        console.log('Enviando datos:', {
            cliente: formData.get('cliente'),
            productos: productosSeleccionados
        });
        
        // Enviar al backend
        const response = await fetch('backend/generar-pdf.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        console.log('Respuesta del backend:', data);
        
        if (data.success) {
            showPreview(data.pdf_url);
            showNotification('Oferta generada exitosamente', 'success');
        } else {
            showNotification('Error al generar oferta: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Error de conexión al generar oferta', 'error');
        console.error('Error:', error);
    } finally {
        hideLoading();
    }
}

function showPreview(url) {
    const modal = document.getElementById('preview-modal');
    const iframe = document.getElementById('pdf-preview');
    
    // Determinar el tipo de archivo por la extensión
    const isPDF = url.toLowerCase().endsWith('.pdf');
    
    // Cambiar el título del modal
    const modalTitle = modal.querySelector('h3');
    if (modalTitle) {
        modalTitle.textContent = isPDF ? 'Vista Previa del PDF' : 'Vista Previa de la Oferta';
    }
    
    // Agregar manejo de errores para el iframe
    iframe.onerror = function() {
        console.error('Error al cargar la vista previa:', url);
        showNotification('Error al cargar la vista previa. Intenta descargar el archivo directamente.', 'error');
    };
    
    iframe.onload = function() {
        console.log('Vista previa cargada exitosamente:', url);
    };
    
    console.log('Intentando cargar vista previa:', url);
    iframe.src = url;
    modal.style.display = 'flex';
}

function closePreview() {
    const modal = document.getElementById('preview-modal');
    const iframe = document.getElementById('pdf-preview');
    iframe.src = '';
    modal.style.display = 'none';
}

function descargarPDF() {
    const iframe = document.getElementById('pdf-preview');
    const url = iframe.src;
    
    if (!url) {
        showNotification('No hay archivo para descargar', 'error');
        return;
    }
    
    // Extraer el nombre del archivo de la URL
    const urlParts = url.split('/');
    const filename = urlParts[urlParts.length - 1];
    
    // Usar el endpoint de descarga
    const downloadUrl = `backend/descargar-pdf.php?file=${encodeURIComponent(filename)}`;
    
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    link.click();
}

// Utilidades
function showLoading() {
    document.getElementById('loading-overlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loading-overlay').style.display = 'none';
}

function showNotification(message, type = 'info') {
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Configurar colores según el tipo
    let backgroundColor = 'var(--medirex-success)';
    if (type === 'error') backgroundColor = 'var(--medirex-error)';
    if (type === 'warning') backgroundColor = 'var(--medirex-orange)';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 500;
        z-index: 10000;
        background: ${backgroundColor};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 400px;
        word-wrap: break-word;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Event handlers
function handleClienteSubmit(event) {
    event.preventDefault();
    nextStep();
}

function handleProductosSubmit(event) {
    event.preventDefault();
    generarPDF();
} 