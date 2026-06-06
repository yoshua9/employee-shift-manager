<?php
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/repositories/EmployeeRepository.php';

class EmployeeController {
    public static function index(array $user): void {
        json_response(['data' => EmployeeRepository::list($user)], 200);
    }

    public static function show(array $user, int $id): void {
        $emp = EmployeeRepository::find($user, $id);
        if (!$emp) { json_error('El empleado no existe o no es accesible.', 404); }
        json_response(['data' => $emp], 200);
    }

    public static function create(array $user, array $body): void {
        $err = self::validateCreate($body);
        if ($err) { json_error($err, 422); }
        if (!EmployeeRepository::departmentExists($body['departamento'])) {
            json_error('El departamento indicado no existe.', 422);
        }
        if (EmployeeRepository::emailExists(trim($body['correo']))) {
            json_error('Ese correo ya está registrado.', 409);
        }
        $id = EmployeeRepository::create([
            'nombre'       => trim($body['nombre']),
            'apellidos'    => trim($body['apellidos']),
            'correo'       => trim($body['correo']),
            'contrasena'   => $body['contrasena'],
            'departamento' => $body['departamento'],
            'rol'          => $body['rol'],
            'activo'       => array_key_exists('activo', $body) ? (int)(bool)$body['activo'] : 1,
        ]);
        json_response(['data' => EmployeeRepository::find($user, $id)], 201);
    }

    public static function update(array $user, int $id, array $body): void {
        if (!EmployeeRepository::find($user, $id)) { json_error('El empleado no existe o no es accesible.', 404); }
        if (array_key_exists('correo', $body)) {
            if (!filter_var($body['correo'], FILTER_VALIDATE_EMAIL)) { json_error('El correo no tiene un formato válido.', 422); }
            if (EmployeeRepository::emailExists(trim($body['correo']), $id)) { json_error('Ese correo ya está registrado.', 409); }
        }
        if (array_key_exists('departamento', $body) && !EmployeeRepository::departmentExists($body['departamento'])) {
            json_error('El departamento indicado no existe.', 422);
        }
        if (array_key_exists('contrasena', $body) && strlen((string)$body['contrasena']) < 8) {
            json_error('La contraseña debe tener al menos 8 caracteres.', 422);
        }
        if (array_key_exists('rol', $body) && !in_array($body['rol'], ROLES, true)) {
            json_error('El rol indicado no es válido.', 422);
        }
        EmployeeRepository::update($id, self::filterUpdatable($body));
        json_response(['data' => EmployeeRepository::find($user, $id)], 200);
    }

    public static function destroy(array $user, int $id): void {
        if (!EmployeeRepository::find($user, $id)) { json_error('El empleado no existe o no es accesible.', 404); }
        EmployeeRepository::delete($id);
        json_response(['ok' => true], 200);
    }

    private static function validateCreate(array $b): ?string {
        $labels = [
            'nombre' => 'Indica el nombre.', 'apellidos' => 'Indica los apellidos.',
            'correo' => 'Indica el correo.', 'contrasena' => 'Indica la contraseña.',
            'departamento' => 'Indica el departamento.', 'rol' => 'Indica el rol.',
        ];
        foreach (['nombre', 'apellidos', 'correo', 'contrasena', 'departamento', 'rol'] as $f) {
            if (trim((string)($b[$f] ?? '')) === '') { return $labels[$f]; }
        }
        if (!filter_var($b['correo'], FILTER_VALIDATE_EMAIL)) { return 'El correo no tiene un formato válido.'; }
        if (strlen((string)$b['contrasena']) < 8) { return 'La contraseña debe tener al menos 8 caracteres.'; }
        if (!in_array($b['rol'], ROLES, true)) { return 'El rol indicado no es válido.'; }
        return null;
    }

    private static function filterUpdatable(array $b): array {
        $out = [];
        foreach (['nombre', 'apellidos', 'correo', 'departamento', 'rol', 'contrasena'] as $f) {
            if (array_key_exists($f, $b)) { $out[$f] = is_string($b[$f]) ? trim($b[$f]) : $b[$f]; }
        }
        if (array_key_exists('activo', $b)) { $out['activo'] = (int)(bool)$b['activo']; }
        return $out;
    }
}
