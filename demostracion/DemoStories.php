<?php

// Relatos y valores ficticios. La variación es repetible para no cambiar al recargar.
class DemoStories
{
    public static function number(string $key, int $minimum, int $maximum): int
    {
        // El resumen mezcla la clave: turnos consecutivos no producen patrones repetidos.
        $number = hexdec(substr(hash('sha256', $key), 0, 7));
        return $minimum + $number % ($maximum - $minimum + 1);
    }

    public static function profile(array $tournament): array
    {
        $discipline = mb_strtolower($tournament['discipline']);
        if (str_contains($discipline, 'básquet')) {
            return ['place' => 'gimnasio del barrio', 'session' => 'circuito de lanzamiento y desplazamiento',
                'metrics' => [
                    ['Tiros libres acertados', 'aciertos', 'integer', 'person', 'sum', 3, 10, 'En cada sesión se lanzan diez tiros libres. Cuenta cuántos entraron; se suman las sesiones registradas.'],
                    ['Precisión de lanzamiento', '%', 'decimal', 'person', 'average', 40, 95, 'Porcentaje de aciertos en veinte lanzamientos desde posiciones señaladas. Nuva muestra el promedio de las sesiones.'],
                    ['Tiempo de circuito con balón', 's', 'decimal', 'person', 'min', 15, 28, 'Tiempo para completar el mismo recorrido con conos y balón. Se conserva el menor tiempo; se incluye la repetición tras una pérdida de control.'],
                    ['Puntos anotados por el equipo', 'puntos', 'integer', 'team', 'sum', 0, 0, 'Puntos del marcador de los encuentros ya terminados. Cada registro corresponde a una jornada; no incluye encuentros pendientes.'],
                ]];
        }
        if ($tournament['discipline_category'] === 'esports') {
            return ['place' => 'sala de entrenamiento en red', 'session' => 'bloque de práctica táctica con revisión de la repetición',
                'metrics' => [
                    ['Asistencias de entrenamiento', 'asistencias', 'integer', 'person', 'sum', 2, 16, 'Asistencias observadas en un bloque de práctica de veinte minutos. Se revisa la repetición junto al capitán y se suman los bloques.'],
                    ['Precisión en práctica de puntería', '%', 'decimal', 'person', 'average', 28, 76, 'Porcentaje de impactos sobre cien intentos en la actividad de puntería previa a la partida. No es un porcentaje extraído del marcador del torneo.'],
                    ['Tiempo de reacción en ejercicio', 'ms', 'decimal', 'person', 'min', 180, 340, 'Menor tiempo registrado en una serie de diez señales visuales con el mismo equipo y configuración. Permite revisar el ejercicio, no decidir quién juega mejor.'],
                    ['Marcador acumulado del equipo', $tournament['score_label'], 'integer', 'team', 'sum', 0, 0, 'Mapas del marcador de las series terminadas. Las series pendientes todavía no aportan valores. En Rocket League o EA Sports FC representa los puntos del marcador de la serie.'],
                ]];
        }
        if ($tournament['discipline_category'] === 'mental') {
            return ['place' => 'biblioteca comunitaria', 'session' => 'taller de posiciones y análisis de decisiones',
                'metrics' => [
                    ['Posiciones resueltas', 'posiciones', 'integer', 'person', 'sum', 3, 10, 'Cada ficha tiene diez posiciones de la disciplina del torneo. Se cuenta una solución cuando la persona explica la secuencia completa al coordinador.'],
                    ['Tiempo de resolución', 's', 'decimal', 'person', 'min', 75, 300, 'Tiempo para resolver una posición del nivel acordado sin ayuda. Se conserva el menor valor entre las sesiones comparables.'],
                    ['Precisión en ejercicios', '%', 'decimal', 'person', 'average', 30, 100, 'Porcentaje de soluciones correctas en la ficha del taller. Se muestra el promedio de sesiones, separado de los puntos del torneo.'],
                ]];
        }
        if (!str_contains($discipline, 'fút') && !str_contains($discipline, 'fut')) {
            return ['place' => 'polideportivo del barrio', 'session' => 'circuito de coordinación específico de la disciplina',
                'metrics' => [
                    ['Objetivos técnicos logrados', 'objetivos', 'integer', 'person', 'sum', 8, 20, 'El entrenador prepara veinte objetivos: servicios en deportes de raqueta, recepciones en vóleibol o apoyos en escalada. Cuenta los completados sin penalización.'],
                    ['Precisión del ejercicio técnico', '%', 'decimal', 'person', 'average', 40, 100, 'Porcentaje de objetivos completados sobre los veinte del circuito. Se comparan únicamente sesiones de la misma disciplina y dificultad.'],
                    ['Tiempo de circuito técnico', 's', 'decimal', 'person', 'min', 12, 26, 'Tiempo para recorrer las mismas marcas sin penalizaciones. Es una práctica de coordinación en suelo plano, incluso cuando el torneo es de escalada.'],
                    ['Marcador acumulado del equipo', $tournament['score_label'], 'decimal', 'team', 'sum', 0, 0, 'Suma de los marcadores registrados en encuentros terminados, por jornada. La unidad es la del reglamento del torneo.'],
                ]];
        }
        return ['place' => 'polideportivo del barrio', 'session' => 'circuito técnico de pases y conducción',
            'metrics' => [
                ['Pases acertados en circuito', 'pases', 'integer', 'person', 'sum', 18, 38, 'En cada sesión se realizan cuarenta pases a objetivos marcados. Se cuentan los que llegan al objetivo sin salir del circuito.'],
                ['Precisión del ejercicio técnico', '%', 'decimal', 'person', 'average', 55, 98, 'Porcentaje de aciertos en un ejercicio de veinte objetivos adaptados a la disciplina. Para escalada y atletismo son apoyos o marcas de coordinación, no pases.'],
                ['Tiempo de circuito técnico', 's', 'decimal', 'person', 'min', 12, 26, 'Tiempo para recorrer las mismas marcas del circuito sin penalizaciones. Es una actividad complementaria en suelo plano, también cuando el torneo es de escalada.'],
                ['Marcador acumulado del equipo', $tournament['score_label'], 'integer', 'team', 'sum', 0, 0, 'Goles del marcador de los encuentros terminados, reunidos por jornada. No se inventan goles para los encuentros que aún no se jugaron.'],
            ]];
    }

    public static function workspaceDescription(string $name): string
    {
        if (str_contains($name, 'FURIA') || str_contains($name, 'G2') || str_contains($name, 'Digital')) {
            $story = 'Una comunidad reúne planteles juveniles para practicar comunicación, organizar series y revisar decisiones con sus capitanes. Cada semana hay partidas y un bloque de entrenamiento previo.';
        } elseif (str_contains($name, 'Estrategia') || str_contains($name, 'FIDE')) {
            $story = 'La biblioteca del barrio abre mesas de juego los miércoles y sábados. El grupo combina torneos con talleres de posiciones, fichas de ejercicios y revisión de partidas junto al coordinador.';
        } else {
            $story = 'El polideportivo organiza encuentros entre grupos del barrio. Sus entrenadores registran prácticas técnicas, controles de movimiento y jornadas de competición para preparar la temporada y seguir a cada participante.';
        }
        return $story . ' Escenario ficticio de Nuva, independiente de las entidades cuyos escudos se muestran.';
    }

    public static function tournamentDescription(array $tournament): string
    {
        $profile = self::profile($tournament);
        $format = match ($tournament['format_code']) {
            'league' => 'Cada rival se enfrenta según las jornadas de la liga.',
            'swiss' => 'Después de cada ronda, los cruces reúnen participantes de puntuación cercana.',
            default => 'Quien pierde sale de la llave; el ganador avanza hasta la final.',
        };
        $stage = match ($tournament['status']) {
            'Planificado' => 'Inscripciones y controles de ingreso completados; los cruces se sortearán al comenzar.',
            'En curso' => 'Ya se disputaron las primeras jornadas y la agenda incluye los próximos encuentros.',
            default => 'La comisión cerró las actas y dejó disponibles la clasificación y el historial de las pruebas.',
        };
        return mb_substr('Encuentro en ' . $profile['place'] . '. ' . $format . ' ' . $stage . ' Las estadísticas corresponden a ' . $profile['session'] . '. Relato y registros ficticios.', 0, 500);
    }

    public static function matchNotes(array $tournament, array $match): string
    {
        $prefix = ($match['round_name'] ?? 'Encuentro') . '. ';
        if ($match['status'] === 'Finalizado') {
            $prefix .= $match['score_1'] . ' — ' . $match['score_2'] . ' (' . $tournament['score_label'] . '). ';
        }
        return mb_substr($prefix . self::matchStory($tournament, $match), 0, 500);
    }

    private static function matchStory(array $tournament, array $match): string
    {
        $location = self::profile($tournament)['place'];
        if ($match['status'] !== 'Finalizado') {
            return 'Turno reservado en ' . $location . '. Los capitanes confirmarán asistencia antes del inicio; el acta queda pendiente.';
        }
        if (!empty($match['winner_id']) && $match['score_1'] === null && $match['score_2'] === null) {
            return 'El rival no se presentó a la mesa en el horario acordado. Se dejó constancia del avance por incomparecencia, sin un marcador deportivo.';
        }
        if (!empty($match['winner_id']) && $match['score_1'] == $match['score_2']) {
            return 'Con la misma marca en el turno regular, la mesa aplicó el desempate previsto mediante un intento adicional. Ambos participantes confirmaron a quién correspondía avanzar.';
        }
        if ($tournament['discipline_category'] === 'mental') {
            return $match['score_1'] == $match['score_2']
                ? 'Se acordaron tablas tras revisar la posición final con el coordinador. Ambos participantes confirmaron la planilla.'
                : 'La secuencia táctica del cierre dio ventaja a la persona ganadora; la mesa confirmó la posición final. El coordinador cotejó la planilla antes de registrar el resultado.';
        }
        if ($tournament['discipline_category'] === 'esports') {
            return 'La escuadra ganadora resolvió mejor las rotaciones del último tramo. Ambos capitanes revisaron las capturas del marcador. Se confirmó el resultado del servidor de práctica; no hubo incidencias de conexión.';
        }
        if ($match['score_1'] == $match['score_2']) {
            return 'El turno recreativo terminó igualado. El reglamento de esta jornada permite empates sin prórroga; ambos capitanes validaron el acta.';
        }
        if (($tournament['score_order'] ?? '') === 'lower') {
            return 'Se revisaron las marcas y las penalizaciones con la mesa. Avanza la menor marca válida, según el reglamento; no se registró salida anticipada.';
        }
        if (str_contains($tournament['discipline'], 'Básquet')) {
            return 'El plantel vencedor sostuvo la defensa en el último cuarto y aseguró el cierre desde la línea de tiros libres. La mesa cotejó los puntos con ambos capitanes.';
        }
        return 'El grupo ganador ajustó la coordinación en el tramo final y sostuvo la ventaja. Acta revisada por la mesa y los capitanes al terminar el encuentro en ' . $location . '. El marcador incluye todas las acciones válidas del turno.';
    }
}
