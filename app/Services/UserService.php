<?php
namespace App\Services;

use app\Models\User;

class UserService
{
    public function findOrCreateFromGoogle(array $userInfo): User
    {
        $googleId = $userInfo['google_id'] ?? null;
        $email = $userInfo['email'] ?? null;
        if (!is_string($googleId) || $googleId === '' || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Google no devolvió una cuenta válida.');
        }

        $user = User::findByGoogleId($googleId) ?? User::findByEmail($email);
        $name = is_string($userInfo['name'] ?? null) ? trim($userInfo['name']) : '';
        $picture = $this->pictureUrl($userInfo['picture_url'] ?? null);

        if ($user === null) {
            $user = new User($email, $name !== '' ? $name : $email, $googleId, $picture);
        } else {
            $user->setEmail($email)->setGoogleId($googleId);
            // El nombre elegido dentro de Nuva tiene prioridad sobre el nombre de Google.
            if ($user->getName() === '' && $name !== '') $user->setName($name);
            if ($picture !== null) $user->setPictureUrl($picture);
        }

        if (!$user->save()) throw new \RuntimeException('No se pudo guardar el usuario.');
        return $user;
    }

    private function pictureUrl($value): ?string
    {
        if (!is_string($value) || strlen($value) > 2048 || !filter_var($value, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($value);
        return ($parts && strtolower($parts['scheme'] ?? '') === 'https' && !empty($parts['host'])) ? $value : null;
    }
}
