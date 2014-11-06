<?php
namespace _2UpMedia\Hooky\Integration;

use _2UpMedia\Hooky\Fixtures\Client;
use _2UpMedia\Hooky\Constants;
use _2UpMedia\Hooky\BaseTestCase;

class InstanceHooksTest extends BaseTestCase
{
    public function testBeforeHooks()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;

        $this->client->before_getTextHook(function ($resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
        });

        $this->client->beforeGetTextHook(array($this, 'getBeforeGetTextCallable'));

        $this->client->beforeGetTextHook(function ($resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->beforeAllHook(function ($method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->beforeAllHook(function ($method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @param $instance
     * @param $resourceLocation
     *
     * @return string
     */
    public function getBeforeGetTextCallable($instance, &$resourceLocation)
    {
        $resourceLocation = '/path/to/resource1';

        $this->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        $this->assertEquals('/path/to/resource1', $resourceLocation);
        return $resourceLocation;
    }





    public function testOnceBeforePublicMethod()
    {
        $this->setPublicAccessible();
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBeforeGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceBeforeProtectedMethod()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBefore_getTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceBeforeAll()
    {
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBeforeAllHook(function ($method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceAfterMethod()
    {
        $this->setPublicAccessible();

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceAfterGetTextHook(function (&$resourceLocation) use ($that, &$count) {
            ++$count;

            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceAfterAll()
    {
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceAfterAllHook(function ($method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testAfterProtectedMethod()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->after_getTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(2, $count);
    }

    public function testAfterPrivateMethod()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE | Constants::PRIVATE_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->afterPrivateMethodHook(function () use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
        });

        $this->client->publicMethod();
        $this->client->publicMethod();

        $this->assertEquals(2, $count);
    }

    public function testAfterHooks()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;

        $this->client->after_getTextHook(function ($resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
        });

        $this->client->afterGetTextHook(function (&$resourceLocation) use ($that) {
            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->afterGetTextHook(function ($resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->afterAllHook(function ($method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->afterAllHook(function ($method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
    }







    public function testConstructorHooks()
    {
//        $this->markTestIncomplete('r');

        $this->setPublicAccessible();

        $beforeCount = 0;
        $beforeCallable = function ($parameterOne, $parameterTwo) use (&$beforeCount) {
            ++$beforeCount;
        };
        Client::beforeConstructorHook($beforeCallable);

        $afterCount = 0;
        $afterCallable = function () use (&$afterCount) {
            ++$afterCount;
        };
        Client::afterConstructorHook($afterCallable);

        $one = new Client();

        $this->assertEquals(1, $beforeCount);
        $this->assertEquals(1, $afterCount);

        $beforeCount = 0;
        $afterCount = 0;

        $one = new Client();

        $this->assertEquals(0, $beforeCount);
        $this->assertEquals(0, $afterCount);

        $beforeCount = 0;
        $afterCount = 0;

        Client::beforeConstructorHook($beforeCallable);
        Client::afterConstructorHook($afterCallable);

        $three = new Client();

        $this->assertEquals(1, $beforeCount);
        $this->assertEquals(1, $afterCount);
    }

    public function testDestructorHooks()
    {
        $this->setPublicAccessible();

        $beforeCount = 0;
        $this->client->before__destructHook(function () use (&$beforeCount) {
            ++$beforeCount;
        });

        $afterCount = 0;
        $this->client->after__destructHook(function () use (&$afterCount) {
            ++$afterCount;
        });

        unset($this->client);

        $this->assertEquals(1, $beforeCount);
        $this->assertEquals(1, $afterCount);
    }

    public function testNoCallablesSetUp()
    {
        $client = new Client();

        $this->assertEquals('/path/to/resource', $client->getText('/path/to/resource'));
    }



}
