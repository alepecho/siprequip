CREATE TABLE empleados_caja (
    id_empleados INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_caja  VARCHAR(15),
    cedula INT,
    nombre  VARCHAR(15),
    apellido1  VARCHAR(15),
    apellido2  VARCHAR(15),
    correo_caja  VARCHAR(15),
    servicio_departamento VARCHAR(15),
    contrasena VARCHAR(15),
  	FOREIGN KEY (id_empleados) REFERENCES empleados_caja(id_empleados),
  	FOREIGN KEY (usuario_caja) REFERENCES empledos_caja(usuario_caja),
    FOREIGN KEY (cedula) REFERENCES empleados_caja(cedula),
    FOREIGN KEY (servicio_departamento) REFERENCES empleados_caja(servicio_departamento)
);