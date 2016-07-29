<?php

namespace Kordy\AuzoTools\Tests;

use App\User;

class ExampleTest extends AuzoToolsTestCase
{
    /**
     * It's rather meta test to see if a User class is successfully mocked.
     *
     * @test
     */
    public function user_class_exists()
    {
        $user = new User();
        $user2 = new $this->userClass();
    }
}
