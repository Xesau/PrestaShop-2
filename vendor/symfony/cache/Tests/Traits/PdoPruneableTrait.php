<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5ea00cc67502b\Symfony\Component\Cache\Tests\Traits;

use Doctrine\DBAL\Statement;
use PDO;
use ReflectionObject;
use function count;
use function sprintf;

trait PdoPruneableTrait
{
    protected function isPruned($cache, $name)
    {
        $o = new ReflectionObject($cache);
        if (!$o->hasMethod('getConnection')) {
            self::fail('Cache does not have "getConnection()" method.');
        }
        $getPdoConn = $o->getMethod('getConnection');
        $getPdoConn->setAccessible(true);
        /** @var Statement $select */
        $select = $getPdoConn->invoke($cache)->prepare('SELECT 1 FROM cache_items WHERE item_id LIKE :id');
        $select->bindValue(':id', sprintf('%%%s', $name));
        $select->execute();
        return 0 === count($select->fetchAll(PDO::FETCH_COLUMN));
    }
}
