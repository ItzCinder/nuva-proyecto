<?php

namespace App\Modules\Estadisticas\Services;

class Physics
{
    public const VERSION = 1;
    public const TEMPLATES = [
        'motion' => [
            'name' => 'Recorrido y tiempo',
            'purpose' => 'Saber a qué ritmo hizo un recorrido. Solo necesitas metros y segundos.',
            'example' => 'Si recorre 10 metros en 2 segundos, su rapidez media es 5 m/s: un promedio de 5 metros por segundo.',
            'meaning' => 'Sirve para comparar intentos de la misma distancia y en condiciones parecidas. No dice a qué rapidez iba en cada momento.',
            'conditions' => 'Mide la distancia y el tiempo entre las mismas dos marcas. Para estimar también el aumento de rapidez, debe salir quieto, ir en línea recta y ganar rapidez de forma aproximadamente uniforme.'
        ],
        'dynamics' => [
            'name' => 'Fuerza durante el movimiento',
            'purpose' => 'Estimar la fuerza total que hizo que la persona u objeto cambiara su rapidez.',
            'example' => 'En un ejemplo ideal, una persona de 70 kg sale quieta y recorre 10 metros en 2 segundos, ganando rapidez de forma uniforme. La fuerza estimada es 350 N.',
            'meaning' => 'Ayuda a comparar movimientos hechos de la misma manera. Una persona de más kilos necesita más fuerza para ganar rapidez al mismo ritmo. No es una medida de fuerza muscular.',
            'conditions' => 'Necesitas los kilos de la balanza y datos de cómo cambió el movimiento. Nuva puede estimarlo con distancia y tiempo desde una salida quieta, si el recorrido es recto y gana rapidez de forma aproximadamente uniforme.'
        ],
        'energy' => [
            'name' => 'Energía del movimiento',
            'purpose' => 'Conocer la energía que tiene la persona u objeto por estar moviéndose.',
            'example' => 'Una persona de 70 kg que se mueve a 10 m/s tiene 3500 J de energía de movimiento. J significa julios, la unidad de energía.',
            'meaning' => 'Sirve para comparar cuánta energía tiene un movimiento al principio y al final. Depende de los kilos y de la rapidez. No indica calorías gastadas ni cansancio.',
            'conditions' => 'Necesitas los kilos y la rapidez en el momento que quieres estudiar. Nuva puede estimar la rapidez final con un recorrido desde una salida quieta, recto y con aumento de rapidez aproximadamente uniforme.'
        ],
    ];
    public const METRICS = [
        'speed' => ['Rapidez media', 'm/s', 'motion'],
        'acceleration' => ['Aceleración', 'm/s²', 'motion'],

        'final_speed' => ['Rapidez final', 'm/s', 'motion'],
        'force' => ['Fuerza neta horizontal', 'N', 'dynamics'],

        'kinetic' => ['Energía cinética final', 'J', 'energy'],
        'delta_energy' => ['Cambio de energía cinética', 'J', 'energy'],

        'work' => ['Trabajo neto', 'J', 'energy'],
    ];
    public const RESULT_HELP = [
        'speed' => 'Metros que recorrió por segundo, en promedio.',
        'acceleration' => 'Cuánto cambia su rapidez cada segundo. No es la rapidez misma.',
        'final_speed' => 'Rapidez estimada al llegar a la marca, no el promedio del recorrido.',
        'force' => 'Fuerza total asociada al cambio de movimiento. No mide fuerza muscular.',
        'kinetic' => 'Energía que tiene al final por estar en movimiento. No son calorías gastadas.',
        'delta_energy' => 'Cuánta energía de movimiento ganó o perdió respecto al inicio.',
        'work' => 'Energía que aporta la fuerza si es positivo, o que quita si es negativo.',
    ];

    public static function number(array $input, string $key, bool $required = false, ?float $min = null): ?float
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new \InvalidArgumentException('Revisa el dato ' . self::label($key) . '.');
        }

        $value = str_replace(',', '.', trim((string)$value));
        if ($value === '') {
            if ($required) {
                throw new \InvalidArgumentException('Falta un dato necesario: ' . self::label($key) . '.');
            }
            return null;
        }

        if (!preg_match('/^-?\d+(?:\.\d{1,8})?$/D', $value) || !is_finite((float)$value) || abs((float)$value) > 1000000) {
            throw new \InvalidArgumentException('Revisa ' . self::label($key) . ': usa un número con hasta ocho decimales y magnitud máxima de un millón.');
        }

        $number = (float)$value;
        if ($min !== null && $number < $min) {
            throw new \InvalidArgumentException(self::label($key) . ' debe ser mayor o igual a ' . $min . '.');
        }

        return $number;
    }
    private static function label(string $key): string
    {
        if (str_starts_with($key, 'u_')) {
            return 'margen de duda de ' . self::label(substr($key, 2));
        }

        return [
            'distance' => 'distancia del recorrido',
            'time' => 'tiempo del recorrido',
            'segment_distance' => 'distancia del tramo inicial',
            'segment_time' => 'tiempo del tramo inicial',
            'mass' => 'masa',
            'acceleration' => 'aceleración medida',
            'speed' => 'rapidez final medida',
            'initial_speed' => 'rapidez inicial',
            'force' => 'fuerza neta medida',
            'work_distance' => 'desplazamiento del trabajo'
        ][$key] ?? str_replace('_', ' ', $key);
    }
    private static function positive(array $input, string $key): float
    {
        $number = self::number($input, $key, true, 0);
        if ($number <= 0) {
            throw new \InvalidArgumentException(self::label($key) . ' debe ser mayor que cero.');
        }

        return $number;
    }
    public static function calculate(array $measurements, array $selectedTemplates): array
    {
        $inputs = [];
        $results = [];
        $notes = [];
        $independent = ($measurements['independent_inputs'] ?? '') === '1';
        $acceleration = null;
        $accelerationUncertainty = null;
        $finalSpeed = null;
        $finalSpeedUncertainty = null;
        $force = null;
        $uForce = null;
        $mass = null;
        $massUncertainty = null;
        $estimateAcceleration = in_array('motion', $selectedTemplates, true) && ($measurements['mrua'] ?? '') === '1';
        $calculateEnergy = in_array('energy', $selectedTemplates, true);
        if (in_array('motion', $selectedTemplates, true)) {
            $distance = self::readPositive($measurements, $inputs, 'distance');
            $time = self::readPositive($measurements, $inputs, 'time');
            $distanceUncertainty = self::readUncertainty($measurements, $inputs, 'distance');
            $timeUncertainty = self::readUncertainty($measurements, $inputs, 'time');
            $value = $distance / $time;
            $uncertainty = $independent && $distanceUncertainty !== null && $timeUncertainty !== null ? abs($value) * sqrt(($distanceUncertainty / $distance) ** 2 + ($timeUncertainty / $time) ** 2) : null;
            self::storeResult($results, 'speed', $value, $uncertainty);
        }

        $accelerationSource = ($measurements['acceleration_source'] ?? 'model') === 'measured' ? 'measured' : 'model';
        $speedSource = ($measurements['speed_source'] ?? 'model') === 'measured' ? 'measured' : 'model';
        $estimateFromDistanceAndTime = $estimateAcceleration || (in_array('dynamics', $selectedTemplates, true) && $accelerationSource === 'model') || ($calculateEnergy && $speedSource === 'model');
        if ($estimateFromDistanceAndTime) {
            if (($measurements['from_rest'] ?? '') !== '1' || ($measurements['constant_acceleration'] ?? '') !== '1') {
                throw new \InvalidArgumentException('Para estimar con distancia y tiempo, confirma que empezó quieto, fue en línea recta y ganó rapidez de forma aproximadamente uniforme.');
            }

            $segmentDistance = self::readPositive($measurements, $inputs, 'segment_distance');
            $segmentTime = self::readPositive($measurements, $inputs, 'segment_time');
            $segmentDistanceUncertainty = self::readUncertainty($measurements, $inputs, 'segment_distance');
            $segmentTimeUncertainty = self::readUncertainty($measurements, $inputs, 'segment_time');
            if (isset($distance, $time) && ($segmentDistance > $distance || $segmentTime > $time)) {
                throw new \InvalidArgumentException('El tramo inicial no puede superar la distancia ni el tiempo del recorrido completo.');
            }

            $acceleration = 2 * $segmentDistance / ($segmentTime * $segmentTime);
            $finalSpeed = 2 * $segmentDistance / $segmentTime;
            if ($independent && $segmentDistanceUncertainty !== null && $segmentTimeUncertainty !== null) {
                $accelerationUncertainty = abs($acceleration) * sqrt(($segmentDistanceUncertainty / $segmentDistance) ** 2 + (2 * $segmentTimeUncertainty / $segmentTime) ** 2);
                $finalSpeedUncertainty = abs($finalSpeed) * sqrt(($segmentDistanceUncertainty / $segmentDistance) ** 2 + ($segmentTimeUncertainty / $segmentTime) ** 2);
            }

            if (in_array('motion', $selectedTemplates, true)) {
                self::storeResult($results, 'acceleration', $acceleration, $accelerationUncertainty, true);
                self::storeResult($results, 'final_speed', $finalSpeed, $finalSpeedUncertainty, true);
            }

            $notes[] = 'Estimación MRUA: trayectoria recta, salida desde el reposo y aceleración constante. Conviene contrastarla con marcas intermedias.';
        }

        if (in_array('dynamics', $selectedTemplates, true) || $calculateEnergy) {
            $mass = self::readPositive($measurements, $inputs, 'mass');
            $massUncertainty = self::readUncertainty($measurements, $inputs, 'mass');
        }

        if (in_array('dynamics', $selectedTemplates, true)) {
            if ($accelerationSource === 'measured') {
                $acceleration = self::readInput($measurements, $inputs, 'acceleration', true);
                $accelerationUncertainty = self::readUncertainty($measurements, $inputs, 'acceleration');
            }

            $force = $mass * $acceleration;
            if ($independent && $massUncertainty !== null && $accelerationUncertainty !== null) {
                $uForce = sqrt(($acceleration * $massUncertainty) ** 2 + ($mass * $accelerationUncertainty) ** 2);
            }

            self::storeResult($results, 'force', $force, $uForce, true);
        }

        if ($calculateEnergy) {
            if ($speedSource === 'measured') {
                $finalSpeed = self::readInput($measurements, $inputs, 'speed', true, 0);
                $finalSpeedUncertainty = self::readUncertainty($measurements, $inputs, 'speed');
                $initialSpeed = self::readInput($measurements, $inputs, 'initial_speed', true, 0);
                $initialSpeedUncertainty = self::readUncertainty($measurements, $inputs, 'initial_speed');
            } else {
                $initialSpeed = 0.0;
                $initialSpeedUncertainty = 0.0;
            }

            $kineticEnergy = .5 * $mass * $finalSpeed * $finalSpeed;
            $energyChange = .5 * $mass * ($finalSpeed * $finalSpeed - $initialSpeed * $initialSpeed);
            $kineticUncertainty = null;
            $energyChangeUncertainty = null;
            if ($independent && $massUncertainty !== null && $finalSpeedUncertainty !== null) {
                $kineticUncertainty = sqrt((.5 * $finalSpeed * $finalSpeed * $massUncertainty) ** 2 + ($mass * $finalSpeed * $finalSpeedUncertainty) ** 2);
                if ($initialSpeedUncertainty !== null) {
                    $energyChangeUncertainty = sqrt((.5 * ($finalSpeed * $finalSpeed - $initialSpeed * $initialSpeed) * $massUncertainty) ** 2 + ($mass * $finalSpeed * $finalSpeedUncertainty) ** 2 + ($mass * $initialSpeed * $initialSpeedUncertainty) ** 2);
                }
            }

            self::storeResult($results, 'kinetic', $kineticEnergy, $kineticUncertainty, $speedSource === 'model');
            self::storeResult($results, 'delta_energy', $energyChange, $energyChangeUncertainty, $speedSource === 'model');
            if (($measurements['work_mode'] ?? '') === 'model') {
                if (!$estimateFromDistanceAndTime || $speedSource !== 'model' || (in_array('dynamics', $selectedTemplates, true) && $accelerationSource !== 'model')) {
                    throw new \InvalidArgumentException('Para estimar el trabajo, usa el mismo recorrido desde la salida quieta. Si tienes medidas de un instrumento, elige la opción de fuerza medida.');
                }

                $work = 2 * $mass * $segmentDistance * $segmentDistance / ($segmentTime * $segmentTime);
                $workUncertainty = null;
                if ($independent && $massUncertainty !== null && $segmentDistanceUncertainty !== null && $segmentTimeUncertainty !== null) {
                    $workUncertainty = abs($work) * sqrt(($massUncertainty / $mass) ** 2 + (2 * $segmentDistanceUncertainty / $segmentDistance) ** 2 + (2 * $segmentTimeUncertainty / $segmentTime) ** 2);
                }

                self::storeResult($results, 'work', $work, $workUncertainty, true);
                $notes[] = 'Trabajo y cambio de energía se calculan desde los mismos datos: su coincidencia comprueba las ecuaciones, no valida por sí sola el modelo con un experimento independiente.';
            } elseif (($measurements['work_mode'] ?? '') === 'measured') {
                if (($measurements['constant_force'] ?? '') !== '1') {
                    throw new \InvalidArgumentException('Para calcular trabajo confirma que la fuerza neta puede considerarse constante durante el desplazamiento.');
                }

                $measuredForce = self::readInput($measurements, $inputs, 'force', true);
                $workDistance = self::readInput($measurements, $inputs, 'work_distance', true, 0);
                $angle = self::readInput($measurements, $inputs, 'angle', true);
                if (abs($angle) > 360) {
                    throw new \InvalidArgumentException('El ángulo debe estar entre −360° y 360°.');
                }

                $forceUncertainty = self::readUncertainty($measurements, $inputs, 'force');
                $workDistanceUncertainty = self::readUncertainty($measurements, $inputs, 'work_distance');
                $angleUncertainty = self::readUncertainty($measurements, $inputs, 'angle');
                $angleInRadians = deg2rad($angle);
                $workUncertainty = null;
                if ($independent && $forceUncertainty !== null && $workDistanceUncertainty !== null && $angleUncertainty !== null) {
                    $workUncertainty = sqrt(($workDistance * cos($angleInRadians) * $forceUncertainty) ** 2 + ($measuredForce * cos($angleInRadians) * $workDistanceUncertainty) ** 2 + ($measuredForce * $workDistance * sin($angleInRadians) * deg2rad($angleUncertainty)) ** 2);
                }

                self::storeResult($results, 'work', $measuredForce * $workDistance * cos($angleInRadians), $workUncertainty, true);
            }

            $notes[] = 'La energía describe traslación del cuerpo como masa puntual; no mide consumo metabólico ni el movimiento de cada parte del cuerpo.';
        }

        foreach (['mrua', 'from_rest', 'constant_acceleration', 'independent_inputs', 'constant_force'] as $key) {
            $inputs[$key] = ($measurements[$key] ?? '') === '1';
        }

        $inputs += [
            'acceleration_source' => $accelerationSource,
            'speed_source' => $speedSource,
            'work_mode' => in_array($measurements['work_mode'] ?? '', ['model', 'measured'], true) ? $measurements['work_mode'] : 'none'
        ];
        if (!$independent) {
            $notes[] = 'No se propagó incertidumbre: no se declararon entradas independientes. Esto no significa incertidumbre cero.';
        } else {
            foreach ($results as $result) {
                if ($result['uncertainty'] === null) {
                    $notes[] = 'Algunos márgenes no se calcularon porque faltan incertidumbres de entrada. No equivalen a cero.';
                    break;
                }
            }
        }

        return [
            'version' => self::VERSION,
            'inputs' => $inputs,
            'outputs' => $results,
            'notes' => $notes
        ];
    }

    private static function readInput(array $measurements, array &$inputs, string $key, bool $required = false, ?float $minimum = null): ?float
    {
        $value = self::number($measurements, $key, $required, $minimum);
        $inputs[$key] = $value;
        return $value;
    }

    private static function readPositive(array $measurements, array &$inputs, string $key): float
    {
        $value = self::positive($measurements, $key);
        $inputs[$key] = $value;
        return $value;
    }

    private static function readUncertainty(array $measurements, array &$inputs, string $key): ?float
    {
        return self::readInput($measurements, $inputs, 'u_' . $key, false, 0);
    }

    private static function storeResult(array &$results, string $key, float $value, ?float $uncertainty = null, bool $estimated = false): void
    {
        $validValue = is_finite($value) && abs($value) < 1e12;
        $validUncertainty = $uncertainty === null || (is_finite($uncertainty) && abs($uncertainty) < 1e12);
        if (!$validValue || !$validUncertainty) {
            throw new \InvalidArgumentException('El resultado está fuera del rango admitido. Revisa las unidades y mediciones.');
        }

        $results[$key] = [
            'value' => $value,
            'unit' => self::METRICS[$key][1],
            'uncertainty' => $uncertainty,
            'estimated' => $estimated,
        ];
    }
}
