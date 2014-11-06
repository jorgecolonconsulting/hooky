<?php
namespace _2UpMedia\Hooky\Integration;

//require_once __DIR__.'/../fixtures/Client.php'; // TODO: figure out how to remove this without losing code coverage

use _2UpMedia\Hooky\Fixtures\InterfacedClass;
use _2UpMedia\Hooky\Constants;
use _2UpMedia\Hooky\Fixtures\ConcreteAbstractClass;
use _2UpMedia\Hooky\Fixtures\SubclassedConcreteAbstract;

use _2UpMedia\Hooky\BaseTestCase;

class AbstractedMethodsTest extends BaseTestCase
{
    protected $interfacedClass;
    protected $concreteAbstract;

    public function setUp()
    {
        $this->interfacedClass = new InterfacedClass();
        $this->concreteAbstract = new ConcreteAbstractClass();
        $this->subclassedConcreteAbstract = new SubclassedConcreteAbstract;
    }

    public function testInterfaceConcreteMethod()
    {
        $that = $this;

        $this->interfacedClass->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\InterfacedClass', $this);
        });
    }

    public function testAbstractConcreteMethod()
    {
        $that = $this;

        $this->concreteAbstract->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\InterfacedClass', $this);
        });
    }

    public function testSubclassedAbstractConcreteMethod()
    {
        $that = $this;

        $this->subclassedConcreteAbstract->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\Fixtures\InterfacedClass', $this);
        });
    }
}
 