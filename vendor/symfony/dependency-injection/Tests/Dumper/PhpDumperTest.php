<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Dumper;

use _PhpScoper5eddef0da618a\PHPUnit\Framework\TestCase;
use _PhpScoper5eddef0da618a\Psr\Container\ContainerInterface;
use _PhpScoper5eddef0da618a\Symfony\Component\Config\FileLocator;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ChildDefinition;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Parameter;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ServiceLocator;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\CustomDefinition;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\ScalarFactory;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\StubbedTranslator;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\TestServiceSubscriber;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\TypedReference;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Variable;
use _PhpScoper5eddef0da618a\Symfony\Component\ExpressionLanguage\Expression;
require_once __DIR__ . '/../Fixtures/includes/classes.php';
class PhpDumperTest extends \_PhpScoper5eddef0da618a\PHPUnit\Framework\TestCase
{
    protected static $fixturesPath;
    public static function setUpBeforeClass()
    {
        self::$fixturesPath = \realpath(__DIR__ . '/../Fixtures/');
    }
    public function testDump()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services1.php', $dumper->dump(), '->dump() dumps an empty container as an empty PHP class');
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services1-1.php', $dumper->dump(['class' => 'Container', 'base_class' => 'AbstractContainer', 'namespace' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Dump']), '->dump() takes a class and a base_class options');
    }
    public function testDumpOptimizationString()
    {
        $definition = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition();
        $definition->setClass('stdClass');
        $definition->addArgument(['only dot' => '.', 'concatenation as value' => '.\'\'.', 'concatenation from the start value' => '\'\'.', '.' => 'dot as a key', '.\'\'.' => 'concatenation as a key', '\'\'.' => 'concatenation from the start key', 'optimize concatenation' => 'string1%some_string%string2', 'optimize concatenation with empty string' => 'string1%empty_value%string2', 'optimize concatenation from the start' => '%empty_value%start', 'optimize concatenation at the end' => 'end%empty_value%', 'new line' => "string with \nnew line"]);
        $definition->setPublic(\true);
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setResourceTracking(\false);
        $container->setDefinition('test', $definition);
        $container->setParameter('empty_value', '');
        $container->setParameter('some_string', '-');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services10.php', $dumper->dump(), '->dump() dumps an empty container as an empty PHP class');
    }
    public function testDumpRelativeDir()
    {
        $definition = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition();
        $definition->setClass('stdClass');
        $definition->addArgument('%foo%');
        $definition->addArgument(['%foo%' => '%buz%/']);
        $definition->setPublic(\true);
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setDefinition('test', $definition);
        $container->setParameter('foo', 'wiz' . \dirname(__DIR__));
        $container->setParameter('bar', __DIR__);
        $container->setParameter('baz', '%bar%/PhpDumperTest.php');
        $container->setParameter('buz', \dirname(\dirname(__DIR__)));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services12.php', $dumper->dump(['file' => __FILE__]), '->dump() dumps __DIR__ relative strings');
    }
    public function testDumpCustomContainerClassWithoutConstructor()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/custom_container_class_without_constructor.php', $dumper->dump(['base_class' => 'NoConstructorContainer', 'namespace' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Tests\\Fixtures\\Container']));
    }
    public function testDumpCustomContainerClassConstructorWithoutArguments()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/custom_container_class_constructor_without_arguments.php', $dumper->dump(['base_class' => 'ConstructorWithoutArgumentsContainer', 'namespace' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Tests\\Fixtures\\Container']));
    }
    public function testDumpCustomContainerClassWithOptionalArgumentLessConstructor()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/custom_container_class_with_optional_constructor_arguments.php', $dumper->dump(['base_class' => 'ConstructorWithOptionalArgumentsContainer', 'namespace' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Tests\\Fixtures\\Container']));
    }
    public function testDumpCustomContainerClassWithMandatoryArgumentLessConstructor()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/custom_container_class_with_mandatory_constructor_arguments.php', $dumper->dump(['base_class' => 'ConstructorWithMandatoryArgumentsContainer', 'namespace' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Tests\\Fixtures\\Container']));
    }
    /**
     * @dataProvider provideInvalidParameters
     */
    public function testExportParameters($parameters)
    {
        $this->expectException('InvalidArgumentException');
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag($parameters));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
    }
    public function provideInvalidParameters()
    {
        return [[['foo' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition('stdClass')]], [['foo' => new \_PhpScoper5eddef0da618a\Symfony\Component\ExpressionLanguage\Expression('service("foo").foo() ~ (container.hasParameter("foo") ? parameter("foo") : "default")')]], [['foo' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo')]], [['foo' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Variable('foo')]]];
    }
    public function testAddParameters()
    {
        $container = (include self::$fixturesPath . '/containers/container8.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services8.php', $dumper->dump(), '->dump() dumps parameters');
    }
    /**
     * @group legacy
     * @expectedDeprecation Dumping an uncompiled ContainerBuilder is deprecated since Symfony 3.3 and will not be supported anymore in 4.0. Compile the container beforehand.
     */
    public function testAddServiceWithoutCompilation()
    {
        $container = (include self::$fixturesPath . '/containers/container9.php');
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services9.php', \str_replace(\str_replace('\\', '\\\\', self::$fixturesPath . \DIRECTORY_SEPARATOR . 'includes' . \DIRECTORY_SEPARATOR), '%path%', $dumper->dump()), '->dump() dumps services');
    }
    public function testAddService()
    {
        $container = (include self::$fixturesPath . '/containers/container9.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services9_compiled.php', \str_replace(\str_replace('\\', '\\\\', self::$fixturesPath . \DIRECTORY_SEPARATOR . 'includes' . \DIRECTORY_SEPARATOR), '%path%', $dumper->dump()), '->dump() dumps services');
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'FooClass')->addArgument(new \stdClass())->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        try {
            $dumper->dump();
            $this->fail('->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        } catch (\Exception $e) {
            $this->assertInstanceOf('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\RuntimeException', $e, '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
            $this->assertEquals('Unable to dump a service container if a parameter is an object or a resource.', $e->getMessage(), '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        }
    }
    public function testDumpAsFiles()
    {
        $container = (include self::$fixturesPath . '/containers/container9.php');
        $container->getDefinition('bar')->addTag('hot');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dump = \print_r($dumper->dump(['as_files' => \true, 'file' => __DIR__, 'hot_path_tag' => 'hot']), \true);
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $dump = \str_replace('\\\\Fixtures\\\\includes\\\\foo.php', '/Fixtures/includes/foo.php', $dump);
        }
        $this->assertStringMatchesFormatFile(self::$fixturesPath . '/php/services9_as_files.txt', $dump);
    }
    public function testServicesWithAnonymousFactories()
    {
        $container = (include self::$fixturesPath . '/containers/container19.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services19.php', $dumper->dump(), '->dump() dumps services with anonymous factories');
    }
    public function testAddServiceIdWithUnsupportedCharacters()
    {
        $class = 'Symfony_DI_PhpDumper_Test_Unsupported_Characters';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter("'", 'oh-no');
        $container->register('foo*/oh-no', 'FooClass')->setPublic(\true);
        $container->register('bar$', 'FooClass')->setPublic(\true);
        $container->register('bar$!', 'FooClass')->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_unsupported_characters.php', $dumper->dump(['class' => $class]));
        require_once self::$fixturesPath . '/php/services_unsupported_characters.php';
        $this->assertTrue(\method_exists($class, 'getFooOhNoService'));
        $this->assertTrue(\method_exists($class, 'getBarService'));
        $this->assertTrue(\method_exists($class, 'getBar2Service'));
    }
    public function testConflictingServiceIds()
    {
        $class = 'Symfony_DI_PhpDumper_Test_Conflicting_Service_Ids';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo_bar', 'FooClass')->setPublic(\true);
        $container->register('foobar', 'FooClass')->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => $class]));
        $this->assertTrue(\method_exists($class, 'getFooBarService'));
        $this->assertTrue(\method_exists($class, 'getFoobar2Service'));
    }
    public function testConflictingMethodsWithParent()
    {
        $class = 'Symfony_DI_PhpDumper_Test_Conflicting_Method_With_Parent';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('bar', 'FooClass')->setPublic(\true);
        $container->register('foo_bar', 'FooClass')->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => $class, 'base_class' => '_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Tests\\Fixtures\\containers\\CustomContainer']));
        $this->assertTrue(\method_exists($class, 'getBar2Service'));
        $this->assertTrue(\method_exists($class, 'getFoobar2Service'));
    }
    /**
     * @dataProvider provideInvalidFactories
     */
    public function testInvalidFactories($factory)
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\RuntimeException');
        $this->expectExceptionMessage('Cannot dump definition');
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $def = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition('stdClass');
        $def->setPublic(\true);
        $def->setFactory($factory);
        $container->setDefinition('bar', $def);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
    }
    public function provideInvalidFactories()
    {
        return [[['', 'method']], [['class', '']], [['...', 'method']], [['class', '...']]];
    }
    public function testAliases()
    {
        $container = (include self::$fixturesPath . '/containers/container9.php');
        $container->setParameter('foo_bar', 'foo_bar');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Aliases']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Aliases();
        $foo = $container->get('foo');
        $this->assertSame($foo, $container->get('alias_for_foo'));
        $this->assertSame($foo, $container->get('alias_for_alias'));
    }
    public function testFrozenContainerWithoutAliases()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Frozen_No_Aliases']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Frozen_No_Aliases();
        $this->assertFalse($container->has('foo'));
    }
    /**
     * @group legacy
     * @expectedDeprecation The "decorator_service" service is already initialized, replacing it is deprecated since Symfony 3.3 and will fail in 4.0.
     */
    public function testOverrideServiceWhenUsingADumpedContainer()
    {
        require_once self::$fixturesPath . '/php/services9_compiled.php';
        $container = new \_PhpScoper5eddef0da618a\ProjectServiceContainer();
        $container->get('decorator_service');
        $container->set('decorator_service', $decorator = new \stdClass());
        $this->assertSame($decorator, $container->get('decorator_service'), '->set() overrides an already defined service');
    }
    public function testDumpAutowireData()
    {
        $container = (include self::$fixturesPath . '/containers/container24.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services24.php', $dumper->dump());
    }
    public function testEnvInId()
    {
        $container = (include self::$fixturesPath . '/containers/container_env_in_id.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_env_in_id.php', $dumper->dump());
    }
    public function testEnvParameter()
    {
        $rand = \mt_rand();
        \putenv('Baz=' . $rand);
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $loader = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \_PhpScoper5eddef0da618a\Symfony\Component\Config\FileLocator(self::$fixturesPath . '/yaml'));
        $loader->load('services26.yml');
        $container->setParameter('env(json_file)', self::$fixturesPath . '/array.json');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services26.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_EnvParameters', 'file' => self::$fixturesPath . '/php/services26.php']));
        require self::$fixturesPath . '/php/services26.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_EnvParameters();
        $this->assertSame($rand, $container->getParameter('baz'));
        $this->assertSame([123, 'abc'], $container->getParameter('json'));
        $this->assertSame('sqlite:///foo/bar/var/data.db', $container->getParameter('db_dsn'));
        \putenv('Baz');
    }
    public function testResolvedBase64EnvParameters()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('env(foo)', \base64_encode('world'));
        $container->setParameter('hello', '%env(base64:foo)%');
        $container->compile(\true);
        $expected = ['env(foo)' => 'd29ybGQ=', 'hello' => 'world'];
        $this->assertSame($expected, $container->getParameterBag()->all());
    }
    public function testDumpedBase64EnvParameters()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('env(foo)', \base64_encode('world'));
        $container->setParameter('hello', '%env(base64:foo)%');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_base64_env.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Base64Parameters']));
        require self::$fixturesPath . '/php/services_base64_env.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Base64Parameters();
        $this->assertSame('world', $container->getParameter('hello'));
    }
    public function testCustomEnvParameters()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('env(foo)', \str_rot13('world'));
        $container->setParameter('hello', '%env(rot13:foo)%');
        $container->register(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Dumper\Rot13EnvVarProcessor::class)->addTag('container.env_var_processor')->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_rot13_env.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Rot13Parameters']));
        require self::$fixturesPath . '/php/services_rot13_env.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Rot13Parameters();
        $this->assertSame('world', $container->getParameter('hello'));
    }
    public function testFileEnvProcessor()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('env(foo)', __FILE__);
        $container->setParameter('random', '%env(file:foo)%');
        $container->compile(\true);
        $this->assertStringEqualsFile(__FILE__, $container->getParameter('random'));
    }
    public function testUnusedEnvParameter()
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\EnvParameterException');
        $this->expectExceptionMessage('Environment variables "FOO" are never used. Please, check your container\'s configuration.');
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->getParameter('env(FOO)');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
    }
    public function testCircularDynamicEnv()
    {
        $this->expectException('_PhpScoper5eddef0da618a\\Symfony\\Component\\DependencyInjection\\Exception\\ParameterCircularReferenceException');
        $this->expectExceptionMessage('Circular reference detected for parameter "env(resolve:DUMMY_ENV_VAR)" ("env(resolve:DUMMY_ENV_VAR)" > "env(resolve:DUMMY_ENV_VAR)").');
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('foo', '%bar%');
        $container->setParameter('bar', '%env(resolve:DUMMY_ENV_VAR)%');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dump = $dumper->dump(['class' => $class = __FUNCTION__]);
        eval('?>' . $dump);
        $container = new $class();
        \putenv('DUMMY_ENV_VAR=%foo%');
        try {
            $container->getParameter('bar');
        } finally {
            \putenv('DUMMY_ENV_VAR');
        }
    }
    public function testInlinedDefinitionReferencingServiceContainer()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->addMethodCall('add', [new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('service_container')])->setPublic(\false);
        $container->register('bar', 'stdClass')->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo'))->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services13.php', $dumper->dump(), '->dump() dumps inline definitions which reference service_container');
    }
    public function testNonSharedLazyDefinitionReferences()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setShared(\false)->setLazy(\true);
        $container->register('bar', 'stdClass')->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder::EXCEPTION_ON_INVALID_REFERENCE, \false));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->setProxyDumper(new \_PhpScoper5eddef0da618a\DummyProxyDumper());
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_non_shared_lazy.php', $dumper->dump());
    }
    public function testInitializePropertiesBeforeMethodCalls()
    {
        require_once self::$fixturesPath . '/includes/classes.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(\true);
        $container->register('bar', 'MethodCallClass')->setPublic(\true)->setProperty('simple', 'bar')->setProperty('complex', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo'))->addMethodCall('callMe');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Properties_Before_Method_Calls']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Properties_Before_Method_Calls();
        $this->assertTrue($container->get('bar')->callPassed(), '->dump() initializes properties before method calls');
    }
    public function testCircularReferenceAllowanceForLazyServices()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('bar'))->setPublic(\true);
        $container->register('bar', 'stdClass')->setLazy(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo'))->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->setProxyDumper(new \_PhpScoper5eddef0da618a\DummyProxyDumper());
        $dumper->dump();
        $this->addToAssertionCount(1);
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $message = 'Circular reference detected for service "foo", path: "foo -> bar -> foo". Try running "composer require symfony/proxy-manager-bridge".';
        $this->expectException(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException::class);
        $this->expectExceptionMessage($message);
        $dumper->dump();
    }
    public function testDedupLazyProxy()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setLazy(\true)->setPublic(\true);
        $container->register('bar', 'stdClass')->setLazy(\true)->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->setProxyDumper(new \_PhpScoper5eddef0da618a\DummyProxyDumper());
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_dedup_lazy_proxy.php', $dumper->dump());
    }
    public function testLazyArgumentProvideGenerator()
    {
        require_once self::$fixturesPath . '/includes/classes.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('lazy_referenced', 'stdClass')->setPublic(\true);
        $container->register('lazy_context', 'LazyContext')->setPublic(\true)->setArguments([new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\IteratorArgument(['k1' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('lazy_referenced'), 'k2' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('service_container')]), new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\IteratorArgument([])]);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Lazy_Argument_Provide_Generator']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Lazy_Argument_Provide_Generator();
        $lazyContext = $container->get('lazy_context');
        $this->assertInstanceOf(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\RewindableGenerator::class, $lazyContext->lazyValues);
        $this->assertInstanceOf(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\RewindableGenerator::class, $lazyContext->lazyEmptyValues);
        $this->assertCount(2, $lazyContext->lazyValues);
        $this->assertCount(0, $lazyContext->lazyEmptyValues);
        $i = -1;
        foreach ($lazyContext->lazyValues as $k => $v) {
            switch (++$i) {
                case 0:
                    $this->assertEquals('k1', $k);
                    $this->assertInstanceOf('stdCLass', $v);
                    break;
                case 1:
                    $this->assertEquals('k2', $k);
                    $this->assertInstanceOf('Symfony_DI_PhpDumper_Test_Lazy_Argument_Provide_Generator', $v);
                    break;
            }
        }
        $this->assertEmpty(\iterator_to_array($lazyContext->lazyEmptyValues));
    }
    public function testNormalizedId()
    {
        $container = (include self::$fixturesPath . '/containers/container33.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services33.php', $dumper->dump());
    }
    public function testDumpContainerBuilderWithFrozenConstructorIncludingPrivateServices()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo_service', 'stdClass')->setArguments([new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('baz_service')])->setPublic(\true);
        $container->register('bar_service', 'stdClass')->setArguments([new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('baz_service')])->setPublic(\true);
        $container->register('baz_service', 'stdClass')->setPublic(\false);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_private_frozen.php', $dumper->dump());
    }
    public function testServiceLocator()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo_service', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ServiceLocator::class)->setPublic(\true)->addArgument(['bar' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('bar_service')), 'baz' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\TypedReference('baz_service', 'stdClass')), 'nil' => $nil = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('nil'))]);
        // no method calls
        $container->register('translator.loader_1', 'stdClass')->setPublic(\true);
        $container->register('translator.loader_1_locator', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ServiceLocator::class)->setPublic(\false)->addArgument(['translator.loader_1' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_1'))]);
        $container->register('translator_1', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\StubbedTranslator::class)->setPublic(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_1_locator'));
        // one method calls
        $container->register('translator.loader_2', 'stdClass')->setPublic(\true);
        $container->register('translator.loader_2_locator', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ServiceLocator::class)->setPublic(\false)->addArgument(['translator.loader_2' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_2'))]);
        $container->register('translator_2', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\StubbedTranslator::class)->setPublic(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_2_locator'))->addMethodCall('addResource', ['db', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_2'), 'nl']);
        // two method calls
        $container->register('translator.loader_3', 'stdClass')->setPublic(\true);
        $container->register('translator.loader_3_locator', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ServiceLocator::class)->setPublic(\false)->addArgument(['translator.loader_3' => new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_3'))]);
        $container->register('translator_3', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\StubbedTranslator::class)->setPublic(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_3_locator'))->addMethodCall('addResource', ['db', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_3'), 'nl'])->addMethodCall('addResource', ['db', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('translator.loader_3'), 'en']);
        $nil->setValues([null]);
        $container->register('bar_service', 'stdClass')->setArguments([new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('baz_service')])->setPublic(\true);
        $container->register('baz_service', 'stdClass')->setPublic(\false);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_locator.php', $dumper->dump());
    }
    public function testServiceSubscriber()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo_service', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\TestServiceSubscriber::class)->setPublic(\true)->setAutowired(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference(\_PhpScoper5eddef0da618a\Psr\Container\ContainerInterface::class))->addTag('container.service_subscriber', ['key' => 'bar', 'id' => \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\TestServiceSubscriber::class]);
        $container->register(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\TestServiceSubscriber::class, \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\TestServiceSubscriber::class)->setPublic(\true);
        $container->register(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\CustomDefinition::class, \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\CustomDefinition::class)->setPublic(\false);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_subscriber.php', $dumper->dump());
    }
    public function testPrivateWithIgnoreOnInvalidReference()
    {
        require_once self::$fixturesPath . '/includes/classes.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('not_invalid', 'BazClass')->setPublic(\false);
        $container->register('bar', 'BarClass')->setPublic(\true)->addMethodCall('setBaz', [new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('not_invalid', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Private_With_Ignore_On_Invalid_Reference']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Private_With_Ignore_On_Invalid_Reference();
        $this->assertInstanceOf('BazClass', $container->get('bar')->getBaz());
    }
    public function testArrayParameters()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->setParameter('array_1', [123]);
        $container->setParameter('array_2', [__DIR__]);
        $container->register('bar', 'BarClass')->setPublic(\true)->addMethodCall('setBaz', ['%array_1%', '%array_2%', '%%array_1%%', [123]]);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_array_params.php', \str_replace('\\\\Dumper', '/Dumper', $dumper->dump(['file' => self::$fixturesPath . '/php/services_array_params.php'])));
    }
    public function testExpressionReferencingPrivateService()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('private_bar', 'stdClass')->setPublic(\false);
        $container->register('private_foo', 'stdClass')->setPublic(\false);
        $container->register('public_foo', 'stdClass')->setPublic(\true)->addArgument(new \_PhpScoper5eddef0da618a\Symfony\Component\ExpressionLanguage\Expression('service("private_foo").bar'));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_private_in_expression.php', $dumper->dump());
    }
    public function testUninitializedReference()
    {
        $container = (include self::$fixturesPath . '/containers/container_uninitialized_ref.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_uninitialized_ref.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Uninitialized_Reference']));
        require self::$fixturesPath . '/php/services_uninitialized_ref.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Uninitialized_Reference();
        $bar = $container->get('bar');
        $this->assertNull($bar->foo1);
        $this->assertNull($bar->foo2);
        $this->assertNull($bar->foo3);
        $this->assertNull($bar->closures[0]());
        $this->assertNull($bar->closures[1]());
        $this->assertNull($bar->closures[2]());
        $this->assertSame([], \iterator_to_array($bar->iter));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Uninitialized_Reference();
        $container->get('foo1');
        $container->get('baz');
        $bar = $container->get('bar');
        $this->assertEquals(new \stdClass(), $bar->foo1);
        $this->assertNull($bar->foo2);
        $this->assertEquals(new \stdClass(), $bar->foo3);
        $this->assertEquals(new \stdClass(), $bar->closures[0]());
        $this->assertNull($bar->closures[1]());
        $this->assertEquals(new \stdClass(), $bar->closures[2]());
        $this->assertEquals(['foo1' => new \stdClass(), 'foo3' => new \stdClass()], \iterator_to_array($bar->iter));
    }
    /**
     * @dataProvider provideAlmostCircular
     */
    public function testAlmostCircular($visibility)
    {
        $container = (include self::$fixturesPath . '/containers/container_almost_circular.php');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $container = 'Symfony_DI_PhpDumper_Test_Almost_Circular_' . \ucfirst($visibility);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_almost_circular_' . $visibility . '.php', $dumper->dump(['class' => $container]));
        require self::$fixturesPath . '/php/services_almost_circular_' . $visibility . '.php';
        $container = new $container();
        $foo = $container->get('foo');
        $this->assertSame($foo, $foo->bar->foobar->foo);
        $foo2 = $container->get('foo2');
        $this->assertSame($foo2, $foo2->bar->foobar->foo);
        $this->assertSame([], (array) $container->get('foobar4'));
        $foo5 = $container->get('foo5');
        $this->assertSame($foo5, $foo5->bar->foo);
        $manager = $container->get('manager');
        $this->assertEquals(new \stdClass(), $manager);
        $manager = $container->get('manager2');
        $this->assertEquals(new \stdClass(), $manager);
        $foo6 = $container->get('foo6');
        $this->assertEquals((object) ['bar6' => (object) []], $foo6);
        $this->assertInstanceOf(\stdClass::class, $container->get('root'));
        $manager3 = $container->get('manager3');
        $listener3 = $container->get('listener3');
        $this->assertSame($manager3, $listener3->manager);
        $listener4 = $container->get('listener4');
        $this->assertInstanceOf('stdClass', $listener4);
    }
    public function provideAlmostCircular()
    {
        (yield ['public']);
        (yield ['private']);
    }
    public function testDeepServiceGraph()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $loader = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \_PhpScoper5eddef0da618a\Symfony\Component\Config\FileLocator(self::$fixturesPath . '/yaml'));
        $loader->load('services_deep_graph.yml');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dumper->dump();
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_deep_graph.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Deep_Graph']));
        require self::$fixturesPath . '/php/services_deep_graph.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Deep_Graph();
        $this->assertInstanceOf(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Dumper\FooForDeepGraph::class, $container->get('foo'));
        $this->assertEquals((object) ['p2' => (object) ['p3' => (object) []]], $container->get('foo')->bClone);
    }
    public function testInlineSelfRef()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $bar = (new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition('_PhpScoper5eddef0da618a\\App\\Bar'))->setProperty('foo', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('_PhpScoper5eddef0da618a\\App\\Foo'));
        $baz = (new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition('_PhpScoper5eddef0da618a\\App\\Baz'))->setProperty('bar', $bar)->addArgument($bar);
        $container->register('_PhpScoper5eddef0da618a\\App\\Foo')->setPublic(\true)->addArgument($baz);
        $container->getCompiler()->getPassConfig();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_inline_self_ref.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Inline_Self_Ref']));
    }
    public function testHotPathOptimizations()
    {
        $container = (include self::$fixturesPath . '/containers/container_inline_requires.php');
        $container->setParameter('inline_requires', \true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dump = $dumper->dump(['hot_path_tag' => 'container.hot_path', 'inline_class_loader_parameter' => 'inline_requires', 'file' => self::$fixturesPath . '/php/services_inline_requires.php']);
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $dump = \str_replace("'\\\\includes\\\\HotPath\\\\", "'/includes/HotPath/", $dump);
        }
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_inline_requires.php', $dump);
    }
    public function testDumpHandlesLiteralClassWithRootNamespace()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', '\\stdClass')->setPublic(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Literal_Class_With_Root_Namespace']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Literal_Class_With_Root_Namespace();
        $this->assertInstanceOf('stdClass', $container->get('foo'));
    }
    public function testDumpHandlesObjectClassNames()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['class' => 'stdClass']));
        $container->setDefinition('foo', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Parameter('class')));
        $container->setDefinition('bar', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition('stdClass', [new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo')]))->setPublic(\true);
        $container->setParameter('inline_requires', \true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Object_Class_Name', 'inline_class_loader_parameter' => 'inline_requires']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Object_Class_Name();
        $this->assertInstanceOf('stdClass', $container->get('bar'));
    }
    public function testUninitializedSyntheticReference()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(\true)->setSynthetic(\true);
        $container->register('bar', 'stdClass')->setPublic(\true)->setShared(\false)->setProperty('foo', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo', \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder::IGNORE_ON_UNINITIALIZED_REFERENCE));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_UninitializedSyntheticReference', 'inline_class_loader_parameter' => 'inline_requires']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_UninitializedSyntheticReference();
        $this->assertEquals((object) ['foo' => null], $container->get('bar'));
        $container->set('foo', (object) [123]);
        $this->assertEquals((object) ['foo' => (object) [123]], $container->get('bar'));
    }
    public function testAdawsonContainer()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $loader = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \_PhpScoper5eddef0da618a\Symfony\Component\Config\FileLocator(self::$fixturesPath . '/yaml'));
        $loader->load('services_adawson.yml');
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_adawson.php', $dumper->dump());
    }
    /**
     * @group legacy
     * @expectedDeprecation The "private" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "private_alias" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "decorated_private" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "decorated_private_alias" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "private_not_inlined" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "private_not_removed" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "private_child" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     * @expectedDeprecation The "private_parent" service is private, getting it from the container is deprecated since Symfony 3.2 and will fail in 4.0. You should either make the service public, or stop using the container directly and use dependency injection instead.
     */
    public function testLegacyPrivateServices()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $loader = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \_PhpScoper5eddef0da618a\Symfony\Component\Config\FileLocator(self::$fixturesPath . '/yaml'));
        $loader->load('services_legacy_privates.yml');
        $container->setDefinition('private_child', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ChildDefinition('foo'));
        $container->setDefinition('private_parent', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ChildDefinition('private'));
        $container->getDefinition('private')->setPrivate(\true);
        $container->getDefinition('private_not_inlined')->setPrivate(\true);
        $container->getDefinition('private_not_removed')->setPrivate(\true);
        $container->getDefinition('decorated_private')->setPrivate(\true);
        $container->getDefinition('private_child')->setPrivate(\true);
        $container->getAlias('decorated_private_alias')->setPrivate(\true);
        $container->getAlias('private_alias')->setPrivate(\true);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath . '/php/services_legacy_privates.php', $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Legacy_Privates', 'file' => self::$fixturesPath . '/php/services_legacy_privates.php']));
        require self::$fixturesPath . '/php/services_legacy_privates.php';
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Legacy_Privates();
        $container->get('private');
        $container->get('private_alias');
        $container->get('alias_to_private');
        $container->get('decorated_private');
        $container->get('decorated_private_alias');
        $container->get('private_not_inlined');
        $container->get('private_not_removed');
        $container->get('private_child');
        $container->get('private_parent');
        $container->get('public_child');
    }
    /**
     * This test checks the trigger of a deprecation note and should not be removed in major releases.
     *
     * @group legacy
     * @expectedDeprecation The "foo" service is deprecated. You should stop using it, as it will soon be removed.
     */
    public function testPrivateServiceTriggersDeprecation()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(\false)->setDeprecated(\true);
        $container->register('bar', 'stdClass')->setPublic(\true)->setProperty('foo', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo'));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Private_Service_Triggers_Deprecation']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Private_Service_Triggers_Deprecation();
        $container->get('bar');
    }
    /**
     * @group legacy
     * @expectedDeprecation Parameter names will be made case sensitive in Symfony 4.0. Using "foo" instead of "Foo" is deprecated since Symfony 3.4.
     * @expectedDeprecation Parameter names will be made case sensitive in Symfony 4.0. Using "FOO" instead of "Foo" is deprecated since Symfony 3.4.
     * @expectedDeprecation Parameter names will be made case sensitive in Symfony 4.0. Using "bar" instead of "BAR" is deprecated since Symfony 3.4.
     */
    public function testParameterWithMixedCase()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['Foo' => 'bar', 'BAR' => 'foo']));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Parameter_With_Mixed_Case']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Parameter_With_Mixed_Case();
        $this->assertSame('bar', $container->getParameter('foo'));
        $this->assertSame('bar', $container->getParameter('FOO'));
        $this->assertSame('foo', $container->getParameter('bar'));
        $this->assertSame('foo', $container->getParameter('BAR'));
    }
    /**
     * @group legacy
     * @expectedDeprecation Parameter names will be made case sensitive in Symfony 4.0. Using "FOO" instead of "foo" is deprecated since Symfony 3.4.
     */
    public function testParameterWithLowerCase()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder(new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['foo' => 'bar']));
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Parameter_With_Lower_Case']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Parameter_With_Lower_Case();
        $this->assertSame('bar', $container->getParameter('FOO'));
    }
    /**
     * @group legacy
     * @expectedDeprecation Service identifiers will be made case sensitive in Symfony 4.0. Using "foo" instead of "Foo" is deprecated since Symfony 3.3.
     * @expectedDeprecation The "Foo" service is deprecated. You should stop using it, as it will soon be removed.
     */
    public function testReferenceWithLowerCaseId()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('Bar', 'stdClass')->setProperty('foo', new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference('foo'))->setPublic(\true);
        $container->register('Foo', 'stdClass')->setDeprecated();
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Reference_With_Lower_Case_Id']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Reference_With_Lower_Case_Id();
        $this->assertEquals((object) ['foo' => (object) []], $container->get('Bar'));
    }
    public function testScalarService()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'string')->setPublic(\true)->setFactory([\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Tests\Fixtures\ScalarFactory::class, 'getSomeValue']);
        $container->compile();
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        eval('?>' . $dumper->dump(['class' => 'Symfony_DI_PhpDumper_Test_Scalar_Service']));
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_Test_Scalar_Service();
        $this->assertTrue($container->has('foo'));
        $this->assertSame('some value', $container->get('foo'));
    }
    public function testAliasCanBeFoundInTheDumpedContainerWhenBothTheAliasAndTheServiceArePublic()
    {
        $container = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder();
        $container->register('foo', 'stdClass')->setPublic(\true);
        $container->setAlias('bar', 'foo')->setPublic(\true);
        $container->compile();
        // Bar is found in the compiled container
        $service_ids = $container->getServiceIds();
        $this->assertContains('bar', $service_ids);
        $dumper = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
        $dump = $dumper->dump(['class' => 'Symfony_DI_PhpDumper_AliasesCanBeFoundInTheDumpedContainer']);
        eval('?>' . $dump);
        $container = new \_PhpScoper5eddef0da618a\Symfony_DI_PhpDumper_AliasesCanBeFoundInTheDumpedContainer();
        // Bar should still be found in the compiled container
        $service_ids = $container->getServiceIds();
        $this->assertContains('bar', $service_ids);
    }
}
class Rot13EnvVarProcessor implements \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        return \str_rot13($getEnv($name));
    }
    public static function getProvidedTypes()
    {
        return ['rot13' => 'string'];
    }
}
class FooForDeepGraph
{
    public $bClone;
    public function __construct(\stdClass $a, \stdClass $b)
    {
        // clone to verify that $b has been fully initialized before
        $this->bClone = clone $b;
    }
}
