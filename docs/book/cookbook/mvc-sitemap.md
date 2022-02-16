# How to Create a XML Sitemap in a Mvc-Based Application?

The following example shows _one_ potential use case to render a [XML sitemap](https://www.sitemaps.org) within a laminas-mvc based application.
The example uses a middleware as request handler and the [Sitemap view helper](../helpers/sitemap.md).

The example is based on the [laminas-mvc skeleton application](https://github.com/laminas/laminas-mvc-skeleton).

Before starting, make sure laminas-navigation is installed and configured and also [laminas-mvc-middleware](https://docs.laminas.dev/laminas-mvc-middleware/).

## Create Navigation Container Configuration

[Add a container definition for the navigation](../quick-start.md) to the configuration, e.g. `config/autoload/global.php`:

```php
return [
    'navigation' => [
        'default' => [
            [
                'label' => 'Home',
                'route' => 'home',
            ],
            [
                'label' => 'Another page',
                'route' => 'application',
            ],
        ],
    ],
    // …
];
```

## Create Request Handler

The Sitemap helper already creates all the XML content, so the rendering of the view layer can be omitted.
With the [custom response type `Laminas\Diactoros\Response\XmlResponse`](https://docs.laminas.dev/laminas-diactoros/v2/custom-responses/#xml-responses) the `Content-Type` header is set to `application/xml`.

[Create a middleware class](https://docs.laminas.dev/laminas-mvc-middleware/v2/quick-start/#writing-middleware) as request handler and inject the Sitemap helper via the constructor, e.g. `module/Application/Handler/SitemapHandler.php`:

```php
namespace Application\Handler;

use Laminas\Diactoros\Response\XmlResponse;
use Laminas\View\Helper\Navigation\Sitemap;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SitemapHandler implements RequestHandlerInterface
{
    private Sitemap $sitemapHelper;

    public function __construct(Sitemap $sitemapHelper)
    {
        $this->sitemapHelper = $sitemapHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new XmlResponse(
            $this->sitemapHelper->setContainer('default')->render()
        );
    }
}
```

### Create Factory for Request Handler

Fetch the [Navigation Proxy helper](../helpers/navigation.md) from the [view helper manager](https://docs.laminas.dev/laminas-mvc/services/#plugin-managers) and fetch the Sitemap helper from Proxy helper in a [factory class](https://docs.laminas.dev/laminas-servicemanager/configuring-the-service-manager/#factories), e.g. `src/Application/Handler/SitemapHandlerFactory.php`:

```php
namespace Application\Handler;

use Laminas\View\Helper\Navigation as NavigationProxyHelper;
use Laminas\View\Helper\Navigation\Sitemap;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

class SitemapHandlerFactory
{
    public function __invoke(ContainerInterface $container): SitemapHandler
    {
        // View helper manager
        /** @var HelperPluginManager $viewHelperPluginManager */
        $viewHelperPluginManager = $container->get('ViewHelperManager');

        // Navigation view helper
        /** @var NavigationProxyHelper $navigationHelper */
        $navigationHelper = $viewHelperPluginManager->get(
            NavigationProxyHelper::class
        );

        // Sitemap view helper
        /** @var Sitemap $sitemapHelper */
        $sitemapHelper = $navigationHelper->findHelper(Sitemap::class);

        return new SitemapHandler($sitemapHelper);
    }
}
```

## Register Middleware and Create Route

To [register the middleware](https://docs.laminas.dev/laminas-mvc-middleware/v2/quick-start/#mapping-routes-to-middleware-and-request-handlers) for the application and to [create the route](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-route), extend the configuration of the module.
Add the following lines to the module configuration file, e.g. `module/Application/config/module.config.php`:

<!-- markdownlint-disable MD033 -->
<pre class="language-php" data-line="3-4,9-10,15-25"><code>
namespace Application;

use Laminas\Mvc\Middleware\PipeSpec;
use Laminas\Router\Http\Literal;

return [
    'service_manager' => [
        'factories' => [
            // Add this line
            Handler\SitemapHandler::class => Handler\SitemapHandlerFactory::class,
        ],
    ],
    'router'          => [
        'routes' => [
            // Add following array
            'sitemap'     => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/sitemap.xml',
                    'defaults' => [
                        'controller' => PipeSpec::class,
                        'middleware' => Handler\SitemapHandler::class,
                    ],
                ],
            ],
            // …
        ],
    ],
    // …
];
</code></pre>
<!-- markdownlint-restore -->

## Render Sitemap

To render the sitemap, open a browser, e.g. `http://localhost:8080/sitemap.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>http://localhost:8080/</loc></url><url><loc>http://localhost:8080/application</loc></url></urlset>
```

To format the output, which is useful for better reading _only_ for humans, use the `setFormatOutput` method of the view helper:

```php
return new XmlResponse(
    $this->sitemapHelper->setContainer('default')->setFormatOutput()->render()
);
```

Output:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://localhost:8080/</loc>
  </url>
  <url>
    <loc>http://localhost:8080/application</loc>
  </url>
</urlset>
```

## Using a Controller

This all works with a [_classic_ controller](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-controller) as well.
The Sitemap helper already creates all the XML content, so the rendering of the view layer can be also omitted here:

* Get the response object of the controller
* Set the HTTP header for XML content
* Set the rendered sitemap as content
* Return the response object for controller action

```php
namespace Application\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Helper\Navigation\Sitemap;

class IndexController extends AbstractActionController
{
    private Sitemap $sitemapHelper;

    public function __construct(Sitemap $sitemapHelper)
    {
        $this->sitemapHelper = $sitemapHelper;
    }

    public function sitemapAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        
        // Set HTTP header for XML content type
        $response->setHeaders(
            $response->getHeaders()->addHeaderLine(
                'Content-Type',
                'application/xml; charset=utf-8'
            )
        );
        
        // Render sitemap and set as content
        $response->setContent(
            $this->sitemapHelper->setContainer('default')->render()
        );

        // Return HTTP response
        return $response;
    }
}
```
