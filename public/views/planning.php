<?php
require_once __DIR__ . '/../../includes/auth.php';
if (!current_user()) { header('Location: /login'); exit; }
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/nav.php';
?>
<div class="container-fluid">
  <h1 class="h4 mb-3">Planificación semanal</h1>
  <div class="mb-3">
    <button id="prev-week" class="btn btn-sm btn-outline-secondary">&laquo; Semana anterior</button>
    <span id="week-label" class="mx-2 fw-bold"></span>
    <button id="next-week" class="btn btn-sm btn-outline-secondary">Semana siguiente &raquo;</button>
  </div>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead><tr><th>Empleado</th><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th><th>Dom</th></tr></thead>
      <tbody id="planning-body"></tbody>
    </table>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/planning.js"></script>
</body></html>
