[![Build Status](https://travis-ci.org/2upmedia/hooky.svg)](https://travis-ci.org/2upmedia/hooky)

# Hooky

Easily and reliably allow your classes to be hooked into without allowing people to touch your core code. Some inspiration came from Aspect Oriented Programming.

## Audience

Authors of packages or developers extending existing packages and anyone that needs to allow hooks for core code they can modify.

## Uses
- Logging, triggering events, analytics
- When you need to do something consistently before or after a method call: custom authenticating of an HTTP call, transforming data after it's been received
- Whatever you make of it

## Features
- Hooking to specific instances
- Global hooks for all instances of a specific class
- Hooking to constructors
- Dynamic analysis to catch bugs before they happen: method name checks, mismatched parameters, restricted methods
- PSR2 and PSR4
- 99% code coverage thanks to TDD
- Perfectly-spaced, hand-crafted PHPDocs
- Semantic Versioning (currently in initial development phase)

## Installing via Composer

The recommended way to install Hooky is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Hooky:

```bash
composer require 2upmedia/hooky
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

## Examples

```php
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

(new HelloWorld())->sayIt(); // echos hello world
```
    
## Check out demos with well-known packages

https://github.com/2upmedia/hooky-demos

## Documentation

### Making your classes hooky-compatible

#### Hook executors

There's two core actions: before and after. You choose what if want to allow one, both, or either/or.

`$this->callBeforeHooks()` allows ALL before-type hooks: beforeAll, onceBeforeAll, before{Method}, and onceBefore{Method}

`$this->callAfterHooks()` does the same but for after-type hooks

### Controlling actions

You can control the types of hooks you will allow per method by using the specific call method for the action you want to allow.
 
For instance, if you don't want to allow beforeAll hooks for your method, but you want to allow people to hook before your method you can call `$this->callAfterMethodHooks()`.

#### Allowing return values from callables

Early returns are possible using the following format right before your core method code:

```php
public function foo($methodParameter){
	if (($hookReturn = $this->callBeforeHooks($this, __METHOD__, [$methodParameter])) !== null) {
        return $hookReturn;
    }
```
    
**Note:** The first applicable registered hook that returns a value will cancel all successively registered hooks for that method. 

#### Allowing parameters to be overridden

**NOTE:** use with care

Just send parameters as references

```php
// method hook
public function foo($methodParameter){
    if (($hookReturn = $this->callBeforeHooks($this, __METHOD__, [&$methodParameter])) !== null) {
        return $hookReturn;
    }

// in your callable you also mark the parameter as a reference
$bar->afterFooHook(function (&$methodParameter) {
    $methodParameter = 'Some other value';
});
```
    
#### Handling returned null values

Return the special NULL constant and also make sure to use `hookReturn()` when you set up your hooks.

```php
// method hook
public function foo($methodParameter){
    if (($hookReturn = $this->callBeforeHooks($this, __METHOD__, [&$methodParameter])) !== null) {
        return $this->hookReturn($hookReturn);
    }

// in your callable
use _2UpMedia\Hooky\Constants;

$bar->afterFooHook(function ($methodParameter) {
    return Constants::NULL;
});
```
    
#### Allowing original return value to be manipulated in your after hooks

**NOTE:** use with care

```php
// method hook
public function foo($methodParameter){
	// do some awesome stuff with $methodParameter;
	$return = $this->wowwow($methodParameter); // split core return value into a variable
	
    if (($hookReturn = $this->callAfterHooks($this, __METHOD__, [$methodParameter, $return])) !== null) {
        return $this->hookReturn($hookReturn);
    }
    
    return $return; // very important in case you don't have any hooks returning values
```


#### Cancelling propagation

```php
// in your callable
use _2UpMedia\Hooky\CancelPropagationException;

$bar->afterFooHook(function ($methodParameter) {
    throw new CancelPropagationException('buahahahaha!');
});

$bar->afterFooHook(function ($methodParameter) {
    // this one never gets called
});
```

### Default behavior

To reduce the risk of hooks breaking because of the underlying libraries being refactored, the decision was made to restrict hooking to concrete public methods of interface methods or abstract methods as the default. Interfaces and abstracts are usually well thought out and usually don't change as often as they're supposed to be contracts, and thus any hooks relying on those methods would break much less frequently, if not at all.
 
 This default behavior can be changed. See the Changing Default Behavior section.
 
### Changing Default Behavior

You can restrict hooking to the following types of methods using `setDefaultAccessibility()`: public, protected, private, and/or abstract/interface method.
	
```php
use _2UpMedia\Hooky\Constants;

// allow hooking to public methods
$this->setDefaultAccessibility(Constants::PUBLIC_ACCESSIBLE);

// allow hooking to public and protected methods
$this->setDefaultAccessibility(Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE);

// allow hooking ONLY to public and protected abstract methods
$this->setDefaultAccessibility(Constants::PUBLIC_ACCESSIBLE | Constants::PROTECTED_ACCESSIBLE | Constants::ABSTRACT_ONLY );
```
	
### Class-wide hooks

Global hooks are called statically by the name of the class that's using the HooksTrait. Global hooks are called for all instances.

The format is: `{ClassName}::global{Action}[ {Method} ]Hook`

- `{ClassName}::globalBeforeAllHook`
- `{ClassName}::globalOnceBeforeAllHook`
- `{ClassName}::globalBefore{Method}Hook`
- `{ClassName}::globalOnceBefore{Method}Hook`
- `{ClassName}::globalAfterAllHook`
- `{ClassName}::globalOnceAfterAllHook`
- `{ClassName}::globalAfter{Method}Hook`
- `{ClassName}::globalOnceAfter{Method}Hook`


```php
Bar::globalBeforeAllHook(function ($) {
	var_dump(
});

$bar->foo();
```

### Instance-specific hooks

Instance-specific hooks are called as methods of an instance and these hooks are not called globally.

The format is: `{$instanceVariable}->{action}[ {Method} ]Hook`

- `{$instanceVariable}->beforeAllHook`
- `{$instanceVariable}->onceBeforeAllHook`
- `{$instanceVariable}->before{Method}Hook`
- `{$instanceVariable}->onceBefore{Method}Hook`
- `{$instanceVariable}->afterAllHook`
- `{$instanceVariable}->onceAfterAllHook`
- `{$instanceVariable}->after{Method}Hook`
- `{$instanceVariable}->onceAfter{Method}Hook`

### Check potential bugs before going out to production

The `{ClassName}::$checkCallableParameters` flag triggers PHP Notices on different issues where method parameters don't match up with the callables set up through hooks. This is handy during development to catch potential bugs. By default it is turned off because of a potential performance hit.

### Restricting hooking to specific methods

In the `__construct()` of your concrete class call `$this->setHookableMethods(['methodName1', 'methodName2'])`.

### Working with existing `__destruct()`

Because of the nature of Traits, `__destruct()` will be overridden if the implemented class already has them. The workaround is to use an alias.

```php
use \_2UpMedia\Hooky\HooksTrait { __destruct as traitDestruct; }

public function __destruct()
{
    $this->traitDestruct();
```

keywords: aop, hook, event, pub, sub
