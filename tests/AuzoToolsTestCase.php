<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Schema\Blueprint;

abstract class AuzoToolsTestCase extends \Orchestra\Testbench\TestCase
{

    protected $gate;
    
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected $userClass;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @throws Exception
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        // Create users table
        Illuminate\Support\Facades\Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->rememberToken();
            $table->timestamps();
        });

        $this->userClass = 'TestUser';

        $this->withFactories(__DIR__.'/factories');

        $this->gate = app(Gate::class);
    }

    protected function getPackageProviders($app)
    {
        return ['Kordy\AuzoTools\AuzoToolsServiceProvider'];
    }
}

$laravel_version = substr(Illuminate\Foundation\Application::VERSION, 0, 3);
/*
 * Copy of Laravel 5.2's default App\User
 */
if ($laravel_version == '5.2') {
    class TestUser extends \Illuminate\Foundation\Auth\User
    {
        use \Kordy\AuzoTools\Traits\ModelFieldsPolicy;

        protected $table = 'users';
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = [
            'name',
            'email',
            'password',
        ];
        /**
         * The attributes that should be hidden for arrays.
         *
         * @var array
         */
        protected $hidden = [
            'password',
            'remember_token',
        ];
    }
}
/*
 * Copy of Laravel 5.1's default App\User
 * without CanResetPassword trait
 */
if ($laravel_version == '5.1') {
    class TestUser extends Illuminate\Database\Eloquent\Model implements Illuminate\Contracts\Auth\Authenticatable, Illuminate\Contracts\Auth\Access\Authorizable
    {
        use Illuminate\Auth\Authenticatable, Illuminate\Foundation\Auth\Access\Authorizable;
        use \Kordy\AuzoTools\Traits\ModelFieldsPolicy;
        /**
         * The database table used by the model.
         *
         * @var string
         */
        protected $table = 'users';
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = ['name', 'email', 'password'];
        /**
         * The attributes excluded from the model's JSON form.
         *
         * @var array
         */
        protected $hidden = ['password', 'remember_token'];
    }
}

class_alias('TestUser', 'App\User');