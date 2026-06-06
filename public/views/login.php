<?php require __DIR__ . '/partials/head.php'; ?>
<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="max-width:470px;width:100%;">
    <div class="card-body p-4">
      <h1 class="h4 mb-1 text-center">Gestión de turnos</h1>
      <p class="text-muted text-center mb-4 small">Accede con tu correo corporativo</p>
      <form id="login-form">
        <div class="mb-3"><label class="form-label">Correo</label>
          <input type="email" class="form-control" id="correo" required></div>
        <div class="mb-3"><label class="form-label">Contraseña</label>
          <input type="password" class="form-control" id="password" required></div>
        <button class="btn btn-primary w-100" type="submit">Entrar</button>
      </form>
      <div class="demo-creds mt-4 p-3 bg-light rounded">
        <div class="fw-bold mb-2">Credenciales de demo</div>
        <div class="mb-1">Administrador — <code>admin@turnos.local</code> / <code>Admin1234</code></div>
        <div class="mb-1">Responsable — <code>resp.soporte@turnos.local</code> / <code>Resp1234</code></div>
        <div>Empleado — <code>emp.soporte@turnos.local</code> / <code>Empl1234</code></div>
      </div>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/login.js"></script>
</body></html>
