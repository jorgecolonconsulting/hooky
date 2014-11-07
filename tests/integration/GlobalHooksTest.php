<?php
namespace _2UpMedia\Hooky\integration;

use _2UpMedia\Hooky\BaseTestCase;
use _2UpMedia\Hooky\fixtures\Client;

class GlobalHooksTest extends BaseTestCase
{
    public function testGlobalBeforeAllHooks()
    {
        $that = $this;

        $count = 0;

        Client::globalBeforeAllHook(function ($method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(3, $count);
    }

    public function testGlobalAfterAllHooks()
    {
        $that = $this;

        $count = 0;

        Client::globalAfterAllHook(function ($method) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
            $that->assertContains($method, ['getText', '_getText']);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(3, $count);
    }

    public function testGlobalAfterMethodHooks()
    {
        $that = $this;

        $count = 0;

        Client::globalAfterGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(3, $count);
    }

    public function testGlobalBeforeMethodHooks()
    {
        $that = $this;

        $count = 0;

        Client::globalBeforeGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(3, $count);
    }

    public function testGlobalOnceBeforeMethod()
    {
        $that = $this;

        $count = 0;

        Client::globalOnceBeforeGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(2, $count);
    }

    public function testGlobalOnceAfterMethod()
    {
        $that = $this;

        $count = 0;

        Client::globalOnceAfterGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(2, $count);
    }

    public function testGlobalOnceBeforeAll()
    {
        $that = $this;

        $count = 0;

        Client::globalOnceBeforeAllHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(2, $count);
    }

    public function testGlobalOnceAfterAll()
    {
        $that = $this;

        $count = 0;

        Client::globalOnceAfterAllHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(2, $count);
    }

    public function testCaching()
    {
        $that = $this;

        $count = 0;

        Client::globalBeforeGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        Client::globalBeforeGetTextHook(function ($resourceLocation) use ($that, &$count) {
            ++$count;

            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\Client', $this);
        });

        $resourceLocation = '/path/to/resource';

        $this->client->getText($resourceLocation);
        $this->client->getText($resourceLocation);

        (new Client())->getText($resourceLocation);

        $this->assertEquals(6, $count);
    }

    public function testGlobalBeforeConstructor()
    {
        $this->markTestIncomplete();
    }

    public function testGlobalAfterConstructor()
    {
        $this->markTestIncomplete();
    }

    public function tearDown()
    {
        Client::resetGlobalMethods();
    }
}
