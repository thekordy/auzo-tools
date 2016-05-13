<?php

class GenerateAbilitiesTest extends AuzoToolsTestCase {

    private $expected_fields_abilities = [
        'id' => [
            'index'     => 'testuser.index.id',
            'create'    => 'testuser.create.id',
            'store'     => 'testuser.store.id',
            'show'      => 'testuser.show.id',
            'edit'      => 'testuser.edit.id',
            'update'    => 'testuser.update.id',
            'destroy'   => 'testuser.destroy.id'
        ],
        'name' => [
            'index'     => 'testuser.index.name',
            'create'    => 'testuser.create.name',
            'store'     => 'testuser.store.name',
            'show'      => 'testuser.show.name',
            'edit'      => 'testuser.edit.name',
            'update'    => 'testuser.update.name',
            'destroy'   => 'testuser.destroy.name'
        ],
        'email' => [
            'index'     => 'testuser.index.email',
            'create'    => 'testuser.create.email',
            'store'     => 'testuser.store.email',
            'show'      => 'testuser.show.email',
            'edit'      => 'testuser.edit.email',
            'update'    => 'testuser.update.email',
            'destroy'   => 'testuser.destroy.email'
        ],
        'password' => [
            'index'     => 'testuser.index.password',
            'create'    => 'testuser.create.password',
            'store'     => 'testuser.store.password',
            'show'      => 'testuser.show.password',
            'edit'      => 'testuser.edit.password',
            'update'    => 'testuser.update.password',
            'destroy'   => 'testuser.destroy.password'
        ],
        'remember_token' => [
            'index'     => 'testuser.index.remember_token',
            'create'    => 'testuser.create.remember_token',
            'store'     => 'testuser.store.remember_token',
            'show'      => 'testuser.show.remember_token',
            'edit'      => 'testuser.edit.remember_token',
            'update'    => 'testuser.update.remember_token',
            'destroy'   => 'testuser.destroy.remember_token'
        ],
        'created_at' => [
            'index'     => 'testuser.index.created_at',
            'create'    => 'testuser.create.created_at',
            'store'     => 'testuser.store.created_at',
            'show'      => 'testuser.show.created_at',
            'edit'      => 'testuser.edit.created_at',
            'update'    => 'testuser.update.created_at',
            'destroy'   => 'testuser.destroy.created_at'
        ],
        'updated_at' => [
            'index'     => 'testuser.index.updated_at',
            'create'    => 'testuser.create.updated_at',
            'store'     => 'testuser.store.updated_at',
            'show'      => 'testuser.show.updated_at',
            'edit'      => 'testuser.edit.updated_at',
            'update'    => 'testuser.update.updated_at',
            'destroy'   => 'testuser.destroy.updated_at'
        ]
    ];

    private $expected_model_abilities = [
        'index'     => 'testuser.index',
        'create'    => 'testuser.create',
        'store'     => 'testuser.store',
        'show'      => 'testuser.show',
        'edit'      => 'testuser.edit',
        'update'    => 'testuser.update',
        'destroy'   => 'testuser.destroy',
    ];

    private $flatten_full_model_fields_crud_abilities = [
        "testuser.index", "testuser.create", "testuser.store", "testuser.show", "testuser.edit", "testuser.update",
        "testuser.destroy", "testuser.index.id", "testuser.create.id", "testuser.store.id", "testuser.show.id",
        "testuser.edit.id", "testuser.update.id", "testuser.destroy.id", "testuser.index.name", "testuser.create.name",
        "testuser.store.name", "testuser.show.name", "testuser.edit.name", "testuser.update.name",
        "testuser.destroy.name", "testuser.index.email", "testuser.create.email", "testuser.store.email",
        "testuser.show.email", "testuser.edit.email", "testuser.update.email", "testuser.destroy.email",
        "testuser.index.password", "testuser.create.password", "testuser.store.password", "testuser.show.password",
        "testuser.edit.password", "testuser.update.password", "testuser.destroy.password",
        "testuser.index.remember_token", "testuser.create.remember_token", "testuser.store.remember_token",
        "testuser.show.remember_token", "testuser.edit.remember_token", "testuser.update.remember_token",
        "testuser.destroy.remember_token", "testuser.index.created_at", "testuser.create.created_at",
        "testuser.store.created_at", "testuser.show.created_at", "testuser.edit.created_at",
        "testuser.update.created_at", "testuser.destroy.created_at", "testuser.index.updated_at",
        "testuser.create.updated_at", "testuser.store.updated_at", "testuser.show.updated_at",
        "testuser.edit.updated_at", "testuser.update.updated_at", "testuser.destroy.updated_at"
    ];


    function test_automatic_generate_crud_abilities_for_model()
    {
        $generator = GenerateAbilities::modelAbilities('testuser');

        $this->assertEquals($generator->model_crud_abilities, $this->expected_model_abilities);
    }

    function test_automatic_generate_crud_abilities_for_model_fields()
    {
        $model = $this->userClass;

        $generator = GenerateAbilities::fieldsAbilities($model);

        $this->assertEquals($generator->fields_crud_abilities, $this->expected_fields_abilities);
    }

    function test_automatic_generate_full_crud_abilities_for_model_and_fields()
    {
        $model = $this->userClass;

        $generator = GenerateAbilities::fullCrudAbilities($model);

        $this->assertEquals($generator->model_crud_abilities, $this->expected_model_abilities);
        $this->assertEquals($generator->fields_crud_abilities, $this->expected_fields_abilities);
    }

    function test_automatic_generate_crud_abilities_for_model_with_custom_name()
    {
        $generator = GenerateAbilities::modelAbilities('someName');

        $this->assertEquals($generator->model_crud_abilities['index'], 'somename.index');
    }

    function test_automatic_generate_crud_abilities_for_model_fillable_fields_with_custom_name_delimiter()
    {
        $model = $this->userClass;
        // generate abilities for only fillable fields, use delimiter "-", and custom name "somename"
        $generator = GenerateAbilities::fullCrudAbilities($model, '-', true, 'someName');

        // assert only fillable fields are generated
        $this->assertEquals(app($model)->getFillable(), array_keys($generator->fields_crud_abilities));
        // assert generated fields abilities has same custom name and delimiter
        $this->assertEquals($generator->fields_crud_abilities['name']["index"], 'somename-index-name');
    }

    function test_automatic_generate_abilities_and_write_them_to_a_file()
    {
        $model = $this->userClass;
        $file_path = 'tests/tmp_generated_abilities.json';

        GenerateAbilities::fullCrudAbilities($model)->writeToFile($file_path);

        $generated_abilities = json_decode(\File::get($file_path));

        $this->assertEquals($this->flatten_full_model_fields_crud_abilities, $generated_abilities);
        

        \File::delete($file_path);
    }

}