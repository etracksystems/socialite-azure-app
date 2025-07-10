# Socialite Azure App Driver

```bash
composer require etracksystems/socialite-azure-app
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to your `config/services.php`

```php
'azure' => [    
  'client_id' => env('AZURE_CLIENT_ID'),
  'client_secret' => env('AZURE_CLIENT_SECRET'),
  'redirect' => env('AZURE_REDIRECT_URI'),
  'tenant' => env('AZURE_TENANT_ID'),
],
```

### Add the provider event listener

Add the event handler to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \EtrackSystems\SocialiteAzureApp\AzureAppExtendSocialite::class.'@handle',
    ],
];
```

### Usage

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed):

```php
return Socialite::driver('azure-app')->redirect();
```

To logout of your app and Azure:
```php
public function logout(Request $request) 
{
     Auth::guard()->logout();
     $request->session()->flush();
     $azureLogoutUrl = Socialite::driver('azure-app')->getLogoutUrl(route('login'));
     return redirect($azureLogoutUrl);
}
```

### Returned User fields

- ``id``
- ``name``
- ``email``

## Advanced usage

In order to have multiple / different Active directories on Azure (i.e. multiple tenants) The same driver can be used but with a different config:

```php
/**
 * Returns a custom config for this specific Azure AD connection / directory
 * @return \SocialiteProviders\Manager\Config
 */
function getConfig(): \SocialiteProviders\Manager\Config
{
  return new \SocialiteProviders\Manager\Config(
    env('AD_CLIENT_ID', 'some-client-id'), // a different clientID for this separate Azure directory
    env('AD_CLIENT_SECRET'), // a different secret for this separate Azure directory
    url(env('AD_REDIRECT_PATH', '/azuread/callback')), // the redirect path i.e. a different callback to the other azureAD callbacks
    env('AD_TENANT_ID'), // the azure tenant id which the app is associated to
  );
}
//....//
Socialite::driver('azure-app')
    ->setConfig(getConfig())
    ->redirect();
```

This also applies to the callback for getting the user credentials that one has to remember to inject the ```->setConfig($config)```-method i.e.:
```php
$socialUser = Socialite::driver('azure-app')
    ->setConfig(getConfig())
    ->user();
```

