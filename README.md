[![Latest Stable Version](https://poser.pugx.org/mouf/mvc.splash-common/v/stable)](https://packagist.org/packages/mouf/mvc.splash-common)
[![Total Downloads](https://poser.pugx.org/mouf/mvc.splash-common/downloads)](https://packagist.org/packages/mouf/mvc.splash-common)
[![Latest Unstable Version](https://poser.pugx.org/mouf/mvc.splash-common/v/unstable)](https://packagist.org/packages/mouf/mvc.splash-common)
[![License](https://poser.pugx.org/mouf/mvc.splash-common/license)](https://packagist.org/packages/mouf/mvc.splash-common)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/mvc.splash-common/badges/quality-score.png?b=8.0)](https://scrutinizer-ci.com/g/thecodingmachine/mvc.splash-common/?branch=8.0)
[![Build Status](https://travis-ci.org/thecodingmachine/mvc.splash-common.svg?branch=8.0)](https://travis-ci.org/thecodingmachine/mvc.splash-common)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/mvc.splash-common/badge.svg?branch=8.0&service=github)](https://coveralls.io/github/thecodingmachine/mvc.splash-common?branch=8.0)

Splash: a highly configurable PSR-7 router
==========================================

What is Splash?
---------------

Splash is a PHP router. It takes an HTTP request and dispatches it to the appropriate controller.
  
- Splash is [PSR-7 compatible](http://www.php-fig.org/psr/psr-7/)
- It acts as a [PSR-7 middleware](https://akrabat.com/writing-psr-7-middleware/)
- It is based on **controllers** and **annotations** (routes are declared as annotations in the controllers)
- It is heavily optimized, relying on an underlying [PSR-6 cache](http://www.php-fig.org/psr/psr-6/)
- It is a **pure** router. It is not a full-featured MVC framework. No views management, no model, only routing!
- It promotes best practices. Controllers must be declared in a [container-interop compatible container](https://github.com/container-interop/container-interop/).
- It is extensible.
- It integrates with a high number of tools:
    - With [Mouf](http://mouf-php.com): this provides a friendly UI to generate controllers
    - With [Drupal (through Druplash, a module that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.drupal.druplash),
    - With [Wordpress (through Moufpress, a plugin that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.wordpress.moufpress),
    - With [Joomla (through Moufla, a plugin that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.wordpress.moufpress),
    - With [Magento (through Moufgento, a plugin that adds a Splash-compatible MVC framework)](http://mouf-php.com/packages/mouf/integration.magento.moufgento),
- And it supports emoji routes (mydomain.com/😍)


Clean controllers
-----------------

Want to get a feeling of Splash? Here is a typical controller:

```php
use Mouf\Mvc\Splash\Annotations\Get;
use Mouf\Mvc\Splash\Annotations\URL;

class MyController
{
    /**
     * @URL("/my/path")
     * @Get
     */
    public function index(ServerRequestInterface $request)
    {
        return new JsonResponse(["Hello" => "World!"]);
    }
}
```

Ok, so far, things should be fairly obvious to anyone used to PSR-7. The important parts are:

- Routing is done using the **@URL** annotation. When a method has the **@URL** annotation, we call it an *action*.
- **Controllers are clean**. They don't extend any "Splash" object (so are reusable in any other PSR-7 compatible MVC framework)
- Actions can optionally have a **@Get**, **@Post**, **@Put**, **@Delete** annotation to restrict the response to some HTTP method.
- Splash analyzes the action signature. If it finds a type-hinted `ServerRequestInterface` parameter, it will fill it the PSR-7 request object.
- Actions must return an object implementing the PSR-7 `ResponseInterface`.


Even better
-----------

But Splash can provide much more than this.

Here is a more advanced routing scenario:

```php
use Mouf\Mvc\Splash\Annotations\Post;
use Mouf\Mvc\Splash\Annotations\URL;
use Psr\Http\Message\UploadedFileInterface;

class MyController
{
    /**
     * @URL("/user/{id}")
     * @Post
     */
    public function index($id, $firstName, $lastName, UploadedFileInterface $logo)
    {
        return //...;
    }
}
```

Look at the signature: `public function index($id, $firstName, $lastName, UploadedFileInterface $logo)`.

- `$id` will be fetched from the URL (`@URL("/user/{id}")`)
- `$firstName` and `$lastName` will be automatically fetched from the GET/POST parameters
- finally, `$logo` will contain the uploaded file from `$_FILES['logo']`

See the magic? **Just by looking at your method signature, you know what parameters your route is expecting.** The method signature is self-documenting the route!

Even better, Splash is highly extensible. You can add your own plugins to automatically fill some parameters of the request (we call those `ParameterFetchers`).
You could for instance write a parameter fetcher to automatically load Doctrine entities:

```php
    /**
     * Lo and behold: you can extend Splash to automatically fill the function parameters with objects of your liking
     *
     * @URL("/product/{product}")
     * @Post
     */
    public function index(My\Entities\Product $product)
    {
        return //...;
    }

```

Best practices
--------------

You might wonder: "*How will Splash instantiate your controller*?" Well, Splash will not instantiate your controller.
Instantiating services and containers is the role of the dependency injection container. Splash connects to any *container-interop* compatible container and will fetch your controllers from the container.

This means that you **must** declare your controller in the container you are using. This is actually a *good thing* as this encourages you to not use the container as a service locator.


High performance
----------------

For best performance, Splash is caching the list of routes it detects. Unlike what can be seen in most micro-frameworks where the application slows down as the number of routes increases, in Splash, **performance stays constant as the number of routes increases**.
