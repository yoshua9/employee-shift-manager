const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const path = require('path');

const ROOT = path.resolve(__dirname, '..', '..');
const DB_TEST = process.env.DB_TEST_NAME || 'employee_manager_test';

const DB = (() => {
  const out = execSync(
    `php -r '$c=is_file("includes/config.php")?"includes/config.php":"includes/config.example.php"; require $c; echo DB_HOST,"\\n",DB_USER,"\\n",DB_PASS;'`,
    { cwd: ROOT }
  ).toString().split('\n');
  return { host: out[0], user: out[1], pass: out[2] || '' };
})();

// Recreate the isolated test DB (schema + seed) before each test for independence.
function resetDb() {
  const pw = DB.pass ? `-p${DB.pass}` : '';
  for (const f of ['schema.sql', 'seed.sql']) {
    execSync(`mysql -h${DB.host} -u${DB.user} ${pw} ${DB_TEST} < ${path.join(ROOT, 'sql', f)}`, { stdio: 'ignore' });
  }
}

// Valid login: submit and WAIT for the role-based client redirect to land,
// so a subsequent goto() doesn't abort the in-flight navigation.
async function loginAs(page, correo, password) {
  await page.goto('/login');
  await page.fill('#correo', correo);
  await page.fill('#password', password);
  await page.click('#login-form button[type="submit"]');
  await page.waitForURL(/\/(planificacion|turnos)$/);
}

test.beforeEach(() => resetDb());

test('login admin redirige a planificación', async ({ page }) => {
  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await expect(page).toHaveURL(/\/planificacion$/);
  await expect(page.locator('#planning-body')).toBeVisible();
});

test('login empleado redirige a turnos', async ({ page }) => {
  await loginAs(page, 'emp.soporte@turnos.local', 'Empl1234');
  await expect(page).toHaveURL(/\/turnos$/);
});

test('login inválido muestra toast de error sin navegar', async ({ page }) => {
  await page.goto('/login');
  await page.fill('#correo', 'admin@turnos.local');
  await page.fill('#password', 'CLAVE_MALA');
  await page.click('#login-form button[type="submit"]');
  await expect(page.locator('.toast-body')).toContainText('Credenciales inválidas');
  await expect(page).toHaveURL(/\/login$/);
});

test('crear turno por AJAX: aviso de solapamiento en vivo y alta sin recarga', async ({ page }) => {
  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await page.goto('/turnos');

  // fecha real de un turno de emp3 (el seed es dinámico) para provocar el solape
  const seedDate = await page.evaluate(async () => {
    const r = await fetch('/api/shifts?employee_id=3');
    const j = await r.json();
    return j.data[0] ? j.data[0].fecha : null;
  });

  await page.click('#new-shift');
  await page.selectOption('#s-emp', '3');
  await page.fill('#s-date', seedDate);
  await page.fill('#s-start', '12:00');
  await page.fill('#s-end', '14:00');
  await expect(page.locator('#overlap-warn')).toContainText('Solapa');

  // rango libre (lejos del seed) -> alta correcta, fila nueva sin recargar
  await page.fill('#s-date', '2027-09-15');
  await page.fill('#s-start', '09:00');
  await page.fill('#s-end', '13:00');
  await expect(page.locator('#overlap-warn')).toHaveText('');
  await page.click('#save-shift');
  await expect(page.locator('.toast-body')).toContainText('Turno guardado');
  await expect(page.locator('#shifts-body')).toContainText('2027-09-15');
});

test('admin: editar un turno programado (PUT) y reabrir uno confirmado', async ({ page }) => {
  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await page.goto('/turnos');

  // Editar el primer turno programado: el titular queda bloqueado, cambiamos el tipo
  await page.locator('tr.estado-programado').first().locator('.act-edit-shift').click();
  await expect(page.locator('#shift-modal')).toBeVisible();
  await expect(page.locator('#s-emp')).toBeDisabled();
  await page.selectOption('#s-type', 'tarde');
  await page.click('#save-shift');
  // #toast-container es único; puede acumular varios toasts (cada uno dura 3.5s)
  await expect(page.locator('#toast-container')).toContainText('Turno actualizado');

  // Reabrir un turno confirmado -> vuelve a programado (solo admin ve el botón)
  await page.locator('tr.estado-confirmado').first().locator('.act-reopen').click();
  await expect(page.locator('#toast-container')).toContainText('Turno reabierto');
});

test('sustituto: el empleado ausente no aparece en el desplegable y el turno pasa a cubierto', async ({ page }) => {
  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await page.goto('/turnos');

  // turno seed 3: ausente, titular emp.ventas (id5)
  await page.locator('tr.estado-ausente button.act-sub').first().click();
  await expect(page.locator('#sub-modal')).toBeVisible();

  // el ausente (id5) NO debe estar entre las opciones
  const values = await page.locator('#sub-emp option').evaluateAll(opts => opts.map(o => o.value));
  expect(values).not.toContain('5');

  // elegir un sustituto válido (id4) y asignar
  await page.selectOption('#sub-emp', '4');
  await page.click('#save-sub');
  await expect(page.locator('.toast-body')).toContainText('Sustituto asignado');
  await expect(page.locator('#shifts-body')).toContainText('cubierto');
  // el listado debe mostrar quién cubre (id4 = Miguel Ferrer Lozano)
  await expect(page.locator('#shifts-body')).toContainText('Ferrer Lozano');
});

test('gestión de empleados (admin): alta por AJAX aparece en la tabla', async ({ page }) => {
  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await page.goto('/empleados');
  await page.click('#new-emp');
  await page.fill('#e-nombre', 'Nuevo');
  await page.fill('#e-apellidos', 'Browsertest');
  await page.fill('#e-correo', 'browser@turnos.local');
  await page.fill('#e-pass', 'Clave1234');
  await page.selectOption('#e-dep', 'Ventas');
  await page.selectOption('#e-rol', 'empleado');
  await page.click('#save-emp');
  await expect(page.locator('.toast-body')).toContainText('Empleado guardado');
  await expect(page.locator('#emp-body')).toContainText('browser@turnos.local');
});

test('gate de rol: un responsable no accede a /empleados', async ({ page }) => {
  await loginAs(page, 'resp.ventas@turnos.local', 'Resp1234');
  await page.goto('/empleados');
  await expect(page.locator('body')).toContainText('Acceso solo para administradores');
});

test('XSS: un nombre con <script> se pinta escapado y no se ejecuta', async ({ page }) => {
  let dialogFired = false;
  page.on('dialog', d => { dialogFired = true; d.dismiss(); });

  await loginAs(page, 'admin@turnos.local', 'Admin1234');
  await page.goto('/empleados');
  await page.click('#new-emp');
  await page.fill('#e-nombre', '<script>alert(1)</script>');
  await page.fill('#e-apellidos', 'XSS');
  await page.fill('#e-correo', 'xss@turnos.local');
  await page.fill('#e-pass', 'Clave1234');
  await page.selectOption('#e-dep', 'Ventas');
  await page.click('#save-emp');

  // el texto aparece literal (escapado en el DOM), no como nodo <script>
  await expect(page.locator('#emp-body')).toContainText('<script>alert(1)</script>');
  expect(await page.locator('#emp-body script').count()).toBe(0);
  expect(dialogFired).toBe(false);
});
