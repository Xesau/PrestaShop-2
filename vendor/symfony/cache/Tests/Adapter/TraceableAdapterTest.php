<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5eddef0da618a\Symfony\Component\Cache\Tests\Adapter;

use _PhpScoper5eddef0da618a\Symfony\Component\Cache\Adapter\FilesystemAdapter;
use _PhpScoper5eddef0da618a\Symfony\Component\Cache\Adapter\TraceableAdapter;
/**
 * @group time-sensitive
 */
class TraceableAdapterTest extends \_PhpScoper5eddef0da618a\Symfony\Component\Cache\Tests\Adapter\AdapterTestCase
{
    protected $skippedTests = ['testPrune' => 'TraceableAdapter just proxies'];
    public function createCachePool($defaultLifetime = 0)
    {
        return new \_PhpScoper5eddef0da618a\Symfony\Component\Cache\Adapter\TraceableAdapter(new \_PhpScoper5eddef0da618a\Symfony\Component\Cache\Adapter\FilesystemAdapter('', $defaultLifetime));
    }
    public function testGetItemMissTrace()
    {
        $pool = $this->createCachePool();
        $pool->getItem('k');
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('getItem', $call->name);
        $this->assertSame(['k' => \false], $call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(1, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testGetItemHitTrace()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('k')->set('foo');
        $pool->save($item);
        $pool->getItem('k');
        $calls = $pool->getCalls();
        $this->assertCount(3, $calls);
        $call = $calls[2];
        $this->assertSame(1, $call->hits);
        $this->assertSame(0, $call->misses);
    }
    public function testGetItemsMissTrace()
    {
        $pool = $this->createCachePool();
        $arg = ['k0', 'k1'];
        $items = $pool->getItems($arg);
        foreach ($items as $item) {
        }
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('getItems', $call->name);
        $this->assertSame(['k0' => \false, 'k1' => \false], $call->result);
        $this->assertSame(2, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testHasItemMissTrace()
    {
        $pool = $this->createCachePool();
        $pool->hasItem('k');
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('hasItem', $call->name);
        $this->assertSame(['k' => \false], $call->result);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testHasItemHitTrace()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('k')->set('foo');
        $pool->save($item);
        $pool->hasItem('k');
        $calls = $pool->getCalls();
        $this->assertCount(3, $calls);
        $call = $calls[2];
        $this->assertSame('hasItem', $call->name);
        $this->assertSame(['k' => \true], $call->result);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testDeleteItemTrace()
    {
        $pool = $this->createCachePool();
        $pool->deleteItem('k');
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('deleteItem', $call->name);
        $this->assertSame(['k' => \true], $call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(0, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testDeleteItemsTrace()
    {
        $pool = $this->createCachePool();
        $arg = ['k0', 'k1'];
        $pool->deleteItems($arg);
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('deleteItems', $call->name);
        $this->assertSame(['keys' => $arg, 'result' => \true], $call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(0, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testSaveTrace()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('k')->set('foo');
        $pool->save($item);
        $calls = $pool->getCalls();
        $this->assertCount(2, $calls);
        $call = $calls[1];
        $this->assertSame('save', $call->name);
        $this->assertSame(['k' => \true], $call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(0, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testSaveDeferredTrace()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('k')->set('foo');
        $pool->saveDeferred($item);
        $calls = $pool->getCalls();
        $this->assertCount(2, $calls);
        $call = $calls[1];
        $this->assertSame('saveDeferred', $call->name);
        $this->assertSame(['k' => \true], $call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(0, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
    public function testCommitTrace()
    {
        $pool = $this->createCachePool();
        $pool->commit();
        $calls = $pool->getCalls();
        $this->assertCount(1, $calls);
        $call = $calls[0];
        $this->assertSame('commit', $call->name);
        $this->assertTrue($call->result);
        $this->assertSame(0, $call->hits);
        $this->assertSame(0, $call->misses);
        $this->assertNotEmpty($call->start);
        $this->assertNotEmpty($call->end);
    }
}
