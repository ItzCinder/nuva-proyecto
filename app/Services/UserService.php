<?php

namespace App\Services;

use GuzzleHttp\Client;
use app\Models\User;

class UserService{
    public function findOrCreateFromGoogle(array $userInfo): User{
        $googleId = $userInfo['google_id'] ?? null;
        $email = $userInfo['email'] ?? null;

        if (!is_string($googleId) || !is_string($email)) {
            throw new \InvalidArgumentException(
                'Google no devolvió un ID o email válido'
            );
        }

        $user = User::findByGoogleId($googleId);

        if ($user === null) {
            $user = User::findByEmail($email);
        }

        /*
        Si no hay usuario regitrado, se crea.
        */
        if ($user === null){
            $user = new User(
                email: $email,
                name: $userInfo['name'] ?? '',
                googleId: $googleId,
                pictureUrl: null
            );
            /*
            Si hay usuario registrado se obtiene la información.
            */
        } else {
            $user
                ->setEmail($email)
                ->setName($userInfo['name'] ?? '')
                ->setGoogleId($googleId);
        }

        /*
        Primero guardamos para obtener el ID del usuario.
        */
        if (!$user->save()) {
            throw new \RuntimeException('No se pudo guardar el usuario');
        }

        /*
        Recibir foto de Google
        */
        $remotePicture = $userInfo['picture_url'] ?? null;

        /*
         Descargar imagen si existe
        */
        if (is_string($remotePicture) && $remotePicture !== ''){
            $localPicture = $this->downloadPicture(
                $remotePicture,
                (int) $user->getId()
            );

            /* 
            La guarda en ruta local /public/assets/uploads/users/
            */
            if ($localPicture !== null) {
                $user->setPictureUrl($localPicture);

                if(!$user->save()){
                    throw new \RuntimeException(
                        'No se pudo guardar la ruta de la imagen'
                    );
                }
            }
        }

        return $user;
    }

    private function downloadPicture(string $url, int $userId): ?string{
        // GUZZLE
        $client = new Client([
            'timeout' => 10,
            'allow_redirects' => true,
        ]);


        try{
            /*
            Comprobar repuesta
            */
            $response = $client->get($url);

            if($response->getStatusCode() !== 200){
                return null;
            }

            /*
            Leer y valida el contenido
            */
            $imageData = (string) $response->getBody();
            $imageInfo = getImagesizefromstring($imageData);

            if ($imageInfo === false){
                return null;
            }

            /*
            Escoger formato
            */
            $extension = match ($imageInfo['mime']) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => null,
            };
            if ($extension === null) {
                return null;
            }

            
            $directory = dirname(__DIR__, 2)
                . '/public/assets/uploads/users';

            /*
            Si no existe, la crea
            */
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            /*
            Guardar archivo
            */
            $filename = $userId . '.' . $extension;
            $filePath = $directory . '/' . $filename;

            if (file_put_contents($filePath, $imageData) === false) {
                return null;
            }

            return '/public/assets/uploads/users/' . $filename;
        } catch (\Throwable) {
            return null;
        }
    }
}