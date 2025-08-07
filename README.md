# Medirex App - Generador de Ofertas

Aplicación web para generar ofertas comerciales con lectura de datos desde archivos Excel y generación de PDFs.

## 🚀 Características

- ✅ Lectura de datos desde archivos Excel (.xlsx)
- ✅ Generación de PDFs con plantillas personalizables
- ✅ Interfaz web moderna y responsiva
- ✅ Compatible con PHP 8.1+
- ✅ Optimizado para cPanel

## 📋 Requisitos

- PHP 8.1 o superior
- Extensiones PHP: zip, xml, mbstring, gd, iconv, xmlreader, zlib
- Composer (para dependencias)
- cPanel con acceso SSH (opcional)

## 🛠️ Instalación Local

### 1. Clonar el repositorio
```bash
git clone https://github.com/tuusuario/medirex-app.git
cd medirex-app
```

### 2. Instalar dependencias
```bash
cd backend
composer install
```

### 3. Configurar archivos
- Asegúrate de que los archivos Excel estén en `Recursos/`
- Verifica que la plantilla PDF esté en `Recursos/Plantilla_pdf.pdf`

### 4. Ejecutar servidor local
```bash
php -S localhost:8000
```

### 5. Verificar instalación
Accede a: `http://localhost:8000/backend/verificar_compatibilidad.php`

## 🌐 Deploy en cPanel

### Opción 1: Git Hooks (Recomendado)

1. **Subir a GitHub:**
```bash
git add .
git commit -m "Versión inicial"
git push origin main
```

2. **Configurar en cPanel:**
   - Ve a "Git Version Control"
   - Crea nuevo repositorio
   - URL: `https://github.com/tuusuario/medirex-app.git`
   - Directorio: `public_html/tuapp`
   - Activa "Auto Deploy"

### Opción 2: GitHub Actions (Automático)

1. **Configurar secrets en GitHub:**
   - `CPANEL_HOST`: Tu servidor cPanel
   - `CPANEL_USERNAME`: Usuario cPanel
   - `CPANEL_PASSWORD`: Contraseña cPanel

2. **Push automático:**
```bash
git push origin main
```

### Opción 3: Upload Manual

1. **Comprimir archivos:**
```bash
zip -r medirex-app.zip . -x "temp/*" "*.log"
```

2. **Subir via FileZilla o File Manager**

## 📁 Estructura del Proyecto

```
medirex-app/
├── index.html              # Interfaz principal
├── css/styles.css          # Estilos
├── js/
│   ├── app.js             # Lógica principal
│   └── excel-reader.js    # Lectura de Excel
├── backend/
│   ├── clientes.php       # API clientes
│   ├── productos.php      # API productos
│   ├── grupos.php         # API grupos
│   ├── generar-pdf.php    # Generación PDF
│   ├── composer.json      # Dependencias
│   └── .htaccess          # Configuración Apache
├── Recursos/
│   ├── Listado_clientes.xlsx
│   ├── Listado_Categorias_Y_Otros.xlsx
│   ├── Listado_Precios_Full.xlsx
│   ├── Listado_Precios_Especiales.xlsx
│   └── Plantilla_pdf.pdf
└── temp/                  # Archivos temporales
```

## 🔧 Configuración

### PHP 8.1 en cPanel
1. Ve a "PHP Selector"
2. Selecciona PHP 8.1
3. Guarda cambios

### Permisos
```bash
# Directorios
chmod 755 temp/
chmod 755 backend/temp/
chmod 755 Recursos/

# Archivos
chmod 644 *.php
chmod 644 *.htaccess
```

## 🚨 Solución de Problemas

### Error: "Composer detected issues"
- Verifica que PHP 8.1 esté activo en cPanel

### Error: "Class not found"
- Asegúrate de que la carpeta `vendor/` esté subida

### Error: "File not found"
- Verifica la estructura de carpetas
- Revisa las rutas en los archivos PHP

## 📞 Soporte

Para problemas técnicos:
1. Ejecuta `backend/verificar_compatibilidad.php`
2. Revisa los logs de error en cPanel
3. Verifica la configuración de PHP

## 📄 Licencia

Este proyecto es de uso interno para Medirex.

---

**¡Tu aplicación está lista para producción!** 🎉
