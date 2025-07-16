# Socialite Azure App Driver

## Overview

The purpose of this provider is to provide Azure Oauth where you can apply *Condition Access Policies*. The default package
from ```socialiteproviders/microsoft-azure``` scopes to the Graph api in order to get the user details. This doesnt allow
Conditional Access Policies to be applied scoped solely to accessing your 3-party app. MS applies the policy
to the resource you're access, in this case Graph api, and so, affects users as a whole rather than just when accessing
your app.

This provider initially requests a scope just for the app, which allows the policies to applied scoped to just this context.
In the callback, the token is exchanged for a Graph one, which then allow us to query the Graph endpoint to get the user
details.

## Installation & Basic Usage

```bash
composer require etracksystems/socialite-azure-app
```

### Azure

Register an App with Microsoft Entra ID. Expose an API endpoint on that app, and make sure to keep the Application ID
URI as the client id, example: ```api://e1b40bb5-28da-4b55-a13b-0e121df684f3```

Add a scope to this endpoint with the name ```access```. A custom name can be used, but remember to provide the value
in your ENV setup under the key ```AZURE_ENDPOINT_NAME```. Its this scope that is initially requested in the oauth flow.


Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to your `config/services.php`

```php
'azure-app' => [    
  'client_id' => env('AZURE_CLIENT_ID'),
  'client_secret' => env('AZURE_CLIENT_SECRET'),
  'redirect' => env('AZURE_REDIRECT_URI'),
  'tenant' => env('AZURE_TENANT_ID'),
  'endpoint_name' => env('AZURE_ENDPOINT_NAME')
],
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
    env('AD_ENDPOINT_NAME') // different endpoint name if not default of 'access'
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

