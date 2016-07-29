<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRUD names
    |--------------------------------------------------------------------------
    |
    | These are the names to be used when generating model abilities.
    |
    | ex. AuzoTools will generate 7 abilities for the post model:
    | post.index, post.create, post.store, post.show, post.edit, post.update,
    | and post.destroy
    |
    */

    'crud' => ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'],

    /*
    |--------------------------------------------------------------------------
    | AuzoTools Authorize Registrar
    |--------------------------------------------------------------------------
    |
    | You may here add custom registrar where the Laravel Gate abilities are defined
    |
    */

    'registrar' => \Kordy\AuzoTools\Services\PermissionRegistrar::class,

];
