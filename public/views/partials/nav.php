<?php $__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); ?>
<nav class="navbar navbar-expand bg-dark navbar-dark mb-4">
  <div class="container-fluid">
    <span class="navbar-brand">Turnos</span>
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link<?= $__path === '/planificacion' ? ' active' : '' ?>" href="/planificacion">Planificación</a></li>
      <li class="nav-item"><a class="nav-link<?= $__path === '/turnos' ? ' active' : '' ?>" href="/turnos">Turnos</a></li>
      <?php if (($__user['rol'] ?? '') === 'administrador'): ?>
      <li class="nav-item"><a class="nav-link<?= $__path === '/empleados' ? ' active' : '' ?>" href="/empleados">Empleados</a></li>
      <?php endif; ?>
    </ul>
    <span class="navbar-text text-light me-3"><?= htmlspecialchars(($__user['nombre'] ?? '') . ' (' . ($__user['rol'] ?? '') . ')') ?></span>
    <button id="logout-btn" class="btn btn-outline-light btn-sm">Salir</button>
  </div>
</nav>
