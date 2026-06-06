SET NAMES utf8mb4;
DROP TABLE IF EXISTS turnos;
DROP TABLE IF EXISTS empleados;
DROP TABLE IF EXISTS departamentos;

CREATE TABLE departamentos (
  nombre VARCHAR(100) PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE empleados (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100)  NOT NULL,
  apellidos     VARCHAR(150)  NOT NULL,
  correo        VARCHAR(190)  NOT NULL UNIQUE,
  contrasena    VARCHAR(255)  NOT NULL,
  departamento  VARCHAR(100)  NOT NULL,
  rol           ENUM('administrador','responsable','empleado') NOT NULL DEFAULT 'empleado',
  activo        TINYINT(1)    NOT NULL DEFAULT 1,
  CONSTRAINT fk_emp_dep FOREIGN KEY (departamento)
      REFERENCES departamentos(nombre) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE turnos (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  empleado_id     INT  NOT NULL,
  fecha           DATE NOT NULL,
  hora_inicio     TIME NOT NULL,
  hora_fin        TIME NOT NULL,
  tipo            ENUM('manana','tarde','noche','partido') NOT NULL,
  estado          ENUM('programado','confirmado','ausente','cubierto') NOT NULL DEFAULT 'programado',
  sustituto_id    INT  NULL,
  motivo_ausencia TEXT NULL,
  CONSTRAINT fk_turno_emp  FOREIGN KEY (empleado_id)  REFERENCES empleados(id) ON DELETE CASCADE,
  CONSTRAINT fk_turno_sust FOREIGN KEY (sustituto_id) REFERENCES empleados(id) ON DELETE SET NULL,
  CONSTRAINT chk_horas CHECK (hora_fin > hora_inicio),
  INDEX idx_overlap (empleado_id, fecha, hora_inicio, hora_fin),
  INDEX idx_sustituto (sustituto_id, fecha),
  INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
