<?php

class FieldsPolicyTest extends AuzoToolsTestCase {

    function test_hide_model_collection_fields_by_policy()
    {
        $ability = 'user.show.name';

        $user = factory('App\User')->create();

        $this->gate->define($ability, function ($user, $model) use ($ability) {
            return $user->id == 1;
        });

        $this->actingAs($user);
        
        // this user has ability only for name, so hide any other field, checking by prefix user.show. + field name
        $model = App\User::find(1)->hideFieldsByPolicy('user.show');
        $this->assertEquals([],
            array_diff(
                $model->getHidden(),
                ['id', 'email', 'password', 'remember_token', 'created_at', 'updated_at']
            )
        );
    }

    function test_revoke_fillable_model_collection_fields_by_policy()
    {
        $ability = 'user.create.name';

        $user = factory('App\User')->create();

        $this->gate->define($ability, function ($user, $model) use ($ability) {
            return $user->id == 1;
        });

        $this->actingAs($user);
        
        // this user has only ability to name field, so make all other fields not fillable
        $model = App\User::find(1)->fillableFieldsByPolicy('ability.create');

        $this->assertEquals(['name'], $model->getFillable());
    }

    function test_guard_model_collection_fields_by_policy()
    {
        $ability = 'user.store.name';

        $user = factory('App\User')->create();

        $this->gate->define($ability, function ($user, $model) use ($ability) {
            return $user->id == 1;
        });

        $this->actingAs($user);

        // this user has ability only for name, so guard all other fields
        $model = App\User::find(1);

        $other_fields = ['id', 'email', 'password', 'remember_token', 'created_at', 'updated_at'];
        $guarded = $model->getGuarded() == ['*'] ? ['*'] : $other_fields;

        $model = $model->guardFieldsByPolicy('ability.store');

        $this->assertEquals($model->getGuarded(), $guarded);
    }

}