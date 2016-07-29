<?php

namespace Kordy\AuzoTools\Tests;

use Exception;
use Faker\Factory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\Access\Authorizable as AuthorizableTrait;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Str;
use Kordy\AuzoTools\AuzoToolsServiceProvider;
use Kordy\AuzoTools\Traits\ModelFieldsPolicy;

abstract class AuzoToolsTestCase extends TestCase
{
    use DatabaseMigrations;

    protected $gate;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected $userClass;

    /**
     * Creates the application.
     *
     * @throws Exception
     *
     * @return Application
     */
    public function createApplication()
    {
        // $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app = require __DIR__.'/../../../../bootstrap/app.php';

        $this->setEnv();

        $app->make(Kernel::class)->bootstrap();

        // Run test migrations in the testing environment on sqlite on memory
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        // Use a specific User model, so we can include traits when running tests
        $laravel_version = substr($app::VERSION, 0, 3);

        switch ($laravel_version) {
            case '5.1':
                $app['config']->set('auth.model', TestUserL51::class); //Laravel 5.1
                $this->userClass = app(TestUserL51::class);
                break;
            case '5.2':
                $app['config']->set('auth.providers.users.model', TestUserL52::class); //Laravel 5.2
                $this->userClass = app(TestUserL52::class);
                break;
            default:
                throw new Exception('This package supports only Laravel 5.1 and 5.2');
        }

        $app->register(AuzoToolsServiceProvider::class);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();

        // Run fresh migrations before every test
        $this->artisan('migrate');

        $this->faker = $faker = Factory::create();

        $this->gate = app(Gate::class);
    }

    /**
     * Set environment values. These usually go to .env file.
     */
    protected function setEnv()
    {
        putenv('APP_KEY='.Str::random(32));

        putenv('APP_ENV=testing');
        putenv('CACHE_DRIVER=array');
        putenv('SESSION_DRIVER=array');
        putenv('QUEUE_DRIVER=sync');
    }
}

$laravel_version = substr(Application::VERSION, 0, 3);
/*
 * Copy of Laravel 5.2's default App\User
 */
if ($laravel_version == '5.2') {
    class TestUserL52 extends User
    {
        use ModelFieldsPolicy;

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
    class TestUserL51 extends Model implements Authenticatable, Authorizable
    {
        use AuthenticatableTrait, AuthorizableTrait, ModelFieldsPolicy;

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
