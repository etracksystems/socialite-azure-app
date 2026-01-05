<?php

namespace EtrackSystems\SocialiteAzureApp;

use GuzzleHttp\RequestOptions;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class AzureAppProvider extends AbstractProvider
{
    protected $scopes = ['openid'];

    protected $graph_scopes = ['https://graph.microsoft.com/User.Read', 'openid', 'profile', 'email'];

    protected $graph_url = 'https://graph.microsoft.com/v1.0/me';

    protected $scopeSeparator = ' ';

    public static function additionalConfigKeys()
    {
        return ['tenant_id', 'endpoint_name'];
    }

    /**
     * Checks if the user is a member of any provided group.
     * @param array $groupIds
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isGroupMember(array $groupIds): bool
    {
        // if the user has not been called yet, call it so that we get the auth token to make the api calls in the
        // context of the user
        if (!isset($this->user)) {
            $this->user();
        }

        $response = $this->getHttpClient()->post($this->graph_url . '/checkMemberGroups', [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $this->user->token,
            ],
            RequestOptions::JSON => [
                'groupIds' => $groupIds,
            ]
        ]);

        $value = json_decode($response->getBody()->getContents(), true)['value'];

        return !empty($value);
    }

    protected function getBaseUrl(): string
    {
        return 'https://login.microsoftonline.com/' . $this->getConfig('tenant_id');
    }

    protected function getAuthUrl($state)
    {
        $this->scopes[] = 'api://' . $this->getConfig('client_id') . '/' . $this->getConfig('endpoint_name', 'access');

        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/oauth2/v2.0/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return $this->getBaseUrl() . '/oauth2/v2.0/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->graph_url, [
            RequestOptions::HEADERS => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
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
        $fields = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUrl,
            'scope'         => implode($this->scopeSeparator, $this->graph_scopes),
        ];

        if ($this->usesPKCE()) {
            $fields['code_verifier'] = $this->request->session()->pull('code_verifier');
        }

        return array_merge($fields, $this->parameters);
    }
}
