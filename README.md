# Sistema de Tickets - Municipalidad Provincial de Canchis

Sistema de gestión de tickets de soporte técnico diseñado para la Municipalidad Provincial de Canchis.

## Descripción

Este sistema permite la creación, seguimiento y gestión de tickets de soporte técnico TI para los diferentes departamentos de la Municipalidad. Los usuarios pueden reportar problemas técnicos y hacer seguimiento de sus solicitudes, mientras que los administradores pueden gestionar, priorizar y resolver los tickets.

## Características

- **Para Usuarios**:
  - Creación de tickets de soporte
  - Seguimiento del estado de tickets mediante número de ticket
  - Interfaz intuitiva y fácil de usar

- **Para Administradores**:
  - Panel de administración con listado completo de tickets
  - Actualización de estado, diagnóstico y solución aplicada
  - Clasificación de tickets por prioridad
  - Generación de informes de resolución con opciones de impresión
  - Impresión directa o generación de PDF de tickets resueltos

## Requisitos Técnicos

- PHP 7.4 o superior
- MySQL/MariaDB
- Servidor web Apache
- XAMPP (recomendado para desarrollo local)

## Instalación

1. Clonar o descargar este repositorio en el directorio htdocs de XAMPP
2. Asegurarse que el servidor MySQL esté funcionando
3. Acceder a `http://localhost/Sistema_de_tikets/setup.php` para inicializar la base de datos y las tablas necesarias
4. El sistema creará automáticamente un usuario administrador:
   - Usuario: admin
   - Contraseña: admin123
   - **IMPORTANTE**: Cambiar la contraseña después del primer inicio de sesión

## Estructura de Archivos

```
Sistema_de_tikets/
├── admin.php             # Panel de administración
├── generar_informe.php   # Generador de informes de resolución
├── generar_informe_v2.php # Versión mejorada del generador de informes
├── guardar_ticket.php    # Procesamiento de nuevos tickets
├── imprimir_informe.php  # Versión optimizada para impresión de informes
├── index.php             # Página principal - Creación de tickets
├── login_admin.php       # Acceso a panel de administración
├── logout_admin.php      # Cierre de sesión
├── pdf_generator.php     # Utilidades para generar PDF
├── procesar_login_admin.php  # Procesamiento de credenciales
├── seguimiento.php       # Seguimiento de tickets para usuarios
├── setup.php             # Configuración inicial del sistema
├── assets/               # Recursos CSS y JavaScript
│   ├── css/              # Hojas de estilo
│   │   ├── style.css     # Estilos principales
│   │   └── print-styles.css # Estilos específicos para impresión
│   └── js/               # Scripts JavaScript
├── img/                  # Imágenes y logos
└── includes/             # Funciones, configuración y plantillas
```

## Uso

1. **Usuarios**: Acceder a la página principal para crear tickets o a la sección de seguimiento para consultar el estado de un ticket existente.
2. **Administradores**: Iniciar sesión en el panel de administración para gestionar tickets, actualizar estados y generar informes.

### Funcionalidades de Impresión y PDF

El sistema ofrece varias opciones para la generación de reportes e impresión:

1. **Impresión Directa**: Desde el panel de administración, para tickets resueltos o cerrados, puede hacer clic en "Imprimir Directo" para acceder a una versión optimizada para impresión que se abrirá automáticamente en el diálogo de impresión.

2. **Generación de PDF**: En la vista de informe detallado, puede hacer clic en "Generar PDF" para acceder a una interfaz que le guiará en el proceso de guardar el informe como PDF utilizando la función nativa del navegador.

3. **Exportación de Texto**: En la vista detallada del informe, también puede copiar el texto del informe para pegarlo en otro documento o enviarlo por correo electrónico.

## Seguridad

- El sistema utiliza hash de contraseñas para proteger las credenciales de los administradores
- Limpieza de datos de entrada para prevenir inyecciones SQL y XSS
- Sesiones seguras para el control de acceso

## Contacto

Para más información o soporte, contactar al departamento de TI.

---
© 2025 Municipalidad Provincial de Canchis - Desarrollado con fines demostrativos
