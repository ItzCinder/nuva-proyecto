# Torneos de Nuva

## Preparación

1. Crea un torneo dentro de un entorno y elige disciplina, modalidad individual o por equipos y formato.
2. Configura el marcador (puntos, sets, tiempo expresado en segundos u otra unidad), si gana el valor mayor o menor, y los puntos por victoria, empate y derrota. La eliminación siempre exige ganador.
3. Inscribe miembros del entorno. Un equipo necesita al menos un integrante.
4. Abre **Encuentros → Generar enfrentamientos**. Se sortea y conserva el orden inicial. Desde ese momento se cierran las inscripciones y se fijan reglas, fechas y modalidad.

El dueño, el administrador general y el organizador asignado pueden gestionar los encuentros. El resto puede consultar según la visibilidad del torneo. La consulta pública no exige una cuenta.

## Formatos

- **Liga:** todos contra todos, una o dos vueltas. Un número impar de inscritos produce descansos sin puntos. La segunda vuelta invierte los rivales de local/visitante (solo un orden de presentación, sin reglas deportivas particulares).
- **Eliminación directa:** llave hasta la final, pases automáticos cuando la cantidad no es potencia de dos y tercer puesto opcional (mínimo cuatro inscritos). Deben cerrarse las rondas anteriores antes de registrar resultados de la siguiente.
- **Suizo:** primera ronda sorteada; siguientes según la clasificación, priorizando rivales próximos sin repetir encuentros. Con cantidad impar se asigna un pase libre a alguien de la parte baja que no haya recibido uno. Otorga puntos de victoria, sin inventar un marcador. Se necesita un mínimo de cuatro inscritos. El límite teórico sin repeticiones es N−1 rondas para N par, o N para N impar, además del máximo de 12. Si los cruces previos impiden completar una ronda sin repetir, se informa y no se guarda una ronda parcial. Es un suizo genérico; no implementa reglamentos federativos de colores, Elo o títulos.

## Resultados y clasificaciones

En cada encuentro puedes programar fecha y hora, suspender/reanudar, guardar resultado, corregirlo o anularlo con un motivo. Puedes dejar ambos marcadores vacíos para una decisión arbitral o incomparecencia, explicando el motivo. El ganador explícito puede diferir del marcador en un desempate; también requiere explicación. Esto permite registrar, por ejemplo, un empate en el marcador decidido por una instancia adicional sin confundirlo con los puntos de clasificación.

El marcador es un número positivo o negativo con hasta dos decimales; usa una unidad consistente en todo el torneo. Nuva gestiona enfrentamientos entre dos participantes o equipos. No convierte una prueba masiva de múltiples participantes, series o etapas en un formato deportivo específico.

La clasificación se recalcula a partir de resultados guardados:

- Liga: puntos, diferencia del marcador, victorias y orden inicial sorteado.
- Suizo: puntos, Buchholz (suma de puntos actuales de rivales enfrentados), victorias, diferencia y orden inicial.
- En disciplinas donde gana el menor valor, la diferencia se invierte. Un resultado sin marcador no suma diferencia. Los pases libres no suman Buchholz.

La publicación depende de la visibilidad del torneo: los resultados guardados son inmediatamente consultables. Cerrar una ronda bloquea sus resultados; no es un segundo proceso de publicación. Cuando todas las rondas están cerradas, el torneo finaliza y muestra al campeón.

## Correcciones seguras

Reabre la ronda antes de editar un resultado. En eliminación, si el nuevo ganador cambia rivales de encuentros posteriores con resultados, primero reabre y anula esos resultados desde la última ronda hacia atrás. En suizo, primero anula los resultados de las rondas posteriores y retíralas desde la última; luego corrige y genera nuevamente. Nunca se sustituyen silenciosamente resultados posteriores.

Cada operación queda en la auditoría del entorno. Los formularios llevan protección CSRF, los permisos se validan en el servidor, las operaciones usan transacciones y bloqueo por torneo. Cada encuentro tiene una revisión para rechazar formularios desactualizados.

## Calendario

El calendario marca el período del torneo y los días con encuentros programados. La agenda muestra sus horarios en Uruguay. La descarga `.ics` incluye el torneo y los encuentros con fecha; no supone una duración de partido ni requiere un servicio externo.

## Instalación y comprobación

Para una base existente, hacer una copia y aplicar `database/migrations/004_competition_engine.sql` después de las migraciones anteriores. El esquema `nuvabd.sql` incluye la ampliación para instalaciones nuevas. La migración no borra torneos ni resultados. Los encuentros heredados sin número de ronda requieren revisión antes de operar; Nuva impide duplicar su generación.

Pruebas: `/opt/lampp/bin/php tests/competition.php`. Verifican las ligas, suizos y llaves con cantidades pares/impares, equipos, permisos, resultados, correcciones y transacciones. Crean datos temporales y los revierten al terminar.

## Documentos consultados

- [Consigna del proyecto](https://drive.google.com/file/d/1vJBT1MrBpA1nxYP7KdwvkVuw9MpLVTid/view).
- [Ingeniería, entrega final: requisitos y casos de uso](https://drive.google.com/file/d/1DvzJvQO2NF3cmJptuPLOwpUSWukgWdGX/view).
- [Tutoría, entrega final: manuales](https://drive.google.com/file/d/15WYO1jgoix-S8j0f3N9nyqnSdEdRWLSE/view).

Se implementan generación, consulta, corrección auditada y clasificaciones (RF002–004, RF006, RF009–011 y RF013). Los documentos no fijan un reglamento deportivo universal; los criterios concretos utilizados arriba se muestran también en la configuración.

## Demostraciones operativas

`/opt/lampp/bin/php database/demo/cargar_competiciones.php --owner-id=ID` crea el entorno **Competiciones en acción · Demo**, ocho personas ficticias y seis torneos: un ejemplo en curso y otro finalizado de cada formato. Incluye equipos, empates, pases libres, tercer puesto, marcador de tiempo, calendarios y resultados. Se ejecuta una sola vez por cuenta y no modifica los torneos previos.

El cargador general `database/demo/cargar_datos.php` conserva ejemplos sin encuentros para practicar la preparación e inscripción. Sus nuevos datos usan cantidades compatibles con los formatos.

## Navegación de la plataforma

**Explorar** reúne competiciones públicas, entornos con competiciones publicadas y la revisión de códigos de invitación. Las fichas públicas solo muestran descripción y torneos publicados. **Cuenta** contiene todos los entornos y torneos propios o a los que perteneces, con filtros. **Ajustes** permite cambiar el nombre, sincronizar la foto y cerrar sesión.

Ejemplos adicionales: `database/demo/cargar_mas_competiciones.php --owner-id=ID` añade dos entornos y seis torneos con calendarios y resultados. Evita duplicarlos al volver a ejecutarlo.
