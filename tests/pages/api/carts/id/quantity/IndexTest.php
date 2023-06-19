<?php

namespace MiaoxingTest\Cart\Pages\Api\Carts\Id\Quantity;

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Test\BaseTestCase;
use Miaoxing\Product\Service\Product;
use Miaoxing\Product\Service\ProductModel;

class IndexTest extends BaseTestCase
{
    public function testPut()
    {
        // 创建测试商品
        $product = $this->createProduct([
            'name' => '更改购物车的商品',
        ], [
            'price' => 20,
            'stockNum' => 10,
        ]);

        ['data' => $cart] = $ret = Cart::create([
            'skuId' => $product->skus[0]->id,
            'quantity' => 1,
        ]);
        $this->assertRetSuc($ret);

        $ret = Tester::request(['quantity' => 5])->put(sprintf('/api/carts/%s/quantity', $cart->id));
        $this->assertRetSuc($ret);

        $cart->reload();
        $this->assertSame(5, $cart->quantity);

        $ret = Tester::request(['quantity' => 3])->put(sprintf('/api/carts/%s/quantity', $cart->id));
        $this->assertRetSuc($ret);

        $cart->reload();
        $this->assertSame(3, $cart->quantity);
    }

    protected function createProduct(array $data = [], array $sku = []): ProductModel
    {
        // 创建测试商品
        $ret = Product::create(array_merge([
            'name' => '测试商品',
            'spec' => [
                'specs' => Product::getDefaultSpecs(),
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
