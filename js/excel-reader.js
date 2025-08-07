// Excel Reader - Funcionalidad de respaldo para lectura de archivos Excel
// Nota: Esta funcionalidad es principalmente para desarrollo local
// En producción, el backend PHP manejará la lectura de Excel

class ExcelReader {
    constructor() {
        this.supportedFormats = ['.xlsx', '.xls'];
    }

    // Verificar si el navegador soporta FileReader
    isSupported() {
        return typeof FileReader !== 'undefined';
    }

    // Leer archivo Excel usando SheetJS (si está disponible)
    async readExcelFile(file) {
        if (!this.isSupported()) {
            throw new Error('FileReader no está soportado en este navegador');
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                try {
                    // Intentar usar SheetJS si está disponible
                    if (typeof XLSX !== 'undefined') {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                        const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                        resolve(jsonData);
                    } else {
                        // Fallback: leer como CSV si es posible
                        const text = e.target.result;
                        const csvData = this.parseCSV(text);
                        resolve(csvData);
                    }
                } catch (error) {
                    reject(error);
                }
            }.bind(this);

            reader.onerror = function() {
                reject(new Error('Error al leer el archivo'));
            };

            reader.readAsArrayBuffer(file);
        });
    }

    // Parser CSV simple como fallback
    parseCSV(text) {
        const lines = text.split('\n');
        const result = [];
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line) {
                const values = line.split(',').map(value => value.trim().replace(/"/g, ''));
                result.push(values);
            }
        }
        
        return result;
    }

    // Convertir datos de Excel a formato JSON estructurado
    convertToStructuredData(rawData, headers = null) {
        if (!rawData || rawData.length === 0) {
            return [];
        }

        // Si no se proporcionan headers, usar la primera fila
        if (!headers) {
            headers = rawData[0];
            rawData = rawData.slice(1);
        }

        return rawData.map(row => {
            const obj = {};
            headers.forEach((header, index) => {
                obj[header] = row[index] || '';
            });
            return obj;
        });
    }

    // Filtrar datos por criterios específicos
    filterData(data, filters) {
        return data.filter(item => {
            return Object.keys(filters).every(key => {
                const filterValue = filters[key];
                const itemValue = item[key];
                
                if (typeof filterValue === 'string') {
                    return itemValue.toLowerCase().includes(filterValue.toLowerCase());
                }
                
                return itemValue === filterValue;
            });
        });
    }

    // Buscar en datos (para autocompletado)
    searchInData(data, searchTerm, searchFields) {
        if (!searchTerm || searchTerm.length < 2) {
            return [];
        }

        const term = searchTerm.toLowerCase();
        return data.filter(item => {
            return searchFields.some(field => {
                const value = item[field];
                return value && value.toLowerCase().includes(term);
            });
        });
    }
}

// Instancia global del Excel Reader
const excelReader = new ExcelReader();

// Funciones de utilidad para el manejo de datos de Excel
window.ExcelUtils = {
    // Cargar datos de clientes desde archivo local (para desarrollo)
    async loadClientesFromFile(file) {
        try {
            const rawData = await excelReader.readExcelFile(file);
            const structuredData = excelReader.convertToStructuredData(rawData, [
                'Nombre_cliente', 'Nit'
            ]);
            
            return structuredData.map(item => ({
                nombre: item.Nombre_cliente,
                nit: item.Nit
            }));
        } catch (error) {
            console.error('Error al cargar archivo de clientes:', error);
            throw error;
        }
    },

    // Cargar datos de productos desde archivo local (para desarrollo)
    async loadProductosFromFile(file) {
        try {
            const rawData = await excelReader.readExcelFile(file);
            const structuredData = excelReader.convertToStructuredData(rawData, [
                'Grupo de articulo', 'Portafolio', 'Número de artículo', 
                'Descripción', 'Precio', 'Precio con IVA', 'url_imagen'
            ]);
            
            return structuredData.map(item => ({
                grupo: item['Grupo de articulo'],
                portafolio: item['Portafolio'],
                id_articulo: item['Número de artículo'],
                descripcion: item['Descripción'],
                precio: item['Precio'],
                precio_con_iva: item['Precio con IVA'],
                url_imagen: item['url_imagen']
            }));
        } catch (error) {
            console.error('Error al cargar archivo de productos:', error);
            throw error;
        }
    },

    // Buscar clientes
    searchClientes(clientes, searchTerm) {
        return excelReader.searchInData(clientes, searchTerm, ['nombre']);
    },

    // Filtrar productos por grupo y portafolio
    filterProductos(productos, grupo, portafolio) {
        const filters = {};
        if (grupo) filters.grupo = grupo;
        if (portafolio) filters.portafolio = portafolio;
        
        return excelReader.filterData(productos, filters);
    },

    // Obtener grupos únicos de productos
    getGruposUnicos(productos) {
        const grupos = [...new Set(productos.map(p => p.grupo))];
        return grupos.filter(grupo => grupo && grupo.trim() !== '');
    },

    // Obtener portafolios únicos por grupo
    getPortafoliosPorGrupo(productos, grupo) {
        const productosDelGrupo = productos.filter(p => p.grupo === grupo);
        const portafolios = [...new Set(productosDelGrupo.map(p => p.portafolio))];
        return portafolios.filter(portafolio => portafolio && portafolio.trim() !== '');
    }
};

// Función para cargar archivos Excel en desarrollo
function loadExcelFile(inputElement, callback) {
    const file = inputElement.files[0];
    if (!file) return;

    const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
    if (!excelReader.supportedFormats.includes(fileExtension)) {
        alert('Por favor selecciona un archivo Excel (.xlsx o .xls)');
        return;
    }

    callback(file);
}

// Exportar para uso global
window.ExcelReader = ExcelReader;
window.loadExcelFile = loadExcelFile; 