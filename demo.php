<?php
require 'vendor/autoload.php';

class HelloWorld
{
    use _2UpMedia\Hooky\HooksTrait;

    public function sayIt()
    {
        $this->callBeforeHooks($this, __METHOD__);
    }
}

HelloWorld::globalBeforeAllHook(function() {
    echo 'hello world';
});

(new HelloWorld())->sayIt();
