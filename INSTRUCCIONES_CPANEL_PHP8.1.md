# Instrucciones para Instalar en cPanel con PHP 8.1

## ✅ Verificación de Compatibilidad

Tu aplicación **ya es compatible con PHP 8.1**. Todas las dependencias están configuradas correctamente:

- **PhpSpreadsheet 4.4.0** - Compatible con PHP ^8.1 ✅
- **FPDI v2.6.3** - Compatible con PHP ^7.1 || ^8.0 ✅
- **FPDF 1.8.6** - Sin restricciones de PHP ✅

## 📋 Pasos para Instalación en cPanel

### 1. Preparar Archivos
```bash
# Asegúrate de tener todos estos archivos:
- index.html
- css/styles.css
- js/app.js
- js/excel-reader.js
- backend/ (carpeta completa con vendor/)
- Recursos/ (carpeta con archivos Excel y PDF)
- temp/ (carpeta vacía)
```

### 2. Subir a cPanel
1. **Accede a tu cPanel**
2. **Ve a File Manager**
3. **Navega a tu dominio o subdominio**
4. **Sube todos los archivos manteniendo la estructura de carpetas**

### 3. Configurar PHP 8.1
1. **En cPanel, busca "PHP Selector" o "PHP Version"**
2. **Selecciona PHP 8.1** (no 8.2 ni 8.4)
3. **Guarda los cambios**

### 4. Verificar Instalación
1. **Accede a:** `https://tudominio.com/backend/verificar_compatibilidad.php`
2. **Verifica que todos los elementos muestren ✅ verde**

### 5. Configurar Permisos
```bash
# En File Manager, configura estos permisos:
- temp/ → 755 (directorio)
- backend/temp/ → 755 (directorio)
- Recursos/ → 755 (directorio)
- Todos los archivos .php → 644
```

## 🔧 Configuraciones Optimizadas

### Archivos Creados:
- **`backend/verificar_compatibilidad.php`** - Verifica compatibilidad
- **`backend/.htaccess`** - Optimizaciones para cPanel
- **`backend/composer.json`** - Actualizado para PHP 8.1

### Extensiones Requeridas:
- ✅ zip (para Excel)
- ✅ xml (para XML)
- ✅ mbstring (caracteres)
- ✅ gd (para FPDF)
- ✅ iconv (conversión)
- ✅ xmlreader (PhpSpreadsheet)
- ✅ zlib (compresión)

## 🚨 Solución de Problemas

### Error: "Composer detected issues in your platform"
- **Causa:** Versión de PHP incorrecta
- **Solución:** Cambiar a PHP 8.1 en cPanel

### Error: "Class not found"
- **Causa:** Dependencias no instaladas
- **Solución:** Subir carpeta `vendor/` completa

### Error: "File not found"
- **Causa:** Rutas incorrectas
- **Solución:** Verificar estructura de carpetas

### Error: "Permission denied"
- **Causa:** Permisos incorrectos
- **Solución:** Configurar permisos 755 para directorios

## 📞 Soporte

Si encuentras problemas:
1. **Ejecuta** `verificar_compatibilidad.php`
2. **Revisa** los logs de error en cPanel
3. **Verifica** que PHP 8.1 esté activo

## ✅ Lista de Verificación Final

- [ ] PHP 8.1 configurado en cPanel
- [ ] Todos los archivos subidos
- [ ] Estructura de carpetas correcta
- [ ] Permisos configurados
- [ ] `verificar_compatibilidad.php` muestra todo verde
- [ ] Aplicación funciona correctamente

¡Tu aplicación está lista para PHP 8.1! 🎉
