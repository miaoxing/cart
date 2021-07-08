<?php

namespace MiaoxingTest\Cart\Pages\MApi\Carts;

use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Test\BaseTestCase;

class CountTest extends BaseTestCase
{
    public function testGet()
    {
        $ret = Tester::get('/m-api/carts/count');

        $this->assertRetSuc($ret);

        $this->assertIsInt($ret['data']['count']);
    }
}
