<?php

namespace MiaoxingTest\Cart\Pages\Api\Carts;

use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Test\BaseTestCase;

class CountTest extends BaseTestCase
{
    public function testGet()
    {
        $ret = Tester::get('/api/carts/count');

        $this->assertRetSuc($ret);

        $this->assertIsInt($ret['data']['count']);
    }
}
