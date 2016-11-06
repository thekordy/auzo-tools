<?php

use App\User;
use Faker\Factory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Foundation\Auth\Access\Authorizable as AuthorizableTrait;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

        if (version_compare($app::VERSION, '5.2', '<')) {
            // This is Laravel 5.1 or earlier
            $app['config']->set('auth.model', TestUserL51::class);
            $this->userClass = app(TestUserL51::class);
        } else {
            // Laravel version 5.2+
            $app['config']->set('auth.providers.users.model', TestUserL52::class);
            $this->userClass = app(TestUserL52::class);
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

$version = Application::VERSION;
/*
 * Copy of Laravel 5.2's default App\User
 */
if (version_compare($version, '5.2', '>=')) {
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
if (version_compare($version, '5.2', '<')) {
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
