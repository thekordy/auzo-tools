This package is a set of tools for Laravel 5.1, 5.2, and 5.3 to facilitate authorize management for your Laravel project or package. You can use AuzoTools to manage authorization in your project, or to provide configurable authorization option for your package users.

## Tools included:
1. [Manage Laravel authorization.](#manage-laravel-authorization)
2. [Automatic abilities generator for models.](#automatic-abilities-generator)
3. [Route authorize middleware.](#route-authorize-middleware)
4. [Controller authorize validation rule.](#controller-authorize-validation-rule)
5. [Model fields policy.](#model-fields-policy)

# Installation

You can install the package via composer:
``` bash
composer require kordy/auzo-tools
```

This service provider must be installed.
```php
// config/app.php
'providers' => [
    ...
    Kordy\AuzoTools\AuzoToolsServiceProvider::class,
];
```

You can publish the translations with:
```bash
php artisan vendor:publish --provider="Kordy\AuzoTools\AuzoToolsServiceProvider" --tag="translations"
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Kordy\AuzoTools\AuzoToolsServiceProvider" --tag="config"
```


# Usage

## Manage Laravel authorization

AuzoTools can help you manage abilities for any number of resources and manage restriction policies for who can access them using functions as parameters. 
You can use AuzoTools to manage authorization in your project, or to provide configurable authorization option for your package users.

Parameter functions can act as policies, so when AuzoTools is evaluating a user access to a resource/model record, it will pass these information to the function you specified as a parameter, your function will evaluate user information against the resource they want to access, then return a boolean decision to allow the user access or not.

See an example at the [test file](https://github.com/thekordy/auzo-tools/blob/master/tests/PermissionRegistrarTest.php)

### You have two ways to define policies, either callbacks or a dedicated class methods. ###

#### 1. Callbacks policies ####

Create config file as this one:

```php
// config/acl.php

return [
    'before' => [
        function($user, $ability) {
            return $user->id == 1;
        }
    ],
    'abilities' => [

        'post.update' => [
            function($user, $ability, $model) { return $user->id == 3; },
            ['or' => function ($user, $ability, $model) { return $user->id == 2; }],
        ],

        'post.destroy' => [
            function ($user, $ability, $model) { return $user->id == 2; },
        ],
    ],
    // use this to log or monitor authorization given to users
    //  you may not modify the result of the authorization check from an after callback
    'after' => [
        function ($user, $ability, $result, $arguments = null)
        {
            if ($result) {
                \Log::info("Authorization Log: User $user->name ($user->email) is granted access to ability $ability at ".date('d-m-Y H:j'));
            } else {
                \Log::info("Authorization Log: User $user->name ($user->email) is forbidden to access ability $ability at ".date('d-m-Y H:j'));
            }
        },
    ],
];
```

#### 2. Dedicated class methods ####

Create config file as this one:

```php
// config/acl.php

return [
    'before' => [
        'App\MyPolicyClass@isAdmin'
    ],
    'abilities' => [

        'post.update' => [
            'App\MyPolicyClass@postOwner',
            ['or' => 'App\MyPolicyClass@isModerator']
        ],

        'post.destroy' => [
            'App\MyPolicyClass@isModerator'
        ],
    ],
    // use this to log or monitor authorization given to users
    //  you may not modify the result of the authorization check from an after callback
    'after' => [
        'App\MyPolicyClass@monitor'
    ],
];
```

Create a policy class that holds policies methods
```php
<?php

namespace App;

class MyPolicyClass
{
/**
     * Check if user is admin
     *
     * @param $user
     * @param $ability
     * @return bool
     */
    public function isAdmin($user, $ability) {
        return $user->id == 1;
    }

    /**
     * Check if user is moderator
     *
     * @param $user
     * @param $ability
     * @return bool
     */
    public function isModerator($user, $ability) {
        return $user->role == 'moderator';
    }

    /**
     * Check if user is post owner
     *
     * @param $user
     * @param $ability
     * @return bool
     */
    public function postOwner($user, $ability, $post) {
        if ($post instanceOf Post) {
            return $user->id == $post->user_id;
        } 
        
        // If middleware passed you the user request instead of the model 
        // instance, get the resource information from the request
        if ($post === null || $post instanceof Request) {
            $postId = request()->route('id');
            $post = Post::find($postId);
            return $user->id == $post->user_id;
        }
    }

    /**
     * Run authorization monitor, see storage/logs/laravel.log
     *
     * @param $user
     * @param $ability
     */
    public function monitor($user, $ability, $result, $arguments = null)
    {
        if ($result) {
            \Log::info("Authorization Log: User $user->name ($user->email) is granted access to ability $ability at " . date('d-m-Y H:j'));
        } else {
            \Log::info("Authorization Log: User $user->name ($user->email) is forbidden to access ability $ability at " . date('d-m-Y H:j'));
        }
    }
}
```

### Finally: ###

Load Abilities to Laravel Gate at boot by runing the `\AuzoToolsPermissionRegistrar::registerPermissions($abilities_policies)` in your service provider 
```php
// app/Providers/AppServiceProvider.php

public function boot()
{
    // Load abilities to Laravel Gate
    $abilities_policies = config('acl');
    \AuzoToolsPermissionRegistrar::registerPermissions($abilities_policies);
}
```

Now you can authorize users at any point in your project or package:
```php
$user->can('post.show', $post)
// or
$user->cannot('post.update', $post)
// or for current logged in user
Gate::allows('post.update', Post::findOrFail($postId));
```

More details: https://laravel.com/docs/master/authorization#authorizing-actions-using-policies

## Automatic abilities generator
Give it a name and it automatically generates abilities names
matching  the default route resource names or custom name fix.

### Generate CRUD abilities for a model

```php
$generator = GenerateAbilities::modelAbilities('testuser');
$generated_abilities = $generator->model_crud_abilities;
```
This will generate ability name per each route path:

```php
[
    'index'     => 'testuser.index',
    'create'    => 'testuser.create',
    'store'     => 'testuser.store',
    'show'      => 'testuser.show',
    'edit'      => 'testuser.edit',
    'update'    => 'testuser.update',
    'destroy'   => 'testuser.destroy',
]
```

In config/auzo-tools.php, you can modify the CRUD for what route paths to
generate for:

```php
'crud' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']
```

### Generate CRUD abilities for a model fields

It can also generate full list of abilities for a model fields like so:

```php
$generator = GenerateAbilities::fieldsAbilities(App\User::class)
$generated_fields_abilities = $generator->fields_crud_abilities;
```

This will generate ability name per each model column per each model route path:

```php
[
    'id' => [
        'index'     => 'user.index.id',
        'create'    => 'user.create.id',
        'store'     => 'user.store.id',
        'show'      => 'user.show.id',
        'edit'      => 'user.edit.id',
        'update'    => 'user.update.id',
        'destroy'   => 'user.destroy.id'
    ],
    'name' => [
        'index'     => 'user.index.name',
        'create'    => 'user.create.name',
        'store'     => 'user.store.name',
        'show'      => 'user.show.name',
        'edit'      => 'user.edit.name',
        'update'    => 'user.update.name',
        'destroy'   => 'user.destroy.name'
    ],
    'email' => [
        'index'     => 'user.index.email',
        'create'    => 'user.create.email',
        'store'     => 'user.store.email',
        'show'      => 'user.show.email',
        'edit'      => 'user.edit.email',
        'update'    => 'user.update.email',
        'destroy'   => 'user.destroy.email'
    ],
    'password' => [
        'index'     => 'user.index.password',
        'create'    => 'user.create.password',
        'store'     => 'user.store.password',
        'show'      => 'user.show.password',
        'edit'      => 'user.edit.password',
        'update'    => 'user.update.password',
        'destroy'   => 'user.destroy.password'
    ],
    'remember_token' => [
        'index'     => 'user.index.remember_token',
        'create'    => 'user.create.remember_token',
        'store'     => 'user.store.remember_token',
        'show'      => 'user.show.remember_token',
        'edit'      => 'user.edit.remember_token',
        'update'    => 'user.update.remember_token',
        'destroy'   => 'user.destroy.remember_token'
    ],
    'created_at' => [
        'index'     => 'user.index.created_at',
        'create'    => 'user.create.created_at',
        'store'     => 'user.store.created_at',
        'show'      => 'user.show.created_at',
        'edit'      => 'user.edit.created_at',
        'update'    => 'user.update.created_at',
        'destroy'   => 'user.destroy.created_at'
    ],
    'updated_at' => [
        'index'     => 'user.index.updated_at',
        'create'    => 'user.create.updated_at',
        'store'     => 'user.store.updated_at',
        'show'      => 'user.show.updated_at',
        'edit'      => 'user.edit.updated_at',
        'update'    => 'user.update.updated_at',
        'destroy'   => 'user.destroy.updated_at'
    ]
]
```

### Saving generated abilities as json string to a file

Encodes the results in a json string and save it to given file path:

```php
$file_path = config_path('abilities/generated_abilities.json');
// This will faltten the output array
GenerateAbilities::fullCrudAbilities($model)->writeToFile($file_path);
// This will not faltten the output array
GenerateAbilities::fullCrudAbilities($model)->writeToFile($file_path, false);
```

Flatten Output:

```php
[
    "user.index", "user.create", "user.store", "user.show", "user.edit", "user.update",
    "user.destroy", "user.index.id", "user.create.id", "user.store.id", "user.show.id",
    "user.edit.id", "user.update.id", "user.destroy.id", "user.index.name", "user.create.name",
    "user.store.name", "user.show.name", "user.edit.name", "user.update.name",
    "user.destroy.name", "user.index.email", "user.create.email", "user.store.email",
    "user.show.email", "user.edit.email", "user.update.email", "user.destroy.email",
    "user.index.password", "user.create.password", "user.store.password", "user.show.password",
    "user.edit.password", "user.update.password", "user.destroy.password",
    "user.index.remember_token", "user.create.remember_token", "user.store.remember_token",
    "user.show.remember_token", "user.edit.remember_token", "user.update.remember_token",
    "user.destroy.remember_token", "user.index.created_at", "user.create.created_at",
    "user.store.created_at", "user.show.created_at", "user.edit.created_at",
    "user.update.created_at", "user.destroy.created_at", "user.index.updated_at",
    "user.create.updated_at", "user.store.updated_at", "user.show.updated_at",
    "user.edit.updated_at", "user.update.updated_at", "user.destroy.updated_at"
]
```

## Route authorize middleware

This is a Route middleware that can be used with routes or in the Http controllers
to check user authorization before accessing the requested resource.

### use it with parameter:

```php
Route::get('user-profile-test', function (){
    return 'hello there';
})->middleware('auzo.acl:user-profile');
```

This will check if the user has authorize ability for 'user-profile'

### Automatic authorization check

```php
Route::get('user-profile-test/{id}', 'Controller@action')
       ->name('user.profile.test')->middleware('auzo.acl');
```

With `named route`, you might use the middleware with no parameter for automatic
authorization.

1. It will check if user is authorized for the route name `user.profile.test`
2. If route does not have name, then it will check against the `Controller@action`

## Controller authorize validation rule

This is a custom validation rule `auzo.can` that check if the user is authorized
against a passed parameter for the field ability name.

```php
$v = Validator::make($data, [
    'someField' => 'auzo.can:test.ability.someField',
]);
```

if user is not authorized, a validation error is generated, to modify the generated
error message, modify `resource/lang/vendor/auzo-tools/en/validation.php`:

```php
return [
    'can' => 'You are not authorized to modify :attribute !',
];
```

## Model fields policy

Very useful for API, where you can change hidden, fillable, and guarded columns
based on user authorization before the model data is being sent to the user.

First make sure you added the ModelFieldsPoilcy trait to your models.
```php
use Kordy\AuzoTools\Traits\ModelFieldsPolicy;
```

Example: In your model files add this after class declaration:
```php
class SomeModel extends Model {

    use Kordy\AuzoTools\Traits\ModelFieldsPolicy;
```

Pass the model ability fix, so it checks for each field name attached to the ability
 fix and check if user is authorized for the generated ability.

So if model has fields `name`, `email`, `password`, and yoe pass `user.show` fix,
Then it will check authorization for:

check for the `name` field if the user has ability `user.show.name`

check for the `email` field if the user has ability `user.show.email`

check for the `password` field if the user has ability `user.show.password`
and so on.

### Make unauthorized columns hidden

```php
$model->hideFieldsByPolicy('user.show');
```

### Make unauthorized columns not fillable

```php
$model->fillableFieldsByPolicy('user.show');
```

### Make unauthorized columns guarded

```php
$model->guardFieldsByPolicy('user.show');
```

# Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

# License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

