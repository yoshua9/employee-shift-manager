<?php
require_once __DIR__ . '/../../includes/auth.php';
$__gateUser = current_user();
if (!$__gateUser) { header('Location: /login'); exit; }
$canManage = in_array($__gateUser['rol'], ['administrador', 'responsable'], true);
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/nav.php';
?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Turnos</h1>
    <?php if ($canManage): ?><button id="new-shift" class="btn btn-primary btn-sm">Nuevo turno</button><?php endif; ?>
  </div>
  <form id="filters" class="row g-2 mb-3">
    <div class="col-auto"><input type="date" id="f-date" class="form-control form-control-sm"></div>
    <div class="col-auto">
      <select id="f-emp" class="form-select form-select-sm"><option value="">(empleado)</option></select>
    </div>
    <div class="col-auto">
      <select id="f-dep" class="form-select form-select-sm"><option value="">(departamento)</option></select>
    </div>
    <div class="col-auto">
      <select id="f-status" class="form-select form-select-sm">
        <option value="">(estado)</option><option>programado</option><option>confirmado</option>
        <option>ausente</option><option>cubierto</option>
      </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filtrar</button></div>
  </form>
  <table class="table table-sm table-hover">
    <thead><tr><th>Fecha</th><th>Horario</th><th>Empleado</th><th>Depto</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
    <tbody id="shifts-body"></tbody>
  </table>
</div>

<!-- Modal crear -->
<div class="modal fade" id="shift-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Turno</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="s-id">
    <div class="mb-2"><label class="form-label">Empleado</label><select id="s-emp" class="form-select"></select></div>
    <div class="mb-2"><label class="form-label">Fecha</label><input type="date" id="s-date" class="form-control"></div>
    <div class="row">
      <div class="col mb-2"><label class="form-label">Inicio</label><input type="time" id="s-start" class="form-control"></div>
      <div class="col mb-2"><label class="form-label">Fin</label><input type="time" id="s-end" class="form-control"></div>
    </div>
    <div class="mb-2"><label class="form-label">Tipo</label>
      <select id="s-type" class="form-select"><option>manana</option><option>tarde</option><option>noche</option><option>partido</option></select>
    </div>
    <div id="overlap-warn" class="text-danger small"></div>
  </div>
  <div class="modal-footer"><button id="save-shift" class="btn btn-primary">Guardar</button></div>
</div></div></div>

<!-- Modal sustituto -->
<div class="modal fade" id="sub-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Asignar sustituto</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" id="sub-shift-id">
    <div class="mb-2"><label class="form-label">Sustituto</label><select id="sub-emp" class="form-select"></select></div>
    <div class="mb-2"><label class="form-label">Motivo</label><input type="text" id="sub-motivo" class="form-control"></div>
    <div id="sub-warn" class="small"></div>
  </div>
  <div class="modal-footer"><button id="save-sub" class="btn btn-primary">Asignar</button></div>
</div></div></div>

<script>
  window.CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;
  window.IS_ADMIN   = <?= $__gateUser['rol'] === 'administrador' ? 'true' : 'false' ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/shifts.js"></script>
</body></html>
