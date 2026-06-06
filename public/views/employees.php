<?php
require_once __DIR__ . '/../../includes/auth.php';
$__gateUser = current_user();
if (!$__gateUser) { header('Location: /login'); exit; }
if ($__gateUser['rol'] !== 'administrador') { http_response_code(403); echo 'Acceso solo para administradores'; exit; }
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/nav.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between mb-3">
    <h1 class="h4 mb-0">Empleados</h1>
    <button id="new-emp" class="btn btn-primary btn-sm">Nuevo empleado</button>
  </div>
  <table class="table table-sm table-hover">
    <thead><tr><th>Nombre</th><th>Correo</th><th>Depto</th><th>Rol</th><th>Activo</th><th></th></tr></thead>
    <tbody id="emp-body"></tbody>
  </table>
</div>

<div class="modal fade" id="emp-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Empleado</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="e-id">
    <div class="mb-2"><label class="form-label">Nombre</label><input id="e-nombre" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Apellidos</label><input id="e-apellidos" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Correo</label><input type="email" id="e-correo" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Contraseña <small class="text-muted">(vacío = sin cambio al editar)</small></label><input type="password" id="e-pass" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Departamento</label><select id="e-dep" class="form-select"></select></div>
    <div class="mb-2"><label class="form-label">Rol</label><select id="e-rol" class="form-select"><option>empleado</option><option>responsable</option><option>administrador</option></select></div>
    <div class="form-check"><input type="checkbox" id="e-activo" class="form-check-input" checked><label class="form-check-label">Activo</label></div>
  </div>
  <div class="modal-footer"><button id="save-emp" class="btn btn-primary">Guardar</button></div>
</div></div></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/employees.js"></script>
</body></html>
