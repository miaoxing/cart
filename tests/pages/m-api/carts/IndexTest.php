<?php

namespace MiaoxingTest\Cart\Pages\MApi\Carts;

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Service\User;
use Miaoxing\Plugin\Test\BaseTestCase;
use Miaoxing\Product\Service\ProductModel;
use Miaoxing\User\Service\UserModel;

class IndexTest extends BaseTestCase
{
    public function testGet()
    {
        User::loginByModel(UserModel::save());

        $product = $this->createProduct();

        ['data' => $cart] = Cart::create(['skuId' => $product->skus[0]->id, 'quantity' => 1]);

        $ret = Tester::get('/m-api/carts');

        $this->assertRetSuc($ret);
        $this->assertSame($cart->id, $ret['data'][0]['id']);
        $this->assertRetSuc($ret['data'][0]['createOrder']);
        $this->assertSame([], $ret['selected']);
    }

    protected function createProduct(array $data = [], array $sku = []): ProductModel
    {
        // 创建测试商品
        $ret = Tester::postAdminApi('products', array_merge([
            'name' => '测试商品',
            'spec' => [
                'specs' => ProductModel::getDefaultSpecs(),
            ],
            'skus' => [
                array_merge([
                    'price' => 20,
                    'stockNum' => 10,
                    'specValues' => [
                        [
                            'name' => '默认',
                            'specName' => '默认',
                        ],
                    ],
                ], $sku),
            ],
        ], $data));

        /** @var ProductModel $product */
        $product = $ret->getMetadata('model');

        return $product;
    }
}
