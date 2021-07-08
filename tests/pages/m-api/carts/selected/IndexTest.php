<?php

namespace MiaoxingTest\Cart\Pages\MApi\Carts\Selected;

use Miaoxing\Cart\Service\CartConfigModel;
use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Test\BaseTestCase;

class IndexTest extends BaseTestCase
{
    public function testPut()
    {
        $ret = Tester::request(['ids' => [1, 2]])->put('/m-api/carts/selected');
        $this->assertRetSuc($ret);

        $config = CartConfigModel::findOrInitMine();
        $this->assertSame([1, 2], $config->selectedIds);
    }
}
