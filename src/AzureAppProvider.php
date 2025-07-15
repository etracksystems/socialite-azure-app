<?php

namespace EtrackSystems\SocialiteAzureApp;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class AzureAppProvider extends AbstractProvider
{
    protected $scopes = ['api://2b54c30b-2128-4e85-8061-d2170abfc8bc/.default', 'openid'];

    protected $scopeSeparator = ' ';


    public static function additionalConfigKeys()
    {
        return ['tenant_id'];
    }

    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode((string)$response->getBody(), true);

        return $this->parseAccessToken($response->getBody());
    }

    protected function getBaseUrl(): string
    {
        return 'https://login.microsoftonline.com/' . $this->getConfig('tenant_id');
    }

    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS     => ['Accept' => 'application/json'],
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
            //RequestOptions::PROXY       => $this->getConfig('proxy'),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/oauth2/v2.0/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/oauth2/v2.0/token';
    }

    protected function getUserByToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid JWT token');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return [
            'id' => $payload['oid'] ?? $payload['sub'] ?? null,
            'name' => $payload['name'] ?? null,
            'email' => $payload['upn'] ?? null,
        ];
    }

    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => null,
            'name'     => $user['displayName'] ?? null,
            'email'    => $user['mail'] ?? $user['userPrincipalName'] ?? $user['upn'] ?? null,
            'avatar'   => null,
        ]);
    }
}
