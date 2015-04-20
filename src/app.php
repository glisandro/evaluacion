<?php
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Doctrine\DBAL\Configuration;
use Security\UserProvider;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use ilex\Provider\SerializerServiceProvider;

$app = new Application();
$app['debug'] = true;

$app->register(new SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new TwigServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...
    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return $app['request_stack']->getMasterRequest()->getBasepath().'/'.$asset;
    }));
    return $twig;
});
$app['twig.path'] = array(__DIR__.'/../templates');

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'     => '127.0.0.1',
        'dbname'     => 'evaluacion',
        'user'     => 'root',
        'password'     => '123',
        'port'     => '3306',
    ),
));

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'webservice' => array(
            'pattern' => '^/admin/webservice/',
            'anonymous' => true,
        ),
        'admin' => array(
            'pattern' => '^/admin/',
            'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
            'logout' => array('logout_path' => '/admin/logout',"invalidate_session" => true),
            //'users' => array(
            //    'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            //),
            'users' => $app->share(function () use ($app) {
					    return new Security\UserProvider($app['db']);
					})
        ),
    )
));
 
$app['security.encoder.digest'] = $app->share(function ($app) {
    return new MessageDigestPasswordEncoder('sha1', false, 1);
});

$app->register(new Silex\Provider\SerializerServiceProvider());

$app->boot();

return $app;