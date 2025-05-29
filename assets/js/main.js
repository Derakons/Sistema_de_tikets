// Ejemplo de validación simple del lado del cliente (se recomienda validación más robusta en el backend)

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form'); // Asume que solo hay un formulario por página o usa un ID específico
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            // Ejemplo de validación para campos requeridos
            const requiredInputs = form.querySelectorAll('[required]');
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    // Aquí podrías agregar una clase para resaltar el campo o mostrar un mensaje
                    console.error(`El campo ${input.name} es obligatorio.`);
                    input.style.borderColor = 'red'; // Ejemplo simple
                }
            });

            if (!isValid) {
                event.preventDefault(); // Detiene el envío del formulario si no es válido
                alert('Por favor, complete todos los campos obligatorios.');
            }
        });
    }

    // Mejorar interactividad del header
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const currentPath = window.location.pathname;

    navLinks.forEach(link => {
        const linkPath = new URL(link.href).pathname;
        
        // Marcar como activo el enlace que coincide con la ruta actual
        // Se compara la parte final de la URL para mayor flexibilidad
        // Por ejemplo, /Sistema%20de%20tikets/index.php vs /index.php
        if (currentPath.endsWith(linkPath) || (currentPath + 'index.php').endsWith(linkPath) ) {
            link.classList.add('active');
            // Si es un dropdown-item, también marcar el dropdown-toggle padre
            if (link.classList.contains('dropdown-item')) {
                const parentDropdown = link.closest('.nav-item.dropdown');
                if (parentDropdown) {
                    const toggle = parentDropdown.querySelector('.nav-link.dropdown-toggle');
                    if (toggle) {
                        toggle.classList.add('active');
                    }
                }
            }
        }

        // Ejemplo de interactividad adicional al pasar el mouse (opcional)
        // Podrías añadir clases para animaciones o cambiar estilos directamente
        link.addEventListener('mouseenter', function() {
            // this.style.setProperty('background-color', 'rgba(255, 255, 255, 0.2)', 'important');
        });
        link.addEventListener('mouseleave', function() {
            // if (!this.classList.contains('active')) {
            //     this.style.removeProperty('background-color');
            // }
        });
    });

    // Inicializar tooltips de Bootstrap (si se usan en algún lugar)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap (si se usan)
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Inicializar dropdowns de Bootstrap (importante para que funcionen los menús desplegables)
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(function (dropdownToggleEl) {
      return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Funcionalidad para mostrar/ocultar dropdown de admin con hover
    const adminDropdown = document.querySelector('.nav-item.dropdown'); // Selector más específico para el dropdown de admin
    if (adminDropdown) {
        const adminDropdownToggle = adminDropdown.querySelector('.dropdown-toggle');
        let timeoutId = null;

        adminDropdown.addEventListener('mouseenter', function () {
            if (timeoutId) clearTimeout(timeoutId);
            const bsDropdown = bootstrap.Dropdown.getInstance(adminDropdownToggle) || new bootstrap.Dropdown(adminDropdownToggle);
            bsDropdown.show();
        });

        adminDropdown.addEventListener('mouseleave', function () {
            timeoutId = setTimeout(() => {
                const bsDropdown = bootstrap.Dropdown.getInstance(adminDropdownToggle);
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }, 200); // Pequeño retardo para permitir mover el cursor al menú desplegable
        });
    }

});
