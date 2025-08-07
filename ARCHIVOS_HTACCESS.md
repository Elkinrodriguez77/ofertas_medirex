# Archivos .htaccess - Estructura y Propósito

## 📁 Estructura de Archivos .htaccess

Tu aplicación tiene **2 archivos .htaccess** con propósitos diferentes:

### 1. `backend/.htaccess` - Configuración Principal
**Propósito:** Configuración general para PHP 8.1 y optimizaciones del servidor

**Contiene:**
- ✅ Configuración PHP 8.1 (memoria, tiempo de ejecución, etc.)
- ✅ Headers de seguridad
- ✅ Compresión GZIP
- ✅ Cache para archivos estáticos
- ✅ Protección de archivos sensibles
- ✅ Configuración CORS

### 2. `temp/.htaccess` - Configuración de Carpeta Temporal
**Propósito:** Configuración específica para la carpeta de archivos temporales

**Contiene:**
- ✅ Permitir acceso a archivos PDF y HTML generados
- ✅ Configurar tipos MIME correctos
- ✅ Prevenir acceso a archivos sensibles
- ✅ Headers de seguridad para descargas

## ✅ ¿Es Correcto Tener Dos Archivos .htaccess?

**SÍ, es completamente correcto y recomendado** porque:

1. **Separación de responsabilidades** - Cada archivo tiene un propósito específico
2. **Seguridad mejorada** - Diferentes niveles de protección
3. **Mantenimiento más fácil** - Cambios específicos por carpeta
4. **Mejor rendimiento** - Configuraciones optimizadas por contexto

## 🔧 Cómo Funcionan en cPanel

### Jerarquía de Aplicación:
```
backend/.htaccess → Aplica a toda la carpeta backend/
temp/.htaccess → Aplica solo a la carpeta temp/
```

### Orden de Precedencia:
1. **`temp/.htaccess`** tiene prioridad sobre `backend/.htaccess` para archivos en `temp/`
2. **Configuraciones específicas** en `temp/` anulan las generales de `backend/`

## 🚨 Importante para cPanel

### Al Subir a cPanel:
1. **Sube ambos archivos** `.htaccess` manteniendo la estructura
2. **Verifica permisos** - Los archivos `.htaccess` deben tener permisos 644
3. **No elimines ninguno** - Ambos son necesarios

### Verificación:
- **`backend/.htaccess`** → Configura PHP 8.1 y optimizaciones
- **`temp/.htaccess`** → Permite descarga de PDFs generados

## 📋 Resumen de Configuraciones

| Archivo | Propósito | Aplica a |
|---------|-----------|----------|
| `backend/.htaccess` | Configuración general PHP 8.1 | Carpeta `backend/` |
| `temp/.htaccess` | Acceso a archivos temporales | Carpeta `temp/` |

## ✅ Estado Actual

Tu configuración está **correcta y optimizada** para PHP 8.1 en cPanel. No necesitas hacer cambios adicionales.

¡Los dos archivos .htaccess están funcionando correctamente! 🎉
