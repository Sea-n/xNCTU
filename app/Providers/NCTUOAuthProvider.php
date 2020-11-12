<?php

namespace App\Providers;

use GuzzleHttp\Exception\GuzzleException;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class NCTUOAuthProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /*
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'profile',
        'name',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://id.nctu.edu.tw/o/authorize/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://id.nctu.edu.tw/o/token/';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param $token
     * @return mixed
     * @throws GuzzleException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://id.nctu.edu.tw/api/profile', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'    => $user['username'],
            'email' => $user['email'],
        ]);
    }
}
