<?php

namespace _PhpScoper5ea00cc67502b;

use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\ContainerBuilder;
use _PhpScoper5ea00cc67502b\Symfony\Component\DependencyInjection\Reference;
$container = new ContainerBuilder();
$container->register('foo', 'FooClass')->addArgument(new Reference('bar'))->setPublic(true);
$container->register('bar', 'BarClass')->setPublic(true);
$container->compile();
return $container;
