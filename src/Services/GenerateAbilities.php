<?php

namespace Kordy\AuzoTools\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class GenerateAbilities
{
    // CRUD elements/routes to be used in the model abilities auto generate
    public $crud;

    // model CRUD generated abilities
    public $model_crud_abilities = [];

    // model fields generated abilities
    public $fields_crud_abilities = [];

    public function __construct()
    {
        $this->crud = config('auzoTools.crud');
    }

    /**
     * Generates an ability for each CRUD element
     * ex. ClassName.index, ClassName.create, ..etc.
     *
     * @param string       $delimiter
     * @param string|Model $name
     *
     * @return $this
     */
    public function modelAbilities($name, $delimiter = '.')
    {
        $name = is_string($name) ? $name : class_basename($name);

        // create the model ability crud
        foreach ($this->crud as $ability) {
            $this->model_crud_abilities[$ability] = strtolower($name.$delimiter.$ability);
        }

        return $this;
    }

    /**
     * Generates an ability for each field for each model CRUD ability
     * ex. ClassName.create.field1, ClassName.create.field2,
     *      ClassName.store.field2, ClassName.store.field2, ..etc.
     *
     * @param $model
     * @param string $delimiter
     * @param bool   $fillable_only
     *
     * @return $this
     */
    public function fieldsAbilities($model, $delimiter = '.', $fillable_only = false)
    {
        if (is_string($model)) {
            $model = app($model);
        }
        if ($fillable_only) {
            $columns = $model->getFillable();
        } else {
            $columns = Schema::getColumnListing($model->getTable());
        }
        if (!$this->model_crud_abilities) {
            $this->modelAbilities($model, $delimiter);
        }

        // create columns crud abilities
        foreach ($columns as $column) {
            foreach ($this->model_crud_abilities as $ability => $model_ability) {
                $this->fields_crud_abilities[$column][$ability] =
                    strtolower($model_ability.$delimiter.$column);
            }
        }

        return $this;
    }

    /**
     * Generates an ability for each element and for fields,
     * ex. ClassName.create, ClassName.create.field1, ClassName.create.field2,
     *      ClassName.store, ClassName.store.field1, ClassName.store.field2, ..etc.
     *
     * @param Model       $model
     * @param string      $delimiter
     * @param bool        $fillable_only
     * @param null|string $name
     *
     * @return $this
     */
    public function fullCrudAbilities($model, $delimiter = '.', $fillable_only = false, $name = null)
    {
        $name = $name ?: $model;

        return $this->modelAbilities($name, $delimiter)
                    ->fieldsAbilities($model, $delimiter, $fillable_only);
    }

    public function writeToFile($file_path, $flatten = true, $model = true, $fields = true)
    {
        $abilities = [];
        if ($model) {
            if (!$this->model_crud_abilities) {
                throw new \Exception('No model abilities are found!');
            }
            array_push($abilities, $this->model_crud_abilities);
        }
        if ($fields) {
            if (!$this->fields_crud_abilities) {
                throw new \Exception('No fields abilities are found!');
            }
            array_push($abilities, $this->fields_crud_abilities);
        }
        if ($flatten) {
            $abilities = array_flatten($abilities);
        }
        $bytes_written = File::put($file_path, json_encode($abilities));

        if ($bytes_written === false) {
            throw new \Exception('Error writing to file');
        }

        return true;
    }
}
