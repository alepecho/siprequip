CREATE TABLE Registro(
	id_empleados INT AUTOINCREMENT PRIMARY KEY,
  	usuario_caja VARCHAR(15),
  	fecha_de_salida DATETIME DEFAULT CURRENT_TIMESTAMP,
  	fecha_retorno DATETIME NULL,
  	estado ('Activo','Devuelto') DEFAULT 'Activo',
  	FOREIGN key (id_empleados) REFERENCES empleados_caja(id_empleados),
  	FOREIGN KEY (usuario_caja) REFERENCES empleados_caja(usuario_caja),
  	FOREIGN KEY (id_inventario) REFERENCES Inventario(id_inventario),
    FOREIGN KEY (estado) REFERENCES Inventario(estado) 
);