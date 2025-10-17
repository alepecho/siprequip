CREATE TABLE empleados_caja (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula INT,
    usuario_caja TEXT NOT NULL,
    nombre TEXT NOT NULL,
    apellido1 TEXT NOT NULL,
    apellido2 TEXT,
    correo_caja TEXT NOT NULL,
    servicio_departamento char
);