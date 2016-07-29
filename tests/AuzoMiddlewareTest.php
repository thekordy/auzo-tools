<?php

namespace Kordy\AuzoTools\Tests;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuzoMiddleware extends AuzoToolsTestCase
{
    public function test_auzo_middleware_for_authorization_check()
    {
        $ability = 'user-profile';

        $user1 = factory('App\User')->create();

        $user2 = factory('App\User')->create();

        $this->gate->define($ability, function ($user) use ($ability) {
            return $user->id == 1;
        });

        Route::get('user-profile-test', function () {
            return 'hello there';
        })->middleware('auzo.acl:user-profile');

        $this->actingAs($user1)
            ->visit('/user-profile-test')
            ->see('hello there');

        try {
            $this->actingAs($user2)->visit('/user-profile-test');
        } catch (Exception $e) {
            $this->assertContains('403', $e->getMessage());
        }
    }

    public function test_auzo_middleware_uses_route_name_as_ability_name_for_authorization_check()
    {
        $ability = 'user.profile.test';

        $user1 = factory('App\User')->create();
        $user2 = factory('App\User')->create();

        $this->gate->define($ability, function ($user, $model) use ($ability) {
            return $this->profileOwner($user, $model) || $this->siteAdmin($user);
        });

        Route::get('user-profile-test/{id}', function ($id) {
            return "hello there user $id";
        })->name('user.profile.test')->middleware('auzo.acl');

        // user1 can view any user profile as an admin policy
        $this->actingAs($user1)
            ->visit('/user-profile-test/1')
            ->see('hello there user 1')
            ->visit('/user-profile-test/2')
            ->see('hello there user 2');

        // user2 can only see his own profile
        $this->actingAs($user2)
            ->visit('/user-profile-test/2')
            ->see('hello there user 2');

        try {
            $this->actingAs($user2)->visit('/user-profile-test/1');
        } catch (Exception $e) {
            $this->assertContains('403', $e->getMessage());
        }
    }

    /**
     * Example of condition method to restrict permissions.
     *
     * @param $user
     * @param $model
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function profileOwner($user, $model)
    {
        $user_model = app('App\User');

        if (!$model instanceof $user_model && !$model instanceof Request) {
            // Invalid argument $ticket
            throw new Exception("Invalid argument! no valid user instance found in $model");
        }
        // where $model = Request $request passed by the middleware
        if ($model instanceof Request) {
            $model = $user_model->findOrFail($model->id);
        }

        return $user->id == $model->id;
    }

    /**
     * Example of condition method to restrict permissions.
     *
     * @param $user
     *
     * @return bool
     */
    public function siteAdmin($user)
    {
        return $user->id == 1;
    }
}
