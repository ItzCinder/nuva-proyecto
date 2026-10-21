# Demostración completa de Nuva

Ejecuta desde la raíz del proyecto, con PHP y la base configurados:

```bash
php demostracion/cargar.php --owner-id=ID_DE_TU_CUENTA
```

Reemplaza el ID por el que muestra tu cuenta después de iniciar sesión con Google. Esa cuenta será dueña de los nuevos entornos. Sin `--owner-id`, se utiliza una persona ficticia: los ejemplos siguen siendo públicos, pero para administrarlos utiliza la variante con tu cuenta real. Las cuentas de ejemplo no son accesos de Google ni permiten saltarse la autenticación.

En Docker:

```bash
docker compose --env-file .env.docker exec web php demostracion/cargar.php --owner-id=ID_DE_TU_CUENTA
```

## Qué incluye

- 64 personas ficticias y los roles existentes de dueño, administrador, organizador y participante.
- 6 entornos inspirados en entidades deportivas, con sus escudos e invitaciones.
- 54 torneos: 18 ligas, 18 suizos y 18 de eliminación directa. Cada entorno tiene nueve: los tres formatos en estado planificado, en curso y finalizado.
- Equipos de cinco integrantes para deportes de equipo y participación individual en ajedrez.
- Inscripciones, enfrentamientos, jornadas, resultados variados, tablas y llaves. Los torneos en curso tienen encuentros pendientes con fecha; los planificados todavía no tienen cruces generados.
- Estadísticas de todos los inscritos: circuitos técnicos, pases, tiros libres, precisión, reacción y ejercicios de estrategia, según la disciplina. Hay valores enteros, porcentajes, tiempos y resúmenes por suma, promedio o mejor marca.
- Controles físicos en los torneos deportivos: solo recorrido en algunos, recorrido y fuerza en otros y las tres plantillas en las copas. Incluyen varios turnos, participantes de masas diferentes, medidas, instrumentos, procedimiento, condiciones, márgenes opcionales y algunos controles con sensor.
- Asistencia de los equipos a la preparación. En torneos desarrollados también se registran los totales por jornada a partir de sus marcadores: los puntos, goles o mapas coinciden con los encuentros terminados.
- Actas con un relato del desarrollo, controles de ingreso para los planificados y sesiones de seguimiento para los torneos en curso o terminados. Todos siguen identificados como **Ejemplo**, aunque sus fichas cuentan una historia y explican cómo se obtuvo cada valor.

Las fechas se calculan desde el día de carga: los finalizados quedan en el pasado, los que están en curso tienen encuentros jugados y próximos turnos, y los planificados comienzan la semana siguiente. Sus controles de ingreso se registran antes del comienzo, durante la preparación. Un planificado no tiene resultados futuros inventados ni cruces generados de antemano. Los resultados y actividades son ficticios: no son campeonatos oficiales de las entidades representadas. Los datos permiten explorar la plataforma sin introducir mediciones personales reales.

`cargar.php` abre una transacción y coordina los archivos de la carpeta:

- `DemoData.php`: crea las cuentas, los entornos, las inscripciones y el desarrollo inicial.
- `DemoEnrichment.php`: reconoce y completa los ejemplos existentes, sus planteles, calendarios y actas.
- `DemoStatistics.php`: carga las sesiones, las mediciones y sus correcciones usando los modelos de estadísticas.
- `DemoStories.php`: contiene los relatos y los rangos de valores de cada actividad. La variación es repetible: no vuelve a sortear los números al ejecutar otra vez.

`entidades.php` contiene nombres, disciplinas e imágenes. Además, añade fotos a los seis entornos antiguos conocidos de demostración si todavía no tenían una. No agrega tablas o columnas y no borra información existente. Repetir el comando para la misma cuenta no duplica el conjunto ni las estadísticas. La ampliación se identifica en el diario existente mediante `stat_demo / complete-v2:ID_DEL_ENTORNO`; el diario sigue siendo necesario para conservar configuraciones e historiales. La identificación de las cuentas ficticias de la carga inicial se conserva en `users.google_id`. Cambiar de cuenta crea otro conjunto independiente. Los archivos de `database/demo/cargar_*.php` son accesos de compatibilidad al mismo cargador.

Realiza un respaldo antes de cargar en una base con información importante. No ejecutes `nuvabd.sql` sobre una base existente: ese archivo prepara una instalación vacía. Los scripts de demostración solo se ejecutan por consola y no aceptan solicitudes web.

## Completar los ejemplos que ya estaban cargados

Usa el mismo comando y la misma cuenta dueña. También reconoce los seis entornos de las entregas anteriores: Club del Sur, Club de Estrategia, Arena Digital, Competiciones en acción, Comunidad del Río y Centro de Estrategia. En una base con esos ejemplos y el conjunto completo, se amplían **12 entornos y 75 torneos**.

Los nombres conocidos y las cuentas ficticias se comprueban antes de modificar un entorno. No se completan otros entornos personales ni torneos nuevos creados por el usuario. Las mediciones reales anteriores conservan sus valores y selección de visibilidad. Las fichas antiguas marcadas como ejemplos se completan mediante correcciones con motivo: sus ediciones anteriores siguen en el historial.

Las definiciones nuevas explican la actividad y la unidad: por ejemplo, diez tiros libres por sesión o cien intentos de puntería. Los torneos sin pruebas físicas abren directamente sus estadísticas manuales, para que la información cargada se vea al entrar. Los totales del marcador del equipo se calculan desde los resultados; las prácticas complementarias varían dentro de rangos razonables. Esos datos de práctica no cambian los ganadores ni la clasificación.

Las evidencias por enlace son opcionales y quedan vacías: no se inventan videos ni documentos que no existen. Las menciones a sensores, entrenadores, actas y lugares pertenecen al relato ficticio de la demostración.

## Imágenes y créditos

Las imágenes están en `public/assets/demo/entities`. Se incluyen como referencias visuales para una demostración educativa independiente. Sus marcas y derechos pertenecen a sus titulares; Nuva y Kervox no representan a esas organizaciones ni afirman tener su patrocinio. Verifica sus condiciones antes de redistribuir la demostración en un sitio comercial.

| Entidad | Fuente oficial | Archivo original |
| --- | --- | --- |
| Peñarol | [Símbolos oficiales](https://www.xn--pearol-xwa.org/Downloads/Simbolos-oficiales-uc120) | [SVG](https://www.xn--pearol-xwa.org/plantillas/images/logo_1.svg) |
| Nacional | [Himno y escudo](https://nacional.uy/club/historia/himno-escudo) | [WebP](https://cndef.nyc3.cdn.digitaloceanspaces.com/files/page/Escudo-CNF.webp) |
| Boston Celtics | [Sitio del equipo](https://www.nba.com/celtics) | [SVG de NBA](https://cdn.nba.com/logos/nba/1610612738/primary/L/logo.svg) |
| FURIA | [Acerca de FURIA](https://us.furia.gg/about) | [SVG](https://us.furia.gg/images/brand/logo-vertical-black.svg) |
| G2 Esports | [Sitio oficial](https://g2esports.com/) | [PNG](https://g2esports.com/cdn/shop/files/g2-logo-clear.png?v=1675157476&width=240) |
| FIDE | [Sitio oficial](https://www.fide.com/) | [PNG](https://www.fide.com/img/logo1.png) |

Consulta de fuentes: 16 de octubre de 2026. En Peñarol se extrajo el escudo del logotipo horizontal sin modificar su dibujo. Las copias locales evitan depender de enlaces externos para mostrar las tarjetas.
