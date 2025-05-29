<?php
require_once __DIR__ . '/../config.php';
?>
    </main> <!-- Cierra el main-content abierto en header.php -->
    <footer class="site-footer text-light pt-4 pb-2">
        <div class="container">
            <div class="row align-items-center mb-3">
                <div class="col-md-3 col-lg-2 text-center text-md-start footer-logo-container mb-3 mb-md-0">
                    <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo Municipalidad de Canchis" class="site-footer-logo">
                </div>
                <div class="col-md-6 col-lg-8 col-12 text-center footer-text-container mb-3 mb-md-0">
                    <div class="site-footer-copyright">
                        <p class="mb-1 fw-bold">
                            &copy; <?php echo date("Y"); ?> Municipalidad Provincial de Canchis - Sistema de Tickets de TI
                        </p>
                    </div>
                    <div class="site-footer-info">
                        <p class="mb-0">
                            Sistema desarrollado para la gestión y seguimiento de tickets de soporte técnico.<br>
                            <span class="fw-light">Consultas: <a href="mailto:soporte@municanchis.gob.pe" class="link-light">soporte@municanchis.gob.pe</a> | Tel: (084) 123456</span>
                        </p>
                    </div>
                </div>
                <div class="col-md-3 col-lg-2 text-center text-md-end footer-terms-btn-container">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="btn btn-outline-light btn-sm">Términos</a>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center site-footer-credits">
                    Créditos: <span class="text-light">Desarrollado por el Área de TI</span> |
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="link-light">Términos y Condiciones</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal Términos y Condiciones -->
    <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalTerminosLabel">Términos y Condiciones</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p>
              El uso de este sistema está destinado exclusivamente para la gestión de tickets de soporte técnico de la Municipalidad Provincial de Canchis.<br>
              Toda la información registrada será tratada conforme a las políticas de privacidad institucionales.
            </p>
            <ul>
              <li>El acceso está restringido a usuarios autorizados.</li>
              <li>No comparta sus credenciales de acceso.</li>
              <li>El mal uso del sistema puede ser sancionado.</li>
            </ul>
            <p>Para más información, contacte al área de TI.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS Unificado del Sistema de Tickets -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php if (isset($include_seguimiento_js) && $include_seguimiento_js): ?>
        <script src="<?php echo BASE_URL; ?>assets/js/seguimiento.js"></script>
    <?php endif; ?>
</body>
</html>