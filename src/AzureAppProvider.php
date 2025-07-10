<?php

namespace EtrackSystems\SocialiteAzureApp;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class AzureAppProvider extends AbstractProvider
{
    protected $scopes = ['openid', 'profile', 'email'];

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            "https://login.microsoftonline.com/" .
            $this->getConfig('tenant_id') .
            "/oauth2/v2.0/authorize",
            $state
        );
    }

    protected function getTokenUrl()
    {
        return "https://login.microsoftonline.com/" . $this->getConfig('tenant_id') . "/oauth2/v2.0/token";
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://graph.microsoft.com/v1.0/me',
            ['headers' => ['Authorization' => 'Bearer ' . $token]]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => $user['displayName'] ?? null,
            'email'    => $user['mail'] ?? $user['userPrincipalName'] ?? null,
            'avatar'   => null,
        ]);
    }

    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'scope' => 'api://' . $this->getConfig('client_id') . '/.default',
        ]);
    }
}
