<?php
namespace _2UpMedia\Hooky\Integration;

use _2UpMedia\Hooky\Fixtures\Client;
use _2UpMedia\Hooky\CancelPropagationException;
use _2UpMedia\Hooky\Constants;

use _2UpMedia\Hooky\BaseTestCase;

class GenericTest extends BaseTestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client();
    }

    public function testRestrictEarlyReturn()
    {
        $this->markTestIncomplete();
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage Callable argument 2 'nonexistantParameter' does not exist in the original getText()
     *                           method as argument 2
     */
    public function testMissingCallableParameters()
    {
        $this->setPublicAccessible();

        $this->client->beforeGetTextHook(function ($resourceLocation, $nonexistantParameter) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage Callable argument 1 exists in the original getText() method as argument 1 but is
     *                           omitted in the callable
     */
    public function testMissingOriginalMethodParameters()
    {
        $this->setPublicAccessible();

        $this->client->beforeGetTextHook(function () {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage Callable argument 1 'uri' is named 'resourceLocation' in the original getText() method
     *                           as argument 1
     */
    public function testMismatchingParameters()
    {
        $this->setPublicAccessible();

        $this->client->beforeGetTextHook(function ($uri) {
        });

        $this->client->getText('/path/to/resource');
    }

    public function testCancelPropagation()
    {
        $this->setPublicAccessible();

        $that = $this;

        $beforeAllCount = 0;
        $this->client->beforeAllHook(function ($method) use ($that, &$beforeAllCount) {
            ++$beforeAllCount;

            throw new CancelPropagationException();
        });

        $this->client->beforeAllHook(function ($method) use ($that, &$beforeAllCount) {
            ++$beforeAllCount;
        });

        $beforeMethodCount = 0;
        $this->client->beforeGetTextHook(function ($resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->beforeGetTextHook(function ($resourceLocation) use (&$beforeMethodCount) {
            ++$beforeMethodCount;
        });

        $onceBeforeAllCount = 0;
        $this->client->onceBeforeAllHook(function ($resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeAllHook(function ($resourceLocation) use (&$onceBeforeAllCount) {
            ++$onceBeforeAllCount;
        });

        $onceBeforeMethodCount = 0;
        $this->client->onceBeforeGetTextHook(function ($resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceBeforeGetTextHook(function ($resourceLocation) use (&$onceBeforeMethodCount) {
            ++$onceBeforeMethodCount;
        });

        $afterAllCount = 0;
        $this->client->afterAllHook(function ($method) use ($that, &$afterAllCount) {
            ++$afterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->afterAllHook(function ($method) use ($that, &$afterAllCount) {
            ++$afterAllCount;
        });

        $afterMethodCount = 0;
        $this->client->afterGetTextHook(function ($resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->afterGetTextHook(function ($resourceLocation) use (&$afterMethodCount) {
            ++$afterMethodCount;
        });

        $onceAfterMethodCount = 0;
        $this->client->onceAfterGetTextHook(function ($resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterGetTextHook(function ($resourceLocation) use (&$onceAfterMethodCount) {
            ++$onceAfterMethodCount;
        });

        $onceAfterAllCount = 0;
        $this->client->onceAfterAllHook(function () use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;

            throw new CancelPropagationException();
        });

        $this->client->onceAfterAllHook(function () use (&$onceAfterAllCount) {
            ++$onceAfterAllCount;
        });

        $beforeConstructorCount = 0;
        Client::beforeConstructorHook(
            function ($parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
                ++$beforeConstructorCount;

                throw new CancelPropagationException();
            }
        );

        Client::beforeConstructorHook(
            function ($parameterOne, $parameterTwo) use (&$beforeConstructorCount) {
                ++$beforeConstructorCount;
            }
        );

        $afterConstructorCount = 0;
        Client::afterConstructorHook(
            function ($parameterOne, $parameterTwo) use (&$afterConstructorCount) {
                ++$afterConstructorCount;

                throw new CancelPropagationException();
            }
        );

        Client::afterConstructorHook(function ($parameterOne, $parameterTwo) use (&$afterConstructorCount) {
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

    public function testBeforeEarlyReturn()
    {
        $that = $this;

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $beforeMethodCount = 0;
        $client->beforeGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            ++$beforeMethodCount;

            return true;
        });

        $client->beforeGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            ++$beforeMethodCount;
        });

        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $beforeMethodCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $beforeAllCount = 0;
        $client->beforeAllHook(function() use ($that, &$beforeAllCount) {
            ++$beforeAllCount;

            return true;
        });

        $client->beforeAllHook(function() use ($that, &$beforeAllCount) {
            ++$beforeAllCount;
        });

        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $beforeAllCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceBeforeAllCount = 0;
        $client->onceBeforeAllHook(function() use ($that, &$onceBeforeAllCount) {
            ++$onceBeforeAllCount;

            return true;
        });

        $client->onceBeforeAllHook(function() use ($that, &$onceBeforeAllCount) {
            ++$onceBeforeAllCount;
        });

        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceBeforeAllCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceBeforeGetText = 0;
        $client->onceBeforeGetTextHook(function($resourceLocation) use ($that, &$onceBeforeGetText) {
            ++$onceBeforeGetText;

            return true;
        });

        $client->onceBeforeGetTextHook(function($resourceLocation) use ($that, &$onceBeforeGetText) {
            ++$onceBeforeGetText;
        });

        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$beforeMethodCount) {
            $that->fail("Wasn't supposed to get called");
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceBeforeGetText);
    }

    public function testAfterEarlyReturn()
    {
        $that = $this;

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $afterMethodCount = 0;
        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$afterMethodCount) {
            ++$afterMethodCount;

            return true;
        });

        $client->afterGetTextHook(function ($resourceLocation) use ($that, &$afterMethodCount) {
            ++$afterMethodCount;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $afterMethodCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $afterProtectedMethodCount = 0;
        $client->after_getTextHook(function ($resourceLocation) use ($that, &$afterProtectedMethodCount) {
            ++$afterProtectedMethodCount;

            return true;
        });

        $client->after_getTextHook(function ($resourceLocation) use ($that, &$afterProtectedMethodCount) {
            ++$afterProtectedMethodCount;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $afterProtectedMethodCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $afterAllCount = 0;
        $client->afterAllHook(function() use ($that, &$afterAllCount) {
            ++$afterAllCount;

            return true;
        });

        $client->afterAllHook(function() use ($that, &$afterAllCount) {
            ++$afterAllCount;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $afterAllCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceAfterAllCount = 0;
        $client->onceAfterAllHook(function() use ($that, &$onceAfterAllCount) {
            ++$onceAfterAllCount;

            return true;
        });

        $client->onceAfterAllHook(function() use ($that, &$onceAfterAllCount) {
            ++$onceAfterAllCount;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceAfterAllCount);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceAfterGetText = 0;
        $client->onceAfterGetTextHook(function($resourceLocation) use ($that, &$onceAfterGetText) {
            ++$onceAfterGetText;

            return true;
        });

        $client->onceAfterGetTextHook(function($resourceLocation) use ($that, &$onceAfterGetText) {
            ++$onceAfterGetText;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceAfterGetText);
    }

    public function testEarlyReturnWithMethodHooks()
    {
        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceAfterGetText = 0;
        $client->onceAfter_getTextHook(function($resourceLocation) use (&$onceAfterGetText) {
            ++$onceAfterGetText;

            return true;
        });
        $client->onceAfter_getTextHook(function($resourceLocation) use (&$onceAfterGetText) {
            ++$onceAfterGetText;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceAfterGetText);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $afterGetText = 0;
        $client->after_getTextHook(function($resourceLocation) use (&$afterGetText) {
            ++$afterGetText;

            return true;
        });
        $client->after_getTextHook(function($resourceLocation) use (&$afterGetText) {
            ++$afterGetText;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $afterGetText);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $beforeGetText = 0;
        $client->before_getTextHook(function($resourceLocation) use (&$beforeGetText) {
            ++$beforeGetText;

            return true;
        });
        $client->before_getTextHook(function($resourceLocation) use (&$beforeGetText) {
            ++$beforeGetText;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $beforeGetText);

        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceBeforeGetText = 0;
        $client->onceBefore_getTextHook(function($resourceLocation) use (&$onceBeforeGetText) {
            ++$onceBeforeGetText;

            return true;
        });
        $client->onceBefore_getTextHook(function($resourceLocation) use (&$onceBeforeGetText) {
            ++$onceBeforeGetText;
        });

        $this->assertTrue($client->getText('/path/to/resource'));
        $this->assertEquals(1, $onceBeforeGetText);
    }

    public function testEarlyReturnWithNull()
    {
        $client = new Client();
        $this->setPublicAndProtectedAccessibility($client);

        $onceAfterGetText = 0;
        $client->onceAfterGetTextHook(function($resourceLocation) use (&$onceAfterGetText) {
            ++$onceAfterGetText;

            return Constants::NULL;
        });

        $this->assertNull($client->getText('/path/to/resource'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method is restricted by hooky options
     */
    public function testMethodNotRestrictedWithDefaults()
    {
        $this->setPublicAccessible();

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
     * @expectedExceptionMessage getText method called from  is restricted by hooky options. Must be implemented from
     *                           an interface or abstract method
     */
    public function testMethodNotRestrictedWithPublicAndIsAbstractAccessibility()
    {
        $this->callInaccessibleMethodWithArgs(
            $this->client,
            'setDefaultAccessibility',
            [Constants::PUBLIC_ACCESSIBLE | Constants::ABSTRACT_ACCESSIBLE]
        );

        $this->callInaccessibleMethodWithArgs($this->client, 'methodNotRestricted', ['getText']);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from after_getTextHook is restricted by hooky options
     */
    public function testSetHookableMethods()
    {
        $this->setPublicAccessible();

        $this->callInaccessibleMethodWithArgs($this->client, 'setHookableMethods', [['getText']]);

        $that = $this;

        $this->client->afterGetTextHook(function ($resourceLocation) use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\Client', $this);
            $that->assertEquals('/path/to/resource', $resourceLocation);
        });

        $this->client->getText('/path/to/resource');

        $this->client->after_getTextHook(function ($resourceLocation, &$return) use ($that) {
            $that->fail("This shouldn't have been called");
        });
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage privateMethod method called from beforePrivateMethodHook is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallPrivateMethod()
    {
        $this->setPublicAccessible();

        $that = $this;

        $this->client->beforeGetTextHook(function ($resourceLocation) use ($that) {
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
        $this->client->beforeNonexistantMethodHook(function ($resourceLocation) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage There's a typo in beforGetTextHook. Can't properly set up hook.
     */
    public function testInstanceMagicMethodTypo()
    {
        $this->client->beforGetTextHook(function ($resourceLocation) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage There's a typo in globalBeforGetTextHook. Can't properly set up hook.
     */
    public function testGlobalMagicMethodTypo()
    {
        Client::globalBeforGetTextHook(function ($resourceLocation) {
        });

        $this->client->getText('/path/to/resource');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage _getText method called from before_getTextHook is restricted by hooky options
     */
    public function testDefaultAccessibilityAndCallProtectedMethod()
    {
        $this->setPublicAccessible();

        $that = $this;

        $this->client->beforeGetTextHook(function ($resourceLocation) use ($that) {
        });

        $this->client->before_getTextHook(function ($resourceLocation) use ($that) {
            $that->fail("This shouldn't be accessible");
        });

        $this->client->getText('/path/to/resource');
    }
}