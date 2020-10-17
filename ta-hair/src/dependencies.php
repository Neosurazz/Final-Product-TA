<?php
// DIC configuration

use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

$container['Braintree'] = function ($c) {
    $gateway = new Braintree_Gateway([
        'environment' => 'sandbox',
        'merchantId' => 'qdg7kt797q92xty4',
        'publicKey' => 'mqv5xr93tk47sxkn',
        'privateKey' => 'e9f092ad7a0cce5ec03b7c79b7efdd5c'
    ]);

    return $gateway;
};



// Register Twig View helper
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => false
    ]);
    
    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
    $view->addExtension (new App\Helpers\TwigHelpers());
    return $view;
};

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
//pass the connection to global container (created in previous article)
$container['db'] = function ($container) use ($capsule){
   return $capsule;
};

$container['mailer'] = function ($c) {
    $settings = $c->get('settings')['mail'];

    $dsn = sprintf(
        '%s://%s:%s@%s:%s',
        $settings['type'],
        $settings['username'],
        $settings['password'],
        $settings['host'],
        $settings['port']
    );

    return new Mailer(Transport::fromDsn($dsn));
};

$container['HomeController'] = function($c) {
    $view = $c->get("view"); // retrieve the 'view' from the container
    return new HomeController($view);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};



