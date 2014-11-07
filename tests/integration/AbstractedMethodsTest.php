<?php
namespace _2UpMedia\Hooky\integration;

use _2UpMedia\Hooky\fixtures\InterfacedClass;
use _2UpMedia\Hooky\fixtures\ConcreteAbstractClass;
use _2UpMedia\Hooky\fixtures\SubclassedConcreteAbstract;

use _2UpMedia\Hooky\BaseTestCase;

class AbstractedMethodsTest extends BaseTestCase
{
    protected $interfacedClass;
    protected $concreteAbstract;

    public function setUp()
    {
        $this->interfacedClass = new InterfacedClass();
        $this->concreteAbstract = new ConcreteAbstractClass();
        $this->subclassedConcreteAbstract = new SubclassedConcreteAbstract();
    }

    public function testInterfaceConcreteMethod()
    {
        $that = $this;

        $this->interfacedClass->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\InterfacedClass', $this);
        });
    }

    public function testAbstractConcreteMethod()
    {
        $that = $this;

        $this->concreteAbstract->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\InterfacedClass', $this);
        });
    }

    public function testSubclassedAbstractConcreteMethod()
    {
        $that = $this;

        $this->subclassedConcreteAbstract->beforeTestHook(function () use ($that) {
            $that->assertInstanceOf('_2UpMedia\Hooky\fixtures\InterfacedClass', $this);
        });
    }
}
