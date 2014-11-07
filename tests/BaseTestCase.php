<?php
namespace _2UpMedia\Hooky;

require_once __DIR__.'/fixtures/Client.php'; // TODO: figure out how to remove this without losing code coverage

use _2UpMedia\Hooky\fixtures\Client;

class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client();
    }

    /**
     * @param object $object
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function callInaccessibleMethodWithArgs($object, $method, array $args)
    {
        $reflectionMethod = new \ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $args);
    }

    /**
     * @return mixed
     */
    protected function setPublicAccessible()
    {
        return $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE]
        );
    }

    /**
     * @param $concreteInstance
     */
    protected function setPublicAndProtectedAccessibility($concreteInstance)
    {
        $this->callInaccessibleMethodWithArgs(
            $concreteInstance,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );
    }

    public function tearDown()
    {
        Client::resetStaticConstructorHooks();
        Client::resetHookableMethods();
    }

}
