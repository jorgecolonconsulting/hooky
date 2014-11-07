<?php
namespace _2UpMedia\Hooky;

trait HooksTrait
{
    private $hooks = [
        'beforeAll' => [],
        'afterAll' => [],
        'once' => [
           'beforeAll' => [],
           'afterAll' => []
        ]
    ];

    private static $staticHooks = [
        'instance' => [],
        'global' => [
            'beforeAll' => [],
            'afterAll' => [],
            'once' => [
                'beforeAll' => [],
                'afterAll' => []
            ]
        ],
    ];

    /**
     * @var array read-only. DO NOT write to this property.
     */
    private static $staticHooksDefault = [
        'instance' => [],
        'global' => [
            'beforeAll' => [],
            'afterAll' => [],
            'once' => [
                'beforeAll' => [],
                'afterAll' => []
            ]
        ],
    ];

    private $onceBeforeCalled = false;

    private $onceAfterCalled = false;

    private $staticOnceAfterCalled = false;

    private $staticOnceBeforeCalled = false;

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
    private $onceCalledMethods = [];

    private $staticOnceCalledMethods = [];

    /**
     * @var int based on Constants::* constants. Public interface and abstract method accessibility is default.
     */
    private static $defaultAccessibility = 264;

    private static $hookableMethods = [];

    private $hooksNotRestricted = [];

    private static $staticHooksNotRestricted = [];

    public static $debugMode = false;

    /**
     * Set bitewise options as such
     *
     * <code>
     *  // allows hooking to public and protected methods
     *  $this->setDefaultAccessibility(Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE);
     * </code>
     *
     * @param $options Constants
     */
    protected function setDefaultAccessibility($options)
    {
        self::$defaultAccessibility = $options;
    }

    protected function setHookableMethods(array $methods)
    {
        self::$hookableMethods = $methods;
    }

    /**
     * @param callable $callable
     */
    public static function globalAfterAllHook(callable $callable)
    {
        self::$staticHooks['global']['afterAll'][] = $callable;
    }

    /**
     * @param callable $callable
     */
    public static function globalBeforeAllHook(callable $callable)
    {
        self::$staticHooks['global']['beforeAll'][] = $callable;
    }

    /**
     * @param callable $callable
     */
    public static function globalOnceAfterAllHook(callable $callable)
    {
        self::$staticHooks['global']['once']['afterAll'][] = $callable;
    }

    /**
     * @param callable $callable
     */
    public static function globalOnceBeforeAllHook(callable $callable)
    {
        self::$staticHooks['global']['once']['beforeAll'][] = $callable;
    }

    /**
     * Gets called every time before key methods
     *
     * @param callable $callable
     */
    public function beforeAllHook(callable $callable)
    {
        $this->hooks['beforeAll'][] = $callable;
    }

    /**
     * Gets called every time after key methods
     *
     * @param callable $callable
     */
    public function afterAllHook(callable $callable)
    {
        $this->hooks['afterAll'][] = $callable;
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
        $this->hooks['once']['beforeAll'][] = $callable;
    }

    /**
     * TODO: See if there's a ingenuous way to associate a constructor hook to one instantiated class
     *
     * @param callable $callable
     */
    public static function beforeConstructorHook(callable $callable)
    {
        self::$staticHooks['instance']['beforeConstructor'][] = $callable;
    }

    public static function afterConstructorHook(callable $callable)
    {
        self::$staticHooks['instance']['afterConstructor'][] = $callable;
    }

    public static function resetStaticConstructorHooks()
    {
        self::$staticHooks['instance'] = [];
    }

    public static function resetHookableMethods()
    {
        self::$hookableMethods = [];
    }

    public static function resetGlobalMethods()
    {
        self::$staticHooks['global'] = self::$staticHooksDefault['global'];
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
        $this->hooks['once']['afterAll'][] = $callable;
    }

    /**
     * Registers and calls dynamic method hooks
     *
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
        $this->registerMethodHook($method, $arguments[0]);
    }

    public static function __callStatic($method, $arguments)
    {
        self::registerGlobalMethodHook($method, $arguments[0]);
    }

    private static function registerGlobalMethodHook($method, $callable)
    {
        $callableRegistrationTokens = [
            '^global(After)(.*)(Hook)',
            '^global(Before)(.*)(Hook)',
            '^global(OnceAfter)(.*)(Hook)',
            '^global(OnceBefore)(.*)(Hook)'
        ];

        if (isset(self::$staticHooksNotRestricted[$method])) {
            self::$staticHooks['global'][self::$staticHooksNotRestricted[$method]][] = $callable;

            return true;
        } elseif (($matches = self::matchesAny($callableRegistrationTokens, $method)) != false) {
            if ( ! empty($matches[2])) {
                self::methodExists($matches[2]);
            }

            $targetMethod = lcfirst($matches[2]);

            self::methodNotRestricted($targetMethod, $method);

            $hookName = lcfirst($matches[1].$matches[2]);

            self::$staticHooksNotRestricted[$method] = $hookName;

            self::checkClosure(get_called_class(), $callable, $targetMethod);

            self::$staticHooks['global'][$hookName][] = $callable;

            return true;
        } elseif (strpos($method, 'call') === false) {
            throw new \BadMethodCallException("There's a typo in $method. Can't properly set up hook.");
        }
    }

    /**
     * Calls any registered listeners for beforeAll, before*, and onceBefore*
     *
     * @param object $classInstance
     * @param string $method
     * @param array  $args
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
     * @param array  $args
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
     * @param array  $args
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
     * @param array  $args
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
        if (isset(self::$staticHooks['instance']['beforeConstructor'])) {
            foreach (self::$staticHooks['instance']['beforeConstructor'] as $callable) {
                try {
                    $this->callCallable($classInstance, $callable, $args, null, false);
                } catch (CancelPropagationException $e) {
                    break;
                }
            }

            unset(self::$staticHooks['instance']['beforeConstructor']);
        }
    }

    protected function callAfterConstructorHooks($classInstance, array $args = [])
    {
        if (isset(self::$staticHooks['instance']['afterConstructor'])) {
            foreach (self::$staticHooks['instance']['afterConstructor'] as $callable) {
                try {
                    $this->callCallable($classInstance, $callable, $args, null, false);
                } catch (CancelPropagationException $e) {
                    break;
                }
            }

            unset(self::$staticHooks['instance']['afterConstructor']);
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function callBeforeMethodHooks($classInstance, $method, array $args = [])
    {
        $method = self::getCalledMethod($method);

        $beforeMethodCallableName = 'before'.ucfirst($method);

        $hooks = isset($this->hooks[$beforeMethodCallableName]) ? $this->hooks[$beforeMethodCallableName] : [];

        $staticHooks = isset(self::$staticHooks['global'][$beforeMethodCallableName]) ?
            self::$staticHooks['global'][$beforeMethodCallableName] : [];

        $hooks = array_merge($hooks, $staticHooks);

        if ( ! empty($hooks)) {
            foreach ($hooks as $callable) {
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
     * @param array  $args
     *
     * @return mixed
     */
    protected function callAfterMethodHooks($classInstance, $method, array $args = [])
    {
        $method = self::getCalledMethod($method);

        $afterMethodCallableName = 'after'.ucfirst($method);

        $hooks = isset($this->hooks[$afterMethodCallableName]) ? $this->hooks[$afterMethodCallableName] : [];

        $staticHooks = isset(self::$staticHooks['global'][$afterMethodCallableName]) ?
            self::$staticHooks['global'][$afterMethodCallableName] : [];

        $hooks = array_merge($hooks, $staticHooks);

        if ( ! empty($hooks)) {
            foreach ($hooks as $callable) {
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
     * @param array  $args
     *
     * @return mixed
     */
    protected function callOnceBeforeMethodHooks($classInstance, $method, array $args = [])
    {
        $method = self::getCalledMethod($method);

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

        $staticKey = 'onceBefore'.ucfirst($method);

        $staticHooks = isset(self::$staticHooks['global'][$staticKey]) ? self::$staticHooks['global'][$staticKey] : [];

        if ( ! isset($this->staticOnceCalledMethods['before'][$onceBeforeMethodName]) && $staticHooks) {
            if ( ! empty($staticHooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $staticHooks);

                $this->staticOnceCalledMethods['before'][$onceBeforeMethodName] = true;

                if ($return !== null) {
                    return $return;
                }
            }
        }
    }

    /**
     * @param object $classInstance
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function callOnceAfterMethodHooks($classInstance, $method, array $args = [])
    {
        $method = self::getCalledMethod($method);

        $onceAfterMethodName = 'onceAfter'.ucfirst($method);
        if ( ! isset($this->onceCalledMethods['after'][$onceAfterMethodName])) {
            if (isset($this->hooks[$onceAfterMethodName])) {
                $this->onceCalledMethods['after'][$onceAfterMethodName] = true;

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
            }
        }

        $staticKey = 'onceAfter'.ucfirst($method);

        $staticHooks = isset(self::$staticHooks['global'][$staticKey]) ? self::$staticHooks['global'][$staticKey] : [];

        if ( ! isset($this->staticOnceCalledMethods['after'][$onceAfterMethodName]) && $staticHooks) {
            if ( ! empty($staticHooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $staticHooks);

                $this->staticOnceCalledMethods['after'][$onceAfterMethodName] = true;

                if ($return !== null) {
                    return $return;
                }
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
        $method = self::getCalledMethod($method);

        $hooks = $this->hooks['once']['beforeAll'];

        if (! $this->onceBeforeCalled && $hooks) {

            if ( ! empty($hooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $hooks);

                $this->onceBeforeCalled = true;

                if ($return !== null) {
                    return $return;
                }
            }
        }

        $staticHooks = self::$staticHooks['global']['once']['beforeAll'];

        if ( ! $this->staticOnceBeforeCalled && $staticHooks) {
            if ( ! empty($staticHooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $staticHooks);

                $this->staticOnceBeforeCalled = true;

                if ($return !== null) {
                    return $return;
                }
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
        $method = self::getCalledMethod($method);

        $hooks = $this->hooks['once']['afterAll'];

        if ( ! $this->onceAfterCalled && $hooks) {

            if ( ! empty($hooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $hooks);

                $this->onceAfterCalled = true;

                if ($return !== null) {
                    return $return;
                }
            }
        }

        $staticHooks = self::$staticHooks['global']['once']['afterAll'];

        if ( ! $this->staticOnceAfterCalled && $staticHooks) {
            if ( ! empty($staticHooks)) {
                $return = $this->callAllHookCallables($classInstance, $method, $staticHooks);

                $this->staticOnceAfterCalled = true;

                if ($return !== null) {
                    return $return;
                }
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
        $method = self::getCalledMethod($method);

        $hooks = array_merge($this->hooks['beforeAll'], self::$staticHooks['global']['beforeAll']);

        if ( ! empty($hooks)) {
            $return = $this->callAllHookCallables($classInstance, $method, $hooks);

            if ($return !== null) {
                return $return;
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
        $method = self::getCalledMethod($method);

        $hooks = array_merge($this->hooks['afterAll'], self::$staticHooks['global']['afterAll']);

        if ( ! empty($hooks)) {
            $return = $this->callAllHookCallables($classInstance, $method, $hooks);

            if ($return !== null) {
                return $return;
            }
        }
    }

    /**
     * @param $method
     * @param $callable
     *
     * @return bool
     * @throws \BadMethodCallException
     *
     */
    private function registerMethodHook($method, $callable)
    {
        $callableRegistrationTokens = [
            '^(after)(.*)(Hook)',
            '^(before)(.*)(Hook)',
            '^(onceAfter)(.*)(Hook)',
            '^(onceBefore)(.*)(Hook)'
        ];

        if (isset($this->hooksNotRestricted[$method])) {
            $this->hooks[$this->hooksNotRestricted[$method]][] = $callable;

            return true;
        } elseif (($matches = self::matchesAny($callableRegistrationTokens, $method)) != false) {
            if ( ! empty($matches[2])) {
                self::methodExists($matches[2]);
            }

            $targetMethod = lcfirst($matches[2]);

            self::methodNotRestricted($targetMethod, $method);

            $hookName = $matches[1].$matches[2];

            $this->hooksNotRestricted[$method] = $hookName;

            self::checkClosure($this, $callable, $targetMethod);

            $this->hooks[$hookName][] = $callable;

            return true;
        } elseif (strpos($method, 'call') === false) {
            throw new \BadMethodCallException("There's a typo in $method. Can't properly set up hook.");
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
        if ($callable instanceof \Closure) {
            $preArguments = $method && $includeMethodParameter ? [$method] : [];

            $callable = $callable->bindTo($classInstance);
        } else {
            $preArguments = $method && $includeMethodParameter ? [$classInstance, $method] : [$classInstance];
        }

        return call_user_func_array($callable, array_merge($preArguments, $args));
    }

    protected static function checkClosure(
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
            if ($method && $callableParameters && $callable instanceof \Closure === false) {
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

                $lastCallableParameterIndex = count($callableParameters) - 1;

                // issue a warning error if the parameters are named differently
                foreach ($parameterDiff as $key => $parameter) {
                    $originalPosition = $key + 1;
                    $callablePosition = $key + 1 + $parameterOffset;

                    $parameterNotInOriginal = $parameter['original'] === null && $parameter['callable'] !== null;
                    $isLastCallableParameter = ($lastCallableParameterIndex === $key);

                    if ($isLastCallableParameter && stripos($parameter['callable'], 'return') !== false) {
                        continue;
                    } elseif ($parameterNotInOriginal) {
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

                if ($message) {
                    trigger_error($message, E_USER_NOTICE);
                }
            }
        }
    }

    /**
     * @param $regexes
     * @param $subject
     *
     * @return int
     */
    private static function matchesAny(array $regexes, $subject)
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
    private static function getCalledMethod($method)
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
    private static function methodExists($methodName)
    {
        if (! method_exists(get_called_class(), $methodName)) {
            throw new \BadMethodCallException("$methodName doesn't exist");
        }

        return true;
    }

    private static function methodNotRestricted($method, $callingMethod = null)
    {
        $method = self::getCalledMethod($method);

        $methodVisibility = self::getModifiers(get_called_class(), $method);

        if ($callingMethod) {
            $message = "$method method called from $callingMethod is restricted by hooky options";
        } else {
            $message = "$method method is restricted by hooky options";
        }

        if (self::$hookableMethods && ! in_array($method, self::$hookableMethods)) {
            throw new \BadMethodCallException($message);
        }

        // check if this is an interface method
        if (($methodVisibility & Constants::ABSTRACT_ACCESSIBLE)
            && ($abstractBitExcluded = $methodVisibility ^ Constants::ABSTRACT_ACCESSIBLE)
            && $abstractBitExcluded & self::$defaultAccessibility) {
            return true;
        } elseif ((self::$defaultAccessibility & Constants::ABSTRACT_ACCESSIBLE)) {
            $message = "$method method called from $callingMethod is restricted by hooky options. Must be implemented "
                ."from an interface or abstract method.";

            throw new \BadMethodCallException($message);
        }

        if ($methodVisibility & self::$defaultAccessibility) {
            return true;
        }

        throw new \BadMethodCallException($message);
    }

    /**
     * @param $class
     * @param $method
     *
     * @return int
     */
    private static function getModifiers($class, $method)
    {
        $reflectionClass = new \ReflectionClass($class);
        /**
         * @var \ReflectionClass $reflectionInterfaces
         */
        $reflectionInterfaces = $reflectionClass->getInterfaces();

        $isInterfaceMethod = 0;
        foreach ($reflectionInterfaces as $reflectionInterface) {
            if ($reflectionInterface->hasMethod($method)) {
                $isInterfaceMethod = 1;
            }
        }

        $topMostClass = self::getTopMostClass($reflectionClass);

        $isAbstractMethod = 0;
        if ($topMostClass->isAbstract() && $topMostClass->hasMethod($method)) {
            $isAbstractMethod = 2;
        }

        $reflectionMethod = new \ReflectionMethod($class, $method);
        $visibility = $reflectionMethod->getModifiers();

        if ($isAbstractMethod | $isInterfaceMethod) {
            $visibility = $visibility | Constants::ABSTRACT_ACCESSIBLE;
        }

        return $visibility;
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return \ReflectionClass
     */
    private static function getTopMostClass(\ReflectionClass $class)
    {
        if ($parentClass = $class->getParentClass()) {
            return self::getTopMostClass($parentClass);
        }

        return $class;
    }

    /**
     * Special method that allows null values to be returned from callables
     *
     * @param mixed $return
     * @return null|mixed
     */
    protected function hookReturn($return)
    {
        if ($return === Constants::NULL) {
            return null;
        }

        return $return;
    }

    public function __destruct()
    {
        self::resetStaticConstructorHooks();
    }

    /**
     * @param $classInstance
     * @param $method
     * @param $hooks
     *
     * @return mixed
     */
    protected function callAllHookCallables($classInstance, $method, $hooks)
    {
        $return = null;

        foreach ($hooks as $callable) {
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
