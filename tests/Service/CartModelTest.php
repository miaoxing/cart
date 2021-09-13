<?php

namespace MiaoxingTest\Cart\Service;

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Plugin\Service\Ret;
use Miaoxing\Plugin\Test\BaseTestCase;
use Miaoxing\Product\Service\Product;
use Miaoxing\Product\Service\ProductModel;

class CartModelTest extends BaseTestCase
{
    /**
     * @param string|int $quantity
     * @param Ret $ret
     * @dataProvider providerForUpdateQuantity
     */
    public function testUpdateQuantity($quantity, Ret $ret)
    {
        $product = $this->createProduct([
            'name' => '购物车测试商品1',
        ], [
            'price' => 20,
            'stockNum' => 10,
        ]);

        ['data' => $cart] = Cart::create([
            'skuId' => $product->skus[0]->id,
            'quantity' => 1,
        ]);

        $updateRet = $cart->updateQuantity($quantity);
        $this->assertSameRet($updateRet, $ret);

        if ($ret->isSuc()) {
            $this->assertSame($quantity, $cart->quantity);
        }
    }

    public static function providerForUpdateQuantity(): array
    {
        return [
            [
                'invalid number',
                err('商品数量必须是大于0的整数', -1),
            ],
            [
                2,
                suc('更改成功'),
            ],
            [
                1,
                err('数量未更改'),
            ],
            [
                11,
                err('商品数量不能超过库存'),
            ],
        ];
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
