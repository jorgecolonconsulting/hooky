<?php
namespace _2UpMedia\Hooky;

trait HooksTrait
{
    /**
     * [
     *      'before' => [],
     *      'after' => [],
     *      'once' => [
     *          'before' => [],
     *          'after' => []
     *      ]
     * ]
     *
     * @var array
     */
    protected $hooks = [];

    protected static $staticHooks = [];

    /**
     * @var bool
     */
    protected $onceBeforeCalled = false;

    /**
     * @var bool
     */
    protected $onceAfterCalled = false;

    /**
     * [
     *      'before' => [
     *          'getText' => false
     *      ],
     *      'after' => [
     *          'getText' => true
     *      ]
     * ]
     */
    protected $onceCalledMethods = [];

    /**
     * @var int based on ReflectionMethod::IS_* constants
     */
    protected $defaultAccessibility = 256;

    protected $hookableMethods = [];

    private $hooksNotRestricted = [];

    public static $debugMode = false;

    /**
     * @param $options Constants
     */
    protected function setDefaultAccessibility($options)
    {
        $this->defaultAccessibility = $options;
    }

    protected function setHookableMethods(array $methods)
    {
        $this->hookableMethods = $methods;
    }

    /**
     * Gets called every time before key methods
     *
     * @param callable $callable
     */
    public function beforeAllHook(callable $callable)
    {
        $this->hooks['before'][] = $callable;
    }

    /**
     * Gets called every time after key methods
     *
     * @param callable $callable
     */
    public function afterAllHook(callable $callable)
    {
        $this->hooks['after'][] = $callable;
    }

    /**
     * Gets called once before key methods
     *
     * I.E. setting up authentication
     *
     * @param callable $callable
     */
    public function onceBeforeAllHook(callable $callable)
    {
        $this->hooks['once']['before'][] = $callable;
    }

    public static function beforeConstructorHook(callable $callable)
    {
        self::$staticHooks['before'][] = $callable;
    }

    public static function afterConstructorHook(callable $callable)
    {
        self::$staticHooks['after'][] = $callable;
    }

    public static function resetStaticHooks()
    {
        self::$staticHooks = [];
    }

    /**
     * Gets called once after key methods
     *
     * I.E. setting up authentication
     *
     * @param callable $callable
     */
    public function onceAfterAllHook(callable $callable)
    {
        $this->hooks['once']['after'][] = $callable;
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @method void after{Method}(...)
     * @method void before{Method}(...)
     * @method void onceAfter{Method}(...)
     * @method void onceBefore{Method}(...)
     *
     * @method mixed callAfter{Method}(...)
     * @method mixed callBefore{Method}(...)
     * @method mixed callOnceAfter{Method}(...)
     * @method mixed callOnceBefore{Method}(...)
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $callableRegistrationTokens = [
            '^(after)(.*)(Hook)', '^(before)(.*)(Hook)', '^(onceAfter)(.*)(Hook)', '^(onceBefore)(.*)(Hook)'
        ];

        $callableInvokeTokens = [
            '^(call)(After.*)', '^(call)(Before.*)'
        ];

        $callableInvokeOnceTokens = [
            '^(call)(OnceAfter.*)', '^(call)(OnceBefore.*)'
        ];

        $callable = $arguments[0];
        if (isset($this->hooksNotRestricted[$method])) {
            $this->hooks[$this->hooksNotRestricted[$method]][] = $callable;

            return;
        } elseif (($matches = $this->matchesAny($callableRegistrationTokens, $method))!= false) {
            if (! empty($matches[2])) {
                $this->methodExists($matches[2]);
            }

            $targetMethod = lcfirst($matches[2]);

            $this->methodNotRestricted($targetMethod, $method);

            $hookName = $matches[1].$matches[2];

            $this->hooksNotRestricted[$method] = $hookName;

            $this->checkClosure($this, $callable, $targetMethod);

            $this->hooks[$hookName][] = $callable;

            return;
        } elseif (strpos($method, 'call') === false) {
            throw new \BadMethodCallException("There's a typo in $method. Can't properly set up hook.");
        }

        $extraArgs = [];
        $return = null;
        if (isset($arguments[2])) {
            $extraArgs = array_slice($arguments, 2);
        }

        if (($matches = $this->matchesAny($callableInvokeTokens, $method)) != false) {
            if (strpos($method, 'callAfter') !== false) {
                $return = $this->callAfterMethodHooks($callable, $arguments[1], $extraArgs);
            }

            if (strpos($method, 'callBefore') !== false) {
                $return = $this->callBeforeMethodHooks($callable, $arguments[1], $extraArgs);
            }
        }

        if (($matches = $this->matchesAny($callableInvokeOnceTokens, $method)) != false) {
            if (strpos($method, 'callOnceAfter') !== false) {
                $return = $this->callOnceAfterMethodHooks($callable, $arguments[1], $extraArgs);
            }

            if (strpos($method, 'callOnceBefore') !== false) {
                $return = $this->callOnceBeforeMethodHooks($callable, $arguments[1], $extraArgs);
            }
        }

        if ($return !== null) {
            return $return;
        }
    }

    /**
     * Calls any registered listeners for beforeAll, before*, and onceBefore*
     *
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callBeforeHooks($classInstance, $method, array $args = [])
    {
        $return = $this->callBeforeAllHooks($classInstance, $method);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callBeforeMethodHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callOnceBeforeHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callAfterHooks($classInstance, $method, array $args = [])
    {
        $return = $this->callAfterAllHooks($classInstance, $method);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callAfterMethodHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callOnceAfterHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callOnceBeforeHooks($classInstance, $method, array $args = [])
    {
        $return = $this->callOnceBeforeAllHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callOnceBeforeMethodHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callOnceAfterHooks($classInstance, $method, array $args = [])
    {
        $return = $this->callOnceAfterAllHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }

        $return = $this->callOnceAfterMethodHooks($classInstance, $method, $args);

        if ($return !== null) {
            return $return;
        }
    }

    protected function callBeforeConstructorHooks($classInstance, array $args = [])
    {
        if (isset(self::$staticHooks['before'])) {
            foreach (self::$staticHooks['before'] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, $args, null, false);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    protected function callAfterConstructorHooks($classInstance, array $args = [])
    {
        if (isset(self::$staticHooks['after'])) {
            foreach (self::$staticHooks['after'] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, $args, null, false);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callBeforeMethodHooks($classInstance, $method, array $args)
    {
        $method = $this->getCalledMethod($method);

        $beforeMethodCallableName = 'before'.ucfirst($method);

        if (isset($this->hooks[$beforeMethodCallableName])) {
            foreach ($this->hooks[$beforeMethodCallableName] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, $args, $method, false);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callAfterMethodHooks($classInstance, $method, array $args)
    {
        $method = $this->getCalledMethod($method);

        $afterMethodCallableName = 'after'.ucfirst($method);

        if (isset($this->hooks[$afterMethodCallableName])) {
            foreach ($this->hooks[$afterMethodCallableName] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, $args, $method, false);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callOnceBeforeMethodHooks($classInstance, $method, array $args)
    {
        $method = $this->getCalledMethod($method);

        $onceBeforeMethodName = 'onceBefore'.ucfirst($method);
        if (! isset($this->onceCalledMethods['before'][$onceBeforeMethodName])) {
            if (isset($this->hooks[$onceBeforeMethodName])) {
                foreach ($this->hooks[$onceBeforeMethodName] as $callable) {
                    try {
                        $return = $this->callCallable($classInstance, $callable, $args, $method, false);
                    } catch (CancelPropagationException $e) {
                        break;
                    }

                    if ($return !== null) {
                        return $return;
                    }
                }

                $this->onceCalledMethods['before'][$onceBeforeMethodName] = true;
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callOnceAfterMethodHooks($classInstance, $method, array $args)
    {
        $method = $this->getCalledMethod($method);

        $onceAfterMethodName = 'onceAfter'.ucfirst($method);
        if (! isset($this->onceCalledMethods['after'][$onceAfterMethodName])) {
            if (isset($this->hooks[$onceAfterMethodName])) {
                foreach ($this->hooks[$onceAfterMethodName] as $callable) {
                    try {
                        $return = $this->callCallable($classInstance, $callable, $args, $method, false);
                    } catch (CancelPropagationException $e) {
                        break;
                    }

                    if ($return !== null) {
                        return $return;
                    }
                }

                $this->onceCalledMethods['after'][$onceAfterMethodName] = true;
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     *
     * @return mixed
     */
    protected function callOnceBeforeAllHooks($classInstance, $method)
    {
        $method = $this->getCalledMethod($method);

        if (! $this->onceBeforeCalled) {
            if (isset($this->hooks['once']['before'])) {
                foreach ($this->hooks['once']['before'] as $callable) {
                    try {
                        $return = $this->callCallable($classInstance, $callable, [], $method);
                    } catch (CancelPropagationException $e) {
                        break;
                    }

                    if ($return !== null) {
                        return $return;
                    }
                }

                $this->onceBeforeCalled = true;
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     *
     * @return mixed returns a value if the callable returns something other than null
     */
    protected function callOnceAfterAllHooks($classInstance, $method)
    {
        $method = $this->getCalledMethod($method);

        if (! $this->onceAfterCalled) {
            if (isset($this->hooks['once']['after'])) {
                foreach ($this->hooks['once']['after'] as $callable) {
                    try {
                        $return = $this->callCallable($classInstance, $callable, [], $method);
                    } catch (CancelPropagationException $e) {
                        break;
                    }

                    if ($return !== null) {
                        return $return;
                    }
                }

                $this->onceAfterCalled = true;
            }
        }
    }

    /**
     * @param $classInstance
     * @param $method
     *
     * @return mixed
     */
    protected function callBeforeAllHooks($classInstance, $method)
    {
        // check $classInstance is the valid type of the class using the trait
        $method = $this->getCalledMethod($method);

        if (isset($this->hooks['before'])) {
            foreach ($this->hooks['before'] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, [], $method);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param $classInstance
     * @param $method
     * @return mixed
     */
    protected function callAfterAllHooks($classInstance, $method)
    {
        // check $classInstance is the valid type of the class using the trait
        $calledMethod = $this->getCalledMethod($method);

        if (isset($this->hooks['after'])) {
            foreach ($this->hooks['after'] as $callable) {
                try {
                    $return = $this->callCallable($classInstance, $callable, [], $calledMethod);
                } catch (CancelPropagationException $e) {
                    break;
                }

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param $classInstance
     * @param callable $callable               parameters sent to callable: ($classInstance, $method [, $args]) if
     *                                         $method string is sent internally, ($classInstance [, $args]) if $method
     *                                         string is not sent
     * @param array    $args
     * @param string   $method
     * @param boolean  $includeMethodParameter optional
     *
     * @return mixed
     */
    protected function callCallable(
        $classInstance,
        callable $callable,
        array $args = [],
        $method = null,
        $includeMethodParameter = true
    ) {
        $preArguments = $method && $includeMethodParameter ? array($classInstance, $method) : array($classInstance);

        return call_user_func_array($callable, array_merge($preArguments, $args));
    }

    protected function checkClosure(
        $classInstance,
        callable $callable,
        $method = null
    ) {
        // if there are args and debug mode is on
        if ($method && self::$debugMode) {

            $originalMethodReflection = new \ReflectionMethod($classInstance, $method);

            if ($originalMethodReflection->getNumberOfParameters() === 0) {
                return;
            }

            $originalMethodReflectionParameters = $originalMethodReflection->getParameters();

            if (is_array($callable)) {
                $callableReflection = new \ReflectionMethod($callable[0], $callable[1]);
                $callableReflectionParameters = $callableReflection->getParameters();
            } elseif ($callable instanceof \Closure) {
                $callableReflection = new \ReflectionFunction($callable);
                $callableReflectionParameters = $callableReflection->getParameters();
            }

            $callableParameters = [];
            $originalParameters = [];

            foreach ($originalMethodReflectionParameters as $parameter) {
                $originalParameters[] = $parameter->getName();
            }

            foreach ($callableReflectionParameters as $parameter) {
                $callableParameters[] = $parameter->getName();
            }

            $parameterOffset = 0;

            // remove default parameters
            if ($method && $callableParameters) {
                $callableParameters = array_slice($callableParameters, 1);

                $parameterOffset = 1;
            }

            /**
             * [
             *      0 => [
             *          'original' => 'resourceLocation'
             *          'callable' => 'uri'
             *          'dirty' => true
             *      ],
             *
             *      1 => [
             *          'original' => 'message'
             *          'callable' => null
             *          'missing' => true
             */

            $parameterMeta = array_map(
                function ($originalParameter, $callableParameter) {
                    return ['original' => $originalParameter, 'callable' => $callableParameter];
                },
                $originalParameters,
                $callableParameters
            );

            $parameterDiff = array_filter(
                $parameterMeta,
                function ($item) {
                    return $item['original'] !== $item['callable'];
                }
            ); // remove matching fields

            if ($parameterDiff) {
                $errorBuffer = [];

                // issue a warning error if the parameters are named different
                foreach ($parameterDiff as $key => $parameter) {
                    $originalPosition = $key + 1;
                    $callablePosition = $key + 1 + $parameterOffset;
                    if ($parameter['original'] === null && $parameter['callable'] !== null) {
                        $errorBuffer[] = "Callable argument {$callablePosition} '{$parameter['callable']}' does not "
                            ."exist in the original {$method}() method as argument {$originalPosition}";
                    } elseif ($parameter['original'] !== null && $parameter['callable'] === null) {
                        $errorBuffer[] = "Callable argument {$callablePosition} exists in the original {$method}() "
                            ."method as argument {$originalPosition} but is omitted in the callable";
                    } elseif ($parameter['original'] !== $parameter['callable']) {
                        $errorBuffer[] = "Callable argument {$callablePosition} '{$parameter['callable']}' is named "
                            ."'{$parameter['original']}' in the original {$method}() method as argument "
                            ."{$originalPosition}";
                    }
                }

                $message = implode("\n", $errorBuffer);

                trigger_error($message, E_USER_WARNING);
            }
        }
    }

    /**
     * @param $regexes
     * @param $subject
     *
     * @return int
     */
    private function matchesAny(array $regexes, $subject)
    {
        $matches = null;
        foreach ($regexes as $regex) {
            if (! in_array(preg_match("/$regex/", $subject, $matches), [0, false])) {
                return $matches;
            }
        }
    }

    /**
     * @param $method
     *
     * @return mixed
     */
    private function getCalledMethod($method)
    {
        if (strpos($method, '::') !== false) {
            $methodChunks = explode('::', $method);
            $method = $methodChunks[1];
        }

        return $method;
    }

    /**
     * @param $methodName
     *
     * @return bool
     */
    private function methodExists($methodName)
    {
        if (! method_exists($this, $methodName)) {
            throw new \BadMethodCallException("$methodName doesn't exist");
        }

        return true;
    }

    private function methodNotRestricted($method, $callingMethod = null)
    {
        $method = $this->getCalledMethod($method);

        $reflectionMethod = new \ReflectionMethod($this, $method);

        $visibility = $reflectionMethod->getModifiers();

        if ($callingMethod) {
            $message = "$method method called from $callingMethod is restricted by hooky options";
        } else {
            $message = "$method method is restricted by hooky options";
        }

        if ($this->hookableMethods && ! in_array($method, $this->hookableMethods)) {
            throw new \BadMethodCallException($message);
        }

        // check if this is an interface method

        if ($visibility & $this->defaultAccessibility) {
            return true;
        }

        throw new \BadMethodCallException($message);
    }
}
