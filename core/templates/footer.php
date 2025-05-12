<?php
require_once __DIR__ . '/../config.php';
?>
    </main> <!-- Cierra el main-content abierto en header.php -->
    <footer class="site-footer bg-dark text-light pt-4 pb-2 border-top border-2" style="background: linear-gradient(90deg, var(--color3) 0%, var(--color1) 100%);">
        <div class="container text-center">
            <div class="row align-items-center mb-2">
                <div class="col-md-2 d-none d-md-block">
                    <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo Municipalidad de Canchis" style="height:48px; border-radius:8px; background:#fff; padding:4px; box-shadow:0 2px 8px rgba(0,0,0,0.10);">
                </div>
                <div class="col-md-8 col-12">
                    <p class="mb-1 fw-bold" style="font-family:'Orbitron',sans-serif; font-size:1.1em; letter-spacing:1px; color:var(--color5);">
                        &copy; <?php echo date("Y"); ?> Municipalidad Provincial de Canchis - Sistema de Tickets de TI
                    </p>
                    <p class="mb-1" style="color:var(--color5);">
                        Sistema desarrollado para la gestión y seguimiento de tickets de soporte técnico.<br>
                        <span class="fw-light">Consultas: <a href="mailto:soporte@municanchis.gob.pe" class="link-light text-decoration-underline">soporte@municanchis.gob.pe</a> | Tel: (084) 123456</span>
                    </p>+
                </div>
                <div class="col-md-2 d-none d-md-block">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="btn btn-outline-light btn-sm">Términos</a>
                </div>
            </div>
            <div class="row">
                <div class="col-12 small text-secondary">
                    Créditos: <span class="text-light">Juan Pérez, María López, Carlos Quispe</span> |
                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalTerminos" class="link-light">Términos y Condiciones</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal Términos y Condiciones -->
    <div class="modal fade" id="modalTerminos" tabindex="-1" aria-labelledby="modalTerminosLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:16px;">
          <div class="modal-header bg-dark text-light" style="background:var(--color4); border-top-left-radius:16px; border-top-right-radius:16px;">
            <h5 class="modal-title" id="modalTerminosLabel">Términos y Condiciones</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body bg-light text-dark">
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
          <div class="modal-footer bg-light" style="border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>