<?php
namespace _2UpMedia\Hooky\Integration;

require_once __DIR__.'/../fixtures/Client.php'; // TODO: figure out how to remove this without losing code coverage

use _2UpMedia\Hooky\Fixtures\Client;
use _2UpMedia\Hooky\Hooks;
use _2UpMedia\Hooky\CancelPropagationException;

class HooksTraitTest extends \PHPUnit_Framework_TestCase {

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
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;

        $this->client->before_getText(function ($instance) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        });

        $this->client->beforeGetText(array($this, 'getBeforeGetTextCallable'));

        $this->client->beforeGetText(function ($instance, $resourceLocation) use ($that) {
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
     * @param $that
     * @return string
     */
    function getBeforeGetTextCallable($instance, &$resourceLocation)
    {
        $resourceLocation = '/path/to/resource1';

        $this->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        $this->assertEquals('/path/to/resource1', $resourceLocation);
        return $resourceLocation;
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 3 'nonexistantParameter' does not
     *                           exist in original getText() method as argument 2
     */
    public function testMissingCallableParameters()
    {
        $this->client->beforeGetText(function ($instance, $resourceLocation, $nonexistantParameter) {});

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 2 exists in the original getText() method
     *                           as argument 1 but is omitted in the callable
     */
    public function testMissingOriginalMethodParameters()
    {
        $this->client->beforeGetText(function ($instance) {});

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     * @expectedExceptionMessage Callable argument 2 'uri' is named
     *                           'resourceLocation' in the original getText() method as argument 1
     */
    public function testMismatchingParameters()
    {
        $this->client->beforeGetText(function ($instance, $uri) {});

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

        $this->client->beforeGetText(function ($instance, $resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->beforeGetText(function ($instance, $resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;
        });

        $this->client->onceBeforeAllHook(function ($instance, $resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeAllHook(function ($instance, $resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;
        });

        $this->client->onceBeforeGetText(function ($instance, $resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeGetText(function ($instance, $resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that, &$afterAllCount) {
            ++$afterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->afterAllHook(function ($instance, $method) use ($that, &$afterAllCount) {
            ++$afterAllCount;
        });

        $this->client->afterGetText(function ($instance, $resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->afterGetText(function ($instance, $resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;
        });

        $this->client->onceAfterGetText(function ($instance, $resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterGetText(function ($instance, $resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;
        });

        $this->client->onceAfterAllHook(function ($instance) use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterAllHook(function ($instance) use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;
        });

        Client::beforeConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
            ++$beforeConstructorCount;

            throw new CancelPropagationException();
        });

        Client::beforeConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
            ++$beforeConstructorCount;
        });

        Client::afterConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$afterConstructorCount) {
            ++$afterConstructorCount;

            throw new CancelPropagationException();
        });

        Client::afterConstructorHook(function ($instance, $parameterOne, $parameterTwo) use (&$afterConstructorCount) {
            ++$afterConstructorCount;
        });

        new Client();

        $this->client->getText('/path/to/resource');

        $this->assertEquals($beforeAllCount, 1);
        $this->assertEquals($beforeMethodCount, 1);
        $this->assertEquals($onceBeforeMethodCount, 1);

        $this->assertEquals($afterAllCount, 1);
        $this->assertEquals($afterMethodCount, 1);
        $this->assertEquals($onceAfterMethodCount, 1);

        $this->assertEquals($beforeConstructorCount, 1);
        $this->assertEquals($afterConstructorCount, 1);
    }

    public function testBeforeAllEarlyReturn()
    {
        $that = $this;
        $count = 0;

        $this->client->beforeGetText(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);

            return true;
        });

        $this->client->beforeGetText(function ($instance, $resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->afterGetText(function ($instance, $resourceLocation) use ($that, &$count) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->client->getText('/path/to/resource');

        $this->assertEquals($count, 1);
    }

    public function testOnceBeforePublicMethod()
    {
        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBeforeGetText(function ($instance, $resourceLocation) use ($that, &$count) {
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
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->onceBefore_getText(function ($instance) use ($that, &$count) {
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

        $this->client->onceAfterGetText(function ($instance, &$resourceLocation) use ($that, &$count) {
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
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->after_getText(function ($instance) use ($that, &$count) {
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
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE | Hooks::PRIVATE_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;
        $count = 0;

        $this->client->afterPrivateMethod(function ($instance) use ($that, &$count) {
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
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE]
        );

        /**
         * var self
         */
        $that = $this;

        $this->client->after_getText(function ($instance) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
        });

        $this->client->afterGetText(function ($instance, &$resourceLocation) use ($that) {
            $resourceLocation = '/path/to/resource1';

            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource1', $resourceLocation);
        });

        $this->client->afterGetText(function ($instance, $resourceLocation) use ($that) {
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
        $this->callInaccessibleMethodWithArgs($this->client, 'setDefaultAccessibility', [Hooks::PROTECTED_ACCESSIBLE]);

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
        $this->assertFalse($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
    }

    public function testMethodNotRestrictedWithProtectedAndPublicAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE]
        );

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
    }

    public function testMethodNotRestrictedWithPublicProtectedAndPrivateAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Hooks::PUBLIC_ACCESSIBLE | Hooks::PROTECTED_ACCESSIBLE | Hooks::PRIVATE_ACCESSIBLE]
        );

        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']));
        $this->assertTrue($this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['_getText']));
        $this->assertTrue(
            $this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['privateMethod'])
        );
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from after_getText is restricted by hooky options
     */
    public function testSetHookableMethods()
    {
        $this->callInaccessibleMethodWithArgs($this->client, 'setHookableMethods', [['getText']]);

        $that = $this;

        $this->client->afterGetText(function ($instance, $resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $instance);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->getText('/path/to/resource');

        $this->client->after_getText(function ($instance, $resourceLocation, &$return) use ($that) {
            $that->fail("This shouldn't have been called");
        });
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from before_getText is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallProtectedMethod()
    {
        $that = $this;

        $this->client->beforeGetText(function () use ($that) {
        });

        $this->client->before_getText(function () use ($that) {
            $that->fail("This shouldn't be accessible");
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage privateMethod method called from beforePrivateMethod is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallPrivateMethod()
    {
        $that = $this;

        $this->client->beforeGetText(function () use ($that) {
        });

        $this->client->beforePrivateMethod(function () use ($that) {
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
        $this->client->beforeNonexistantMethod(function ($instance, $resourceLocation) {
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
            $args = func_get_args();
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

        $this->client->before__destruct(function ($instance) use (&$beforeCount) {
            ++$beforeCount;
        });

        $this->client->after__destruct(function ($instance) use (&$afterCount) {
            ++$afterCount;
        });

        unset($this->client);

        $this->assertEquals(1, $beforeCount);
        $this->assertEquals(1, $afterCount);
    }

    public function testNoCallablesSetUp()
    {
        $client = new Client();

        $this->assertEquals('/path/to/resource' ,$this->client->getText('/path/to/resource'));
    }

    /**
     * @param object $object
     * @param string $method
     * @param array $args
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
