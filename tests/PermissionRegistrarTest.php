<?php

class PermissionRegistrarTest extends AuzoToolsTestCase
{
    public $logs = [];

    /**
     * Test PermissionRegistrar service, where registrar registers abilities restricted by policies.
     *
     * @test
     */
    public function register_restricted_abilities()
    {
        $admin = factory('App\User')->create();

        $moderator = factory('App\User')->create();

        $owner = factory('App\User')->create();

        $user = factory('App\User')->create();

        // abilities policies array template
        $abilities_policies = [
            'before' => [
                'PermissionRegistrarTest@isAdmin',
            ],
            'abilities' => [

                'post.update' => [
                    'PermissionRegistrarTest@postOwner',
                    ['or' => 'PermissionRegistrarTest@isModerator'],
                ],

                'post.destroy' => [
                    'PermissionRegistrarTest@isModerator',
                ],
            ],
            // use this to log or monitor authorization given to users
            //  you may not modify the result of the authorization check from an after callback
            'after' => [
                'PermissionRegistrarTest@monitor',
            ],
        ];

        // Load abilities to Laravel Gate
        AuzoToolsPermissionRegistrar::registerPermissions($abilities_policies);

        // Admin can access any
        $this->assertTrue($admin->can('post.update'));
        $this->assertFalse($admin->cannot('post.destroy'));

        // Moderator can access any
        $this->assertTrue($moderator->can('post.update'));
        $this->assertTrue($moderator->can('post.destroy'));

        // Owner can edit but can not destroy his own post
        $this->assertTrue($owner->can('post.update'));
        $this->assertFalse($owner->can('post.destroy'));

        // Owner only can edit
        $this->assertTrue($user->cannot('post.update'));
        $this->assertFalse($user->can('post.destroy'));
    }

    /**
     * Test PermissionRegistrar service, where registrar registers abilities restricted by callbacks.
     *
     * @test
     */
    public function permissions_using_callback_functions()
    {
        $admin = factory('App\User')->create();

        $moderator = factory('App\User')->create();

        $owner = factory('App\User')->create();

        $user = factory('App\User')->create();

        // abilities policies array template
        $abilities_policies = [
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

        // Load abilities to Laravel Gate
        AuzoToolsPermissionRegistrar::registerPermissions($abilities_policies);

        // Admin can access any
        $this->assertTrue($admin->can('post.update'));
        $this->assertFalse($admin->cannot('post.destroy'));

        // Moderator can access any
        $this->assertTrue($moderator->can('post.update'));
        $this->assertTrue($moderator->can('post.destroy'));

        // Owner can edit but can not destroy his own post
        $this->assertTrue($owner->can('post.update'));
        $this->assertFalse($owner->can('post.destroy'));

        // Owner only can edit
        $this->assertTrue($user->cannot('post.update'));
        $this->assertFalse($user->can('post.destroy'));
    }

    /**
     * Check if user is admin.
     *
     * @param $user
     * @param $ability
     *
     * @return bool
     */
    public function isAdmin($user, $ability, $model = null)
    {
        return $user->id == 1;
    }

    /**
     * Check if user is moderator.
     *
     * @param $user
     * @param $ability
     *
     * @return bool
     */
    public function isModerator($user, $ability, $model = null)
    {
        return $user->id == 2;
    }

    /**
     * Check if user is post owner.
     *
     * @param $user
     * @param $ability
     *
     * @return bool
     */
    public function postOwner($user, $ability, $model = null)
    {
        return $user->id == 3;
    }

    /**
     * Run authorization monitor, see storage/logs/laravel.log.
     *
     * @param $user
     * @param $ability
     */
    public function monitor($user, $ability, $result, $arguments = null)
    {
        if ($result) {
            \Log::info("Authorization Log: User $user->name ($user->email) is granted access to ability $ability at ".date('d-m-Y H:j'));
        } else {
            \Log::info("Authorization Log: User $user->name ($user->email) is forbidden to access ability $ability at ".date('d-m-Y H:j'));
        }
    }
}
