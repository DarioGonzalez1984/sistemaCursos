// Función para mostrar el loader
function showLoader() {
    document.body.classList.add('loading');
    document.getElementById('preloader').classList.remove('fade-out');
    document.getElementById('main-content').style.opacity = '0';
}

// Función para ocultar el loader
function hideLoader() {
    document.getElementById('preloader').classList.add('fade-out');
    document.body.classList.remove('loading');
    document.getElementById('main-content').style.opacity = '1';
}

// Ocultar loader cuando la página esté completamente cargada
window.addEventListener('load', function() {
    setTimeout(hideLoader, 500); // Pequeño delay para una transición más suave
});

// Mostrar loader en cambios de página
document.addEventListener('click', function(e) {
    const target = e.target.closest('a');
    if (target && 
        !target.hasAttribute('data-bs-toggle') && 
        !target.closest('.dropdown-menu') &&
        target.getAttribute('href') && 
        !target.getAttribute('href').startsWith('#') &&
        target.getAttribute('href') !== 'javascript:void(0)') {
        showLoader();
    }
});

// Mostrar loader en envío de formularios
document.addEventListener('submit', function(e) {
    if (e.target.checkValidity()) {
        showLoader();
    }
});

// Por seguridad, ocultar el loader si ha estado visible por mucho tiempo
setInterval(function() {
    const preloader = document.getElementById('preloader');
    if (!preloader.classList.contains('fade-out')) {
        hideLoader();
    }
}, 10000);