-- La creación del esquema la hace root durante el primer inicio.
-- La aplicación solo puede consultar y modificar datos de NuvaBD.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'nuva'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON `NuvaBD`.* TO 'nuva'@'%';
