<?php

namespace App\Modules\Participantes\Controllers;

class ParticipantsController
{
    public function operate(array $tournament, int $userId, array $input): void
    {
        $action = $this->text($input, 'action');
        if ($action === 'team') {
            $name = $this->text($input, 'team_name');
            if ($name === '' || mb_strlen($name) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $name)) {
                throw new \InvalidArgumentException('Escribe un nombre de equipo de hasta 100 caracteres.');
            }
            (new \app\Models\Tournament())->createTeam($tournament, $userId, $name);
            return;
        }

        if ($action === 'enroll') {
            (new \app\Models\Tournament())->enroll($tournament, $userId, $this->id($input, 'member_id'), $tournament['has_team'] ? $this->id($input, 'team_id') : null);
            return;
        }

        throw new \InvalidArgumentException('Acción desconocida.');
    }
    private function text(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }
    private function id(array $input, string $key): int
    {
        $value = $this->text($input, $key);
        return (int)(filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
    }
}
