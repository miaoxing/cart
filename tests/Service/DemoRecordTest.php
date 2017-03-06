<?php

namespace MiaoxingTest\Cart\Service;

use Miaoxing\Plugin\Test\BaseTestCase;

/**
 * 演示服务
 */
class CartRecordTest extends BaseTestCase
{
    /**
     * 获取名称
     */
    public function testGetName()
    {
        $this->assertSame('cart', wei()->cartRecord->getName());
    }
}
