<?php

namespace Kordy\AuzoTools\Tests;

use Illuminate\Support\Facades\Validator;

class ValidationRulesTest extends AuzoToolsTestCase
{
    public function test_controller_auzo_can_validation_rule_to_check_user_authorization_per_field()
    {
        $ability = 'test.ability.someField';

        $user = factory('App\User')->create();

        $this->gate->define($ability, function ($user, $model) use ($ability) {
            return $user->id == 1;
        });

        $this->actingAs($user);

        $data = ['someField' => 'some input', 'someOtherField' => 'other input'];

        $v = Validator::make($data, [
            'someField'      => 'auzo.can:test.ability.someField',
            'someOtherField' => 'auzo.can:test.ability.someOtherField',
        ]);

        // assert no authorized fields generate validation error
        $this->assertTrue($v->errors()->has('someOtherField'));

        // assert error message translation is loaded
        $translation_error_msg = trans('auzoTools::validation.can');
        $msg = str_replace(':attribute', 'some other field', $translation_error_msg);
        $this->assertEquals($msg, $v->errors()->get('someOtherField')[0]);

        // assert authorized fields are passed
        $this->assertFalse($v->errors()->has('someField'));
    }
}
