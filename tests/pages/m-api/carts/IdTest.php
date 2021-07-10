<?php

namespace MiaoxingTest\Cart\Pages\MApi\Carts;

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\Service\Tester;
use Miaoxing\Plugin\Test\BaseTestCase;
use Miaoxing\Product\Service\ProductModel;

class IdTest extends BaseTestCase
{
    public function testPatch()
    {
        $product = $this->createProduct();

        ['data' => $cart] = Cart::create(['skuId' => $product->skus[0]->id, 'quantity' => 1]);

        $ret = Tester
            ::request([
                'skuId' => $product->skus[1]->id,
                'quantity' => 2,
            ])
            ->patch('/m-api/carts/' . $cart->id);
        $this->assertRetSuc($ret);

        $cart->reload();
        $this->assertSame($product->skus[1]->id, $cart->skuId);
        $this->assertSame(2, $cart->quantity);
    }

    protected function createProduct(array $data = []): ProductModel
    {
        // 创建测试商品
        $ret = Tester::postAdminApi('products', array_merge([
            'name' => '测试商品',
            'spec' => [
                'specs' => [
                    [
                        'name' => '颜色',
                        'values' => [
                            [
                                'name' => '红色',
                            ],
                            [
                                'name' => '绿色',
                            ],
                        ],
                    ],
                    [
                        'name' => '尺寸',
                        'values' => [
                            [
                                'name' => 'X',
                            ],
                        ],
                    ],
                ],
            ],
            'skus' => [
                [
                    'price' => 20,
                    'stockNum' => 10,
                    'specValues' => [
                        [
                            'name' => '红色',
                            'specName' => '颜色',
                        ],
                        [
                            'name' => 'X',
                            'specName' => '尺寸',
                        ],
                    ],
                ],
                [
                    'price' => 20,
                    'stockNum' => 10,
                    'specValues' => [
                        [
                            'name' => '绿色',
                            'specName' => '颜色',
                        ],
                        [
                            'name' => 'X',
                            'specName' => '尺寸',
                        ],
                    ],
                ],
            ],
        ], $data));

        /** @var ProductModel $product */
        $product = $ret->getMetadata('model');

        return $product;
    }
}
