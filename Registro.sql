CREATE TABLE Inventario (
  Id_inventario INTEGER PRIMARY KEY AUTOINCREMENT,
  Articulo VARCHAR (15),
  Estado VARCHAR,
  Cantidad INT,
  FOREIGN KEY (id_inventario) REFERENCES Registro(id_inventario),
  FOREIGN KEY (estado) REFERENCES Registro(estado) 
  
  );