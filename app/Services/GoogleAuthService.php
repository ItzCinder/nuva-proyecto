<?php

namespace App\Services;

use League\OAuth2\Client\Provider\Google;

class GoogleAuthService
{
    private Google $provider;

    public function __construct()
    {
        $this->provider = new Google([
            'clientId'                => $_ENV['GOOGLE_CLIENT_ID'],
            'clientSecret'            => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirectUri'             => $_ENV['GOOGLE_REDIRECT_URI'],
        ]);
    }

    public function getAuthUrl(): string
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile'],
        ]);

        $_SESSION['oauth2state'] = $this->provider->getState();

        return $authorizationUrl;
    }

    public function getUserInfoFromCode(string $code): array
    {
        $accessToken = $this->provider->getAccessToken(
            'authorization_code',
            ['code' => $code]
        );

        $owner = $this->provider->getResourceOwner($accessToken);
        $data = $owner->toArray();

        $pictureUrl = $data['picture'] ?? null;

        if (is_string($pictureUrl)) {
            $pictureUrl = preg_replace(
                '/=s\d+(-c)?$/',
                '=s512-c',
                $pictureUrl
            );
        }

        return [
            'google_id' => $data['sub'] ?? $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'picture_url' => $pictureUrl,
        ];
    }
}