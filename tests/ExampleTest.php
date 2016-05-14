<?php

class ExampleTest extends AuzoToolsTestCase
{
    /**
     * It's rather meta test to see if a User class is successfully mocked.
     *
     * @test
     */
    public function user_class_exists()
    {
        $user = new App\User();
        $user2 = new $this->userClass();
    }
}
