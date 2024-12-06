# LARAVEL DODO Paymets Package

1. add the Billable trait to your billable model definition.
```
use Laravel\Cashier\Billable;
 
class User extends Authenticatable
{
    use Billable;
}
```
2. API Keys
```
DODO_PAYMENT=YOUR_API_KEY
DODO_SANDBOX=true
DODO_WEBHOOK_SECRET=YOUR_WEBHOOK_KEY
```

3. You can create check using below code

```
 $user->newSubscription('default', 'prod_id')
            ->setBilling($billing)
            ->setReturnUrl(route('user.dashboard'))
            ->create();
```
4. Exclude dodo/* from CSRF protection bootstrap/app.php file for Laravel 11

```
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'paddle/*',
    ]);
})
```
