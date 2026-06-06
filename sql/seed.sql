SET NAMES utf8mb4;

INSERT INTO departamentos (nombre) VALUES ('Soporte'), ('Ventas'), ('Operaciones');

-- ids 1..7 in insertion order. Contraseñas: Admin1234 / Resp1234 / Empl1234 (bcrypt)
-- Correos, roles, departamentos y orden de inserción se mantienen (los tests dependen de ellos).
INSERT INTO empleados (nombre, apellidos, correo, contrasena, departamento, rol, activo) VALUES
('Laura',  'García Ruiz',     'admin@turnos.local',        '$2y$10$d6xrI55sq68a3EtMuqirAubBxlhdEs.YO3KNtx4nJ5/twrxgDk54a', 'Operaciones', 'administrador', 1),
('Carlos', 'Méndez Soler',    'resp.soporte@turnos.local', '$2y$10$A.AC3LMhVKMOYdqUGEBKsORPJ.GgbFfJtU/5T3fRBNutMWabmw10a', 'Soporte',     'responsable',   1),
('Ana',    'Torres Vidal',    'emp.soporte@turnos.local',  '$2y$10$/y0ACGI1HhYUs2CP3b5yE.Lr863HF17HCQ1j/httbQy/Rz/KybrsO', 'Soporte',     'empleado',      1),
('Miguel', 'Ferrer Lozano',   'resp.ventas@turnos.local',  '$2y$10$A.AC3LMhVKMOYdqUGEBKsORPJ.GgbFfJtU/5T3fRBNutMWabmw10a', 'Ventas',      'responsable',   1),
('Lucía',  'Romero Gil',      'emp.ventas@turnos.local',   '$2y$10$/y0ACGI1HhYUs2CP3b5yE.Lr863HF17HCQ1j/httbQy/Rz/KybrsO', 'Ventas',      'empleado',      1),
('Javier', 'Navarro Ortiz',   'resp.oper@turnos.local',    '$2y$10$A.AC3LMhVKMOYdqUGEBKsORPJ.GgbFfJtU/5T3fRBNutMWabmw10a', 'Operaciones', 'responsable',   1),
('Sara',   'Iglesias Marín',  'emp.oper@turnos.local',     '$2y$10$/y0ACGI1HhYUs2CP3b5yE.Lr863HF17HCQ1j/httbQy/Rz/KybrsO', 'Operaciones', 'empleado',      1);

-- turnos dinámicos: fechas relativas al LUNES de la semana actual (WEEKDAY()=0 es lunes),
-- cubriendo esta semana (ids 1..5: los 4 estados + cubierto con sustituto) y la siguiente
-- (ids 6..7). Así el seed siempre cae en "la semana actual" se ejecute cuando se ejecute.
SET @mon = DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY);

INSERT INTO turnos (empleado_id, fecha, hora_inicio, hora_fin, tipo, estado, sustituto_id, motivo_ausencia) VALUES
(3, DATE_ADD(@mon, INTERVAL 0 DAY), '09:00:00', '13:00:00', 'manana',  'programado', NULL, NULL),
(3, DATE_ADD(@mon, INTERVAL 1 DAY), '09:00:00', '17:00:00', 'partido', 'confirmado', NULL, NULL),
(5, DATE_ADD(@mon, INTERVAL 2 DAY), '14:00:00', '22:00:00', 'tarde',   'ausente',    NULL, 'Cita médica'),
(7, DATE_ADD(@mon, INTERVAL 3 DAY), '22:00:00', '23:59:00', 'noche',   'cubierto',   6,    'Baja por enfermedad'),
(4, DATE_ADD(@mon, INTERVAL 4 DAY), '08:00:00', '14:00:00', 'manana',  'programado', NULL, NULL),
-- semana siguiente
(7, DATE_ADD(@mon, INTERVAL 8 DAY), '09:00:00', '13:00:00', 'manana',  'programado', NULL, NULL),
(4, DATE_ADD(@mon, INTERVAL 9 DAY), '10:00:00', '18:00:00', 'partido', 'confirmado', NULL, NULL);
