<?php

namespace Kordy\AuzoTools\Services;

use Exception;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Log;

class PermissionRegistrar
{
    /**
     * @var Gate
     */
    protected $gate;
    /**
     * @var Repository
     */
    protected $cache;
    /**
     * @var string
     */
    protected $cacheKey = 'auzoTools.permissions.cache';

    /**
     * @param Gate       $gate
     * @param Repository $cache
     */
    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate = $gate;
        $this->cache = $cache;
    }

    /**
     *  Register the abilities.
     *
     * @param array $abilities_permissions
     *
     * @return bool
     */
    public function registerPermissions(array $abilities_permissions)
    {
        try {

            // a callback that is run before all other authorization checks
            if (isset($abilities_permissions['before'])) {
                if (is_array($abilities_permissions['before'])) {
                    $this->runGateBefore($abilities_permissions['before']);
                } else {
                    Log::alert('abilities "before" must be an array, no before policies registered!');
                }
            }

            // Register abilities and their polices
            if (isset($abilities_permissions['abilities'])) {
                if (is_array($abilities_permissions['abilities'])) {
                    $this->runGateDefine($abilities_permissions['abilities']);
                } else {
                    Log::alert('abilities must be an array, no abilities registered!');
                }
            }

            //  you may not modify the result of the authorization check from an after callback
            if (isset($abilities_permissions['after'])) {
                if (is_array($abilities_permissions['after'])) {
                    $this->runGateAfter($abilities_permissions['after']);
                } else {
                    Log::alert('abilities "after" must be an array, no "after" callbacks registered!');
                }
            }

            return true;
        } catch (Exception $e) {
            Log::alert('Could not register abilities .. '.$e);

            return false;
        }
    }

    /**
     * @param array $before_permissions
     *
     * @return bool
     */
    private function runGateBefore(array $before_permissions)
    {
        $this->gate->before(function ($user, $ability) use ($before_permissions) {
            $result = true;
            foreach ($before_permissions as $restriction) {
                if (is_array($restriction)) {
                    $policy = reset($restriction);
                    $operator = key($restriction);
                } else {
                    $policy = $restriction;
                    $operator = 'and';
                }

                list($class, $method) = explode('@', $policy);
                $policy_method = app($class)->$method($user, $ability); // should return boolean
                if ($operator === 'or' || $operator === '||') {
                    $result = $result || $policy_method;
                } else {
                    $result = $result && $policy_method;
                }
            }
            if ($result == true) {
                return true;
            }
        });
    }

    /**
     * @param array $after_authorization_callbacks
     *
     * @return bool
     */
    private function runGateAfter(array $after_authorization_callbacks)
    {
        $this->gate->after(function ($user, $ability, $result, $arguments = null) use ($after_authorization_callbacks) {
            foreach ($after_authorization_callbacks as $callback) {
                list($class, $method) = explode('@', $callback);
                app($class)->$method($user, $ability, $result, $arguments);
            }
        });
    }

    /**
     * @param array $abilities_permissions
     *
     * @return bool
     */
    private function runGateDefine(array $abilities_permissions)
    {
        foreach ($abilities_permissions as $ability => $policies) {
            $this->gate->define($ability, function ($user, $model = null) use ($ability, $policies) {
                $result = true;
                foreach ($policies as $restriction) {
                    if (is_array($restriction)) {
                        $policy = reset($restriction);
                        $operator = key($restriction);
                    } else {
                        $policy = $restriction;
                        $operator = 'and';
                    }

                    list($class, $method) = explode('@', $policy);
                    $policy_method = app($class)->$method($user, $ability, $model); // should return boolean
                    if ($operator === 'or' || $operator === '||') {
                        $result = $result || $policy_method;
                    } else {
                        $result = $result && $policy_method;
                    }
                }

                return $result;
            });
        }
    }
}
