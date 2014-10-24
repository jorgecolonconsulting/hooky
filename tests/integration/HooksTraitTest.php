<?php
namespace _2UpMedia\Hooky\Integration;

require_once __DIR__.'/../fixtures/Client.php'; // TODO: figure out how to remove this without losing code coverage

use _2UpMedia\Hooky\Fixtures\Client;
use _2UpMedia\Hooky\Constants;
use _2UpMedia\Hooky\CancelPropagationException;

class HooksTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client();
    }

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

        $this->client->before_getTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        });

        $this->client->beforeGetTextHook(array($this, 'getBeforeGetTextCallable'));

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->beforeAllHook(function ($instance, $method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->beforeAllHook(function ($instance, $method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 3 'nonexistantParameter' does not
     *                           exist in original getText() method as argument 2
     */
    public function testMissingCallableParameters()
    {
        $this->client->beforeGetTextHook(function ($instance, $resourceLocation, $nonexistantParameter) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 2 exists in the original getText() method
     *                           as argument 1 but is omitted in the callable
     */
    public function testMissingOriginalMethodParameters()
    {
        $this->client->beforeGetTextHook(function ($instance) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 2 'uri' is named
     *                           'resourceLocation' in the original getText() method as argument 1
     */
    public function testMismatchingParameters()
    {
        $this->client->beforeGetTextHook(function ($instance, $uri) {
        });

        $this->client->getText('/path/to/resource');
    }

    public function testBeforeAllCanCancelPropagation()
    {
        $that = $this;

        $beforeAllCount = 0;
        $beforeMethodCount = 0;
        $onceBeforeAllCount = 0;
        $onceBeforeMethodCount = 0;

        $afterAllCount = 0;
        $afterMethodCount = 0;
        $onceAfterAllCount = 0;
        $onceAfterMethodCount = 0;

        $beforeConstructorCount = 0;
        $afterConstructorCount = 0;

        $this->client->beforeAllHook(function ($instance, $method) use ($that, &$beforeAllCount) {
            ++$beforeAllCount;

            throw new CancelPropagationException();
        });

        $this->client->beforeAllHook(function ($instance, $method) use ($that, &$beforeAllCount) {
            ++$beforeAllCount;
        });

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;
        });

        $this->client->onceBeforeAllHook(function ($instance, $resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeAllHook(function ($instance, $resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;
        });

        $this->client->onceBeforeGetTextHook(function ($instance, $resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeGetTextHook(function ($instance, $resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that, &$afterAllCount) {
            ++$afterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that, &$afterAllCount) {
            ++$afterAllCount;
        });

        $this->client->afterGetTextHook(function ($instance, $resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->afterGetTextHook(function ($instance, $resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;
        });

        $this->client->onceAfterGetTextHook(function ($instance, $resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterGetTextHook(function ($instance, $resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;
        });

        $this->client->onceAfterAllHook(function ($instance) use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterAllHook(function ($instance) use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;
        });

        Client::beforeConstructorHook(
            function ($instance, $parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
                ++$beforeConstructorCount;

                throw new CancelPropagationException();
            }
        );

        Client::beforeConstructorHook(
            function ($instance, $parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
                ++$beforeConstructorCount;
            }
        );

        Client::afterConstructorHook(
            function ($instance, $parameterOne, $parameterTwo) use (&$afterConstructorCount) {
                ++$afterConstructorCount;

                throw new CancelPropagationException();
            }
        );

        Client::afterConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$afterConstructorCount) {
            ++$afterConstructorCount;
        });

        new Client();

        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $beforeAllCount);
        $this->assertEquals(1, $beforeMethodCount);
        $this->assertEquals(1, $onceBeforeMethodCount);

        $this->assertEquals(1, $afterAllCount);
        $this->assertEquals(1, $afterMethodCount);
        $this->assertEquals(1, $onceAfterMethodCount);

        $this->assertEquals(1, $beforeConstructorCount);
        $this->assertEquals(1, $afterConstructorCount);
    }

    public function testBeforeAllEarlyReturn()
    {
        $that = $this;
        $count = 0;

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);

            return true;
        });

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->afterGetTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceBeforePublicMethod()
    {
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBeforeGetTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
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

        $this->client->onceBefore_getTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

        $this->client->onceBeforeAllHook(function ($instance, $method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
        $this->client->getText('/path/to/resource');

        $this->assertEquals(1, $count);
    }

    public function testOnceAfterMethod()
    {
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceAfterGetTextHook(function ($instance, &$resourceLocation) use ($that, &$count) {
            ++$count;

            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

        $this->client->onceAfterAllHook(function ($instance, $method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

        $this->client->after_getTextHook(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

        $this->client->afterPrivateMethodHook(function ($instance) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
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

        $this->client->after_getTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        });

        $this->client->afterGetTextHook(function ($instance, &$resourceLocation) use ($that) {
            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->afterGetTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method is restricted by hooky options
     */
    public function testMethodNotRestrictedWithDefaults()
    {
        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
        $this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage getText method is restricted by hooky options
     */
    public function testMethodNotRestrictedWithProtectedAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PROTECTED_ACCESSIBLE]
        );

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
        $this->assertFalse($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
    }

    public function testMethodNotRestrictedWithProtectedAndPublicAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE]
        );

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
    }

    public function testMethodNotRestrictedWithPublicProtectedAndPrivateAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE | Constants::PRIVATE_ACCESSIBLE]
        );

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
        $this->assertTrue(
            $this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['privateMethod'])
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from after_getTextHook is restricted by hooky options
     */
    public function testSetHookableMethods()
    {
        $this->callInaccessibleMethodWithArgs($this->client, 'setHookableMethods', [['getText']]);

        $that = $this;

        $this->client->afterGetTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->getText('/path/to/resource');

        $this->client->after_getTextHook(function ($instance, $resourceLocation, &$return) use ($that) {
            $that->fail("This shouldn't have been called");
        });
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from before_getTextHook is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallProtectedMethod()
    {
        $that = $this;

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use ($that) {
        });

        $this->client->before_getTextHook(function ($instance, $resourceLocation) use ($that) {
            $that->fail("This shouldn't be accessible");
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage privateMethod method called from beforePrivateMethodHook is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallPrivateMethod()
    {
        $that = $this;

        $this->client->beforeGetTextHook(function ($instance, $resourceLocation) use ($that) {
        });

        $this->client->beforePrivateMethodHook(function () use ($that) {
            $that->fail("This shouldn't be accessible");
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage NonexistantMethod doesn't exist
     */
    public function testNonexistantMethodsThrowsException()
    {
        $this->client->beforeNonexistantMethodHook(function ($instance, $resourceLocation) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage There's a typo in beforGetText. Can't properly set up hook.
     */
    public function testMagicMethodTypo()
    {
        $this->client->beforGetText(function ($instance, $resourceLocation) {
        });

        $this->client->getText('/path/to/resource');
    }

    public function testConstructorHooks()
    {
        $beforeCount = 0;
        $afterCount = 0;

        Client::beforeConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$beforeCount) {
            ++$beforeCount;
        });

        Client::afterConstructorHook(function ($instance) use (&$afterCount) {
            ++$afterCount;
        });

        new Client();

        $this->assertEquals(1, $beforeCount);
        $this->assertEquals(1, $afterCount);
    }

    public function testDestructorHooks()
    {
        $beforeCount = 0;
        $afterCount = 0;

        $this->client->before__destructHook(function ($instance) use (&$beforeCount) {
            ++$beforeCount;
        });

        $this->client->after__destructHook(function ($instance) use (&$afterCount) {
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

    /**
     * @param object $object
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callInaccessibleMethodWithArgs($object, $method, array $args)
    {
        $reflectionMethod = new \ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $args);
    }

    public function tearDown()
    {
        Client::resetStaticHooks();
    }
}
