<?php

namespace Kordy\AuzoTools\Services;

use Illuminate\Http\Request;

class ValidationRules
{
    /**
     * Check if user is authorized to access an ability
     *
     * @param $attribute
     * @param $value
     * @param $parameters
     * @param $validator
     * @return bool
     */
    public function can($attribute, $value, $parameters, $validator)
    {
        $validator->setFallbackMessages(['auzo.can' => trans('auzoTools::validation.can')]);
        $ability = $parameters[0];
        return \Auth::check() && \Auth::user()->can($ability, app(Request::class));
    }
}