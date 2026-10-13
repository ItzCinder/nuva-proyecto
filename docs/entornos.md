# Entornos y roles: primera etapa

Se utilizaron los requerimientos funcionales, los casos de uso y los manuales de la carpeta [Entrega Final - 24 de Octubre](https://drive.google.com/drive/folders/1-wZn4uQqdWOQnuu-MzIW477YdZLwjJHH), correspondiente a la entrega posterior a la segunda:

- [Ingeniería de Software](https://docs.google.com/document/d/1DvzJvQO2NF3cmJptuPLOwpUSWukgWdGX/edit): RF-001, RF-005, RF-007, RF-011, RF-012, RF-014, RF-015 y RF-020; CU-001, CU-002 y CU-004 a CU-009.
- [Tutoría de Proyecto UTULAB](https://docs.google.com/document/d/15WYO1jgoix-S8j0f3N9nyqnSdEdRWLSE/edit): manual operacional, incorporación voluntaria, baja y administración del entorno.
- [Programación Full-Stack](https://docs.google.com/document/d/1EF4_k4OLql0vkgXZi1-Mi7XNTExFELh4/edit): estructura MVC y tecnología utilizadas por el proyecto.

Por indicación del usuario, crear entornos está disponible para cualquier cuenta autenticada. No hay pagos, planes ni suscripciones. La mención de adquirir el servicio en CU-001 es teórica.

El manual propone en un apartado que el organizador invite personas al torneo y al entorno. Para esta etapa se aplica RF-014: las invitaciones las gestionan el dueño y el administrador, requieren aceptación y solo incorporan al entorno como participante.

## Roles dentro de cada entorno

| Rol | Secciones disponibles | Acciones |
| --- | --- | --- |
| Dueño | Resumen, Torneos, Miembros y roles, Invitaciones, Información, Módulos, Auditoría | Administra el entorno; asigna y retira administradores, organizadores y participantes. |
| Administrador General | Las mismas secciones administrativas | Edita información, cambia roles de organizadores y participantes, retira miembros, genera/revoca invitaciones y configura módulos. Solo el dueño puede cambiar o retirar administradores. Puede abandonar el entorno. |
| Organizador | Resumen y Torneos | Consulta únicamente los torneos asignados. Puede abandonar el entorno. |
| Participante | Resumen y Torneos | Consulta únicamente los torneos donde tiene una inscripción. Puede abandonar el entorno. |
| Usuario sin pertenencia | Acceso a cuenta e invitación válida | Puede crear su propio entorno o aceptar una invitación; no ve la información interna de entornos ajenos. |
| Usuario público | Consulta pública existente y acceso con Google | Debe autenticarse antes de crear un entorno o aceptar una invitación. |

Los roles se guardan en `roles`; sus permisos, en `permissions` y `role_permissions`. `workspace_members` asigna un rol por persona y entorno. `workspaces.owner_id` identifica al dueño y no puede cambiarse desde los formularios de roles. Iniciar sesión no crea pertenencias ni inscripciones.

## Recorrido

1. Desde **Entornos**, seleccionar **Crear entorno**, completar el nombre obligatorio y la descripción opcional, y guardar. El usuario se convierte en dueño. También aparece en **Cuenta → Mis entornos**.
2. Abrir **Invitaciones** y generar un código de 8 caracteres, por ejemplo `K4T7X9MP`. Se puede escribir en mayúsculas o minúsculas; los enlaces anteriores de 48 caracteres siguen funcionando. Guardar el código o copiar el enlace, que se muestra una sola vez. Los códigos se guardan como hashes y no aparecen en auditoría.
3. Quien recibe la invitación inicia sesión, revisa el nombre y descripción del entorno y acepta explícitamente su incorporación como participante. No se inscribe en un torneo. Volver a aceptar no duplica la pertenencia ni modifica el rol actual.
4. Desde **Miembros y roles**, el dueño asigna administradores; el dueño y los administradores asignan organizadores y participantes.
5. **Información del entorno** permite modificar el nombre y la descripción. **Módulos** permite activar Liga, Sistema Suizo y Eliminación Directa. Una modificación no altera torneos existentes.
6. **Auditoría** muestra las últimas 100 acciones de ese entorno, su responsable, fecha y valores anteriores y nuevos. Los registros nuevos se guardan en UTC y se muestran con horario de Uruguay.
7. Un miembro puede abandonar el entorno desde **Resumen**, confirmando la baja. El dueño conserva la propiedad y no puede abandonar desde esta primera etapa. La baja retira las asignaciones como organizador, impide el acceso al entorno y conserva la cuenta y las inscripciones históricas.

## Instalación

- **Base nueva:** importar `nuvabd.sql`. Incluye los roles, permisos, módulos e invitaciones.
- **Base existente:** ejecutar `database/migrations/002_environments_and_roles.sql`. Puede repetirse sin duplicar las semillas; no elimina datos. La migración `001_stat_unit_and_data_type.sql` sigue correspondiendo al cambio anterior de estadísticas y se ejecuta una sola vez si todavía no fue aplicada.
- Las escrituras y su auditoría se guardan en una misma transacción. Los permisos se comprueban con el usuario de la sesión y su rol actual en la base de datos, sin confiar en roles enviados por el navegador.
- Los formularios usan POST, validación de servidor, sentencias preparadas, escape HTML y token CSRF.

## Alcance de esta etapa

La creación, edición, miembros, invitaciones, baja, módulos y auditoría de entornos están conectados a MariaDB. Se mantiene el enrutamiento con `switch` de `public/index.php`, modelos con PDO, un controlador y vistas PHP, sin añadir frameworks.

La sección **Torneos** consulta registros reales con acceso según el rol. La creación, configuración, asignación de organizadores e inscripciones se describen en `docs/torneos.md`. Los enfrentamientos, resultados y estadísticas se desarrollarán después. Todavía no se implementaron la eliminación del entorno ni la transferencia de propiedad.

## Verificación

Para comprobar la instalación, entra con tu cuenta y visita **Entornos**, **Torneos** y **Competiciones**. La carga opcional de datos de ejemplo permite explorar los distintos roles y formatos sin modificar los entornos anteriores.
