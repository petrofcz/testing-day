<?php

use App\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
$loader->load(__DIR__ . '/config/env.yaml');
$loader->load(__DIR__ . '/config/services.yaml');

$containerBuilder->compile();


/** @var Application $app */
return $containerBuilder->get(Application::class);

