<?php
namespace _2UpMedia\Hooky\Fixtures;

class Client {
    use \_2UpMedia\Hooky\HooksTrait;

    public function __construct($parameterOne = 'one', $parameterTwo = 'two')
    {
        $this->callBeforeConstructorHooks($this, [$parameterOne, $parameterTwo]);
        $this->callAfterConstructorHooks($this, [$parameterOne, $parameterTwo]);

        self::$debugMode = true;
    }

    public function getText($resourceLocation)
    {
        if (($return = $this->callBeforeHooks($this, __METHOD__, [&$resourceLocation])) !== null) return $return;

        $coreReturn = $this->_getText($resourceLocation);

        if (($return = $this->callAfterHooks($this, __METHOD__, [&$resourceLocation])) !== null) return $return;

        return $coreReturn;
    }

    protected function _getText($resourceLocation){
        if (($return = $this->callOnceBefore_getTextHooks($this, __METHOD__)) !== null) return $return;

        if (($return = $this->callBefore_getTextHooks($this, __METHOD__)) !== null) return $return;

        $coreReturn = $resourceLocation;

        if (($return = $this->callAfter_getTextHooks($this, __METHOD__)) !== null) return $return;

        if (($return = $this->callOnceAfter_getTextHooks($this, __METHOD__)) !== null) return $return;

        return $coreReturn;
    }

    private function privateMethod()
    {
        $this->callAfterHooks($this, __METHOD__);
    }

    public function publicMethod()
    {
        $this->privateMethod();
    }

    public function __destruct()
    {
        $this->callBefore__destructHooks($this, __METHOD__);
        $this->callAfter__destructHooks($this, __METHOD__);
    }
}
 