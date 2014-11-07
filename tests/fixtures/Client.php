<?php
namespace _2UpMedia\Hooky\fixtures;

class Client
{
    use \_2UpMedia\Hooky\HooksTrait { __destruct as mainDestruct; }

    public function __construct($parameterOne = 'one', $parameterTwo = 'two')
    {
        $this->callBeforeConstructorHooks($this, [$parameterOne, $parameterTwo]);
        $this->callAfterConstructorHooks($this, [$parameterOne, $parameterTwo]);

        self::$debugMode = true;
    }

    public function getText($resourceLocation)
    {
        if (($hookReturn = $this->callBeforeHooks($this, __METHOD__, [&$resourceLocation])) !== null) {
            return $hookReturn;
        }

        $return = $this->_getText($resourceLocation);

        if (($hookReturn = $this->callAfterHooks($this, __METHOD__, [&$resourceLocation, $return])) !== null) {
            return $this->hookReturn($hookReturn); // allowing the return of nulls is optional
        }

        return $return;
    }

    protected function _getText($resourceLocation)
    {
        if (($hookReturn = $this->callOnceBeforeHooks($this, __METHOD__, [$resourceLocation])) !== null) {
            return $hookReturn;
        }

        if (($hookReturn = $this->callBeforeMethodHooks($this, __METHOD__, [$resourceLocation])) !== null) {
            return $hookReturn;
        }

        $return = $resourceLocation;

        if (($hookReturn = $this->callAfterMethodHooks($this, __METHOD__, [$resourceLocation])) !== null) {
            return $hookReturn;
        }

        if (($hookReturn = $this->callOnceAfterMethodHooks($this, __METHOD__, [$resourceLocation])) !== null) {
            return $hookReturn;
        }

        return $return;
    }

    private function privateMethod()
    {
        $this->callAfterHooks($this, __METHOD__);
    }

    public function CapitalCasedMethod()
    {
        $this->callBeforeHooks($this, __METHOD__);
    }

    public function publicMethod()
    {
        $this->privateMethod();
    }

    public function __destruct()
    {
        $this->mainDestruct();

        $this->callBeforeMethodHooks($this, __METHOD__);
        $this->callAfterMethodHooks($this, __METHOD__);
    }
}
