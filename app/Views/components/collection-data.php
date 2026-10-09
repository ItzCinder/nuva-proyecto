<?php
// Los mismos datos de demostración de las vistas originales, sin persistencia.
$football = ['title' => 'Liga ITS 2026', 'sport' => 'Fútbol', 'status' => 'En curso', 'format' => 'Liga', 'players' => 24, 'dates' => '12 Mar - 28 Jun', 'icon' => 'volleyball'];
$basketball = ['title' => 'Liga Bassktel 2026', 'sport' => 'Básquetbol', 'status' => 'En curso', 'format' => 'Liga', 'players' => 24, 'dates' => '12 Mar - 28 Jun', 'icon' => 'volleyball'];
$chess = ['title' => 'Torneo Ajedrez 2026', 'sport' => 'Ajedrez', 'status' => 'En curso', 'format' => 'Sistema Suizo', 'players' => 16, 'dates' => '12 Mar - 12 Sep', 'icon' => 'chess-king'];

return [
    'competitions' => [$football, $basketball, $chess, $football],
    'tournaments' => [$basketball, $chess, $football],
    'environments' => [],
];
