<?php

namespace Kordy\AuzoTools\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait ModelFieldsPolicy
{
    /**
     * Add not authorized fields for a user to model hidden fields
     * 
     * @param $fix
     * @param bool $fix_is_prefix
     * @param string $delimiter
     * @param null $fields
     * @param null $user
     * @return $this
     */
    public function hideFieldsByPolicy($fix, $fix_is_prefix = true, $delimiter = '.', $fields = null, $user = null)
    {
        if (! $fields) {
            $fields = $columns = Schema::getColumnListing($this->getTable());
        }

        if (! $user) {
            $user = \Auth::user();
        }

        $current_hidden = $this->getHidden();
        foreach ($fields as $field) {
            // Apply the prefix/suffix
            $field_ability = ($fix_is_prefix) ? $fix . $delimiter . $field : $field . $delimiter . $fix;
            // if field is not hidden currently hidden and the user can not access it then add to hidden fields
            if (array_search($field, $current_hidden) === false 
                && $user->cannot($field_ability, app(Request::class))) 
            {
                array_push($current_hidden, $field);
            }
        }

        $this->setHidden($current_hidden);

        return $this;
    }
    
    /**
     * Revoke not authorized fields for a user from model fillable fields
     * 
     * @param $fix
     * @param bool $fix_is_prefix
     * @param string $delimiter
     * @param null $fields
     * @param null $user
     * @return $this
     */
    public function fillableFieldsByPolicy($fix, $fix_is_prefix = true, $delimiter = '.', $fields = null, $user = null)
    {
        if (! $fields) {
            $fields = $columns = Schema::getColumnListing($this->getTable());
        }

        if (! $user) {
            $user = \Auth::user();
        }

        $current_fillable = $this->getFillable();

        foreach ($fields as $field) {
            // Apply the prefix/suffix
            $field_ability = ($fix_is_prefix) ? $fix . $delimiter . $field : $field . $delimiter . $fix;
            // if field is currently fillable and the user can not access it then remove it from fillable
            if (array_search($field, $current_fillable) != false
                && $user->cannot($field_ability, app(Request::class)))
            {
                $i = array_search($field, $current_fillable);
                unset($current_fillable[$i]);
            }
        }
        $this->fillable(array_values($current_fillable));

        return $this;
    }
    
    /**
     * add not authorized fields for a user to model guarded fields
     * 
     * @param $fix
     * @param bool $fix_is_prefix
     * @param string $delimiter
     * @param null $fields
     * @param null $user
     * @return $this
     */
    public function guardFieldsByPolicy($fix, $fix_is_prefix = true, $delimiter = '.', $fields = null, $user = null)
    {
        if ( $this->guarded == ['*']) {
            return $this;
        }
        
        if (! $fields) {
            $fields = $columns = Schema::getColumnListing($this->getTable());
        }

        if (! $user) {
            $user = \Auth::user();
        }
        
        $current_guarded = $this->guarded ? : [];
        foreach ($fields as $field) {
            // Apply the prefix/suffix
            $field_ability = ($fix_is_prefix) ? $fix . $delimiter . $field : $field . $delimiter . $fix;
            if (! $this->isGuarded($field) && $user->cannot($field_ability, app(Request::class))) {
                array_push($current_guarded, $field);
            }
        }
        $this->guard($current_guarded);

        return $this;
    }
}