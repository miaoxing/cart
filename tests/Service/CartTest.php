<?php

namespace MiaoxingTest\Cart\Service;

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\Service\User;
use Miaoxing\Plugin\Test\BaseTestCase;
use Miaoxing\Product\Service\Product;
use Miaoxing\Product\Service\ProductModel;
use Wei\Ret;

class CartTest extends BaseTestCase
{
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

        return $ret['data'];
    }

    /**
     * @param array $data
     * @param Ret $ret
     * @dataProvider providerForCreate
     */
    public function testCreate(array $data, Ret $ret)
    {
        $product = $this->createProduct();

        $createRet = Cart::create($data + ['skuId' => $product->skus[0]->id]);

        if ($ret->isErr()) {
            $this->assertSameRet($ret, $createRet);
        } else {
            $this->assertSame($ret->getMessage(), $createRet->getMessage());
            $this->assertSame($product->skus[0]->price, $createRet->getData()->addedPrice);
            $this->assertSame(2, $createRet->getData()->quantity);
        }
    }

    public static function providerForCreate(): array
    {
        return [
            [
                [
                    'skuId' => null,
                ],
                err('商品不存在'),
            ],
            [
                [
                    'quantity' => 'a',
                ],
                err('商品数量必须是大于0的整数', -1),
            ],
            [
                [
                    'quantity' => -1,
                ],
                err('商品数量必须是大于0的整数', -1),
            ],
            [
                [
                    'quantity' => 11,
                ],
                err('商品数量不能超过库存数量', -1),
            ],
            [
                [
                    'quantity' => 2,
                ],
                suc([
                    'message' => '加入成功',
                ]),
            ],
        ];
    }

    /**
     * @param array|\Closure $data
     * @param Ret $ret
     * @throws \Exception
     * @dataProvider providerForUpdate
     */
    public function testUpdate($data, Ret $ret)
    {
        if ($data instanceof \Closure) {
            $data = $data($this);
        }

        $product = $this->createProduct();

        ['data' => $cart] = Cart::create(['skuId' => $product->skus[0]->id, 'quantity' => 2]);

        $data['id'] = $cart->id;
        $updateRet = Cart::update($data + ['skuId' => $cart->skuId]);

        if ($ret->isErr()) {
            $this->assertSameRet($ret, $updateRet);
        } else {
            $this->assertSame($ret->getMessage(), $updateRet->getMessage());
            $this->assertSame($data['quantity'], $updateRet['data']->quantity);
        }
    }

    public static function providerForUpdate(): array
    {
        return [
            [
                [
                    'skuId' => 'invalid',
                ],
                err('该商品不存在'),
            ],
            [
                static function (self $test) {
                    return [
                        'skuId' => $test->createProduct()->skus[0]->id,
                    ];
                },
                err('该商品属性不存在'),
            ],
            [
                [
                    'quantity' => 100,
                ],
                err('库存不足'),
            ],
            [
                [
                    'quantity' => 3,
                ],
                suc('更新成功'),
            ],
        ];
    }

    public function testCreateOrUpdate()
    {
        User::loginById(1);

        // 创建测试商品
        $ret = Product::create([
            'name' => '重复加入购物车的测试商品',
            'spec' => [
                'specs' => Product::getDefaultSpecs(),
            ],
            'skus' => [
                [
                    'price' => 20,
                    'stockNum' => 1000,
                    'specValues' => [
                        [
                            'name' => '默认',
                            'specName' => '默认',
                        ],
                    ],
                ],
            ],
        ]);

        $product = $ret['data'];
        $firstSku = $product->skus[0];

        // 首次加入购物车
        $ret = Cart::createOrUpdate([
            'skuId' => $firstSku->id,
            'quantity' => 1,
        ]);
        $this->assertRetSuc($ret);
        $this->assertFalse($ret['exists']);

        // 再次加入购物车
        $ret = Cart::createOrUpdate([
            'skuId' => $firstSku->id,
            'quantity' => 1,
        ]);
        $this->assertRetSuc($ret);
        $this->assertTrue($ret['exists']);

        // 商品降价,仍然加入到原来的购物车
        $firstSku->save(['price' => 19]);

        $ret = Cart::createOrUpdate([
            'skuId' => $firstSku->id,
            'quantity' => 1,
        ]);
        $this->assertRetSuc($ret);
        $this->assertTrue($ret['exists']);
    }

    public function testUpdateQuantity()
    {
        User::loginById(1);

        // 创建测试商品
        $ret = Product::create([
            'name' => '更改购物车数量的测试商品',
            'spec' => [
                'specs' => Product::getDefaultSpecs(),
            ],
            'skus' => [
                [
                    'price' => 20,
                    'stockNum' => 10,
                    'specValues' => [
                        [
                            'name' => '默认',
                            'specName' => '默认',
                        ],
                    ],
                ],
            ],
            'maxOrderQuantity' => 3,
        ]);

        $product = $ret['data'];
        $firstSku = $product->skus[0];

        // 加入购物车
        $ret = Cart::createOrUpdate([
            'skuId' => $firstSku->id,
            'quantity' => 2,
        ]);
        $this->assertRetSuc($ret);
        $cart = CartModel::findOrFail($ret['data']['id']);

        $ret = $cart->updateQuantity(0);
        $this->assertRetErr($ret, '商品数量必须是大于0的整数');

        $ret = $cart->updateQuantity(2);
        $this->assertRetErr($ret, '数量未更改');

        $ret = $cart->updateQuantity(11);
        $this->assertRetErr($ret, '商品数量不能超过库存');

        $ret = $cart->updateQuantity(1);
        $this->assertRetSuc($ret);

        $ret = $cart->updateQuantity(3);
        $this->assertRetSuc($ret);

        $ret = $cart->updateQuantity(4);
        $this->assertRetErr($ret, '此商品每人限购3件，购物车已有3件，请去购物车继续购买');
    }

    public function testCheckMaxOrderQuantity()
    {
        User::loginById(1);

        // 创建测试商品
        $product = $this->createProduct([
            'name' => '限购的测试商品1',
            'maxOrderQuantity' => 2,
        ], [
            'price' => 20,
            'stockNum' => 10,
        ]);

        $skuId = $product->skus[0]->id;

        // 加入购物车
        $ret = Cart::createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '3',
        ]);
        $this->assertRetErr($ret, '此商品每人限购2件，请返回修改');

        $ret = Cart::createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);
        $this->assertRetSuc($ret);

        $ret = Cart::createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);
        $this->assertRetSuc($ret);

        $ret = Cart::createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);
        $this->assertRetErr($ret, '此商品每人限购2件，购物车已有2件，请去购物车继续购买');
    }

    public function ignoreTestCheckLimitationWithOrder()
    {
        wei()->curUser->loginById(1);

        // 创建测试商品
        $product = wei()->product();
        $ret = $product->create([
            'name' => '限购的测试商品2',
            'quantity' => 10,
            'price' => '20.00',
            'images' => [
                '/assets/mall/product/placeholder.gif',
            ],
            'limitation' => 2,
        ]);
        $this->assertRetSuc($ret);

        $skuId = $product->getFirstSku()->get('id');

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);
        $this->assertRetSuc($ret);

        // 下单
        $order = wei()->order();
        $ret = $order->create([
            'cartId' => $ret['data']['id'],
            'payType' => 'test',
            'addressId' => 1,
            'userLogisticsId' => 1,
        ]);
        $this->assertRetSuc($ret);

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '3',
        ]);
        $this->assertRetErr($ret, '此商品每人限购2件，订单已有1件，请返回修改', -4);
    }

    public function ignoreTestCheckLimitationWithCartAndOrder()
    {
        wei()->curUser->loginById(1);

        // 创建测试商品
        $product = wei()->product();
        $ret = $product->create([
            'name' => '限购的测试商品3',
            'quantity' => 10,
            'price' => '20.00',
            'images' => [
                '/assets/mall/product/placeholder.gif',
            ],
            'limitation' => 4,
        ]);
        $this->assertRetSuc($ret);

        $skuId = $product->getFirstSku()->get('id');

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '2',
        ]);
        $this->assertRetSuc($ret);

        $order = wei()->order();
        $ret = $order->create([
            'cartId' => $ret['data']['id'],
            'payType' => 'test',
            'addressId' => 1,
            'userLogisticsId' => 1,
        ]);
        $this->assertRetSuc($ret);

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);
        $this->assertRetSuc($ret);

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '2',
        ]);
        $this->assertRetErr($ret, '此商品每人限购4件，购物车已有1件，订单已有2件，请去购物车继续购买', -4);
    }

    public function ignoreTestCheckLimitationWithOrderReachLimit()
    {
        wei()->curUser->loginById(1);

        // 创建测试商品
        $product = wei()->product();
        $ret = $product->create([
            'name' => '限购的测试商品4',
            'quantity' => 10,
            'price' => '20.00',
            'images' => [
                '/assets/mall/product/placeholder.gif',
            ],
            'limitation' => 2,
        ]);
        $this->assertRetSuc($ret);

        $skuId = $product->getFirstSku()->get('id');

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '2',
        ]);
        $this->assertRetSuc($ret);

        $order = wei()->order();
        $ret = $order->create([
            'cartId' => $ret['data']['id'],
            'payType' => 'test',
            'addressId' => 1,
            'userLogisticsId' => 1,
        ]);
        $this->assertRetSuc($ret);

        $ret = wei()->cart->createOrUpdate([
            'skuId' => $skuId,
            'quantity' => '1',
        ]);

        $this->assertRetErr($ret, '此商品每人限购2件，订单已有2件，请去订单中查看', -4);
    }

    public function testCreateProductNotFound()
    {
        $ret = Cart::create(['skuId' => -1]);
        $this->assertRetErr($ret, '商品不存在');
    }

    public function testCreateProductIsAllowAddCart()
    {
        $product = $this->createProduct(['isAllowAddCart' => false]);
        $ret = Cart::create(['skuId' => $product->skus[0]->id]);
        $this->assertRetErr($ret, '该商品不可加入购物车');
    }

    public function testCreateStockNumNotEnough()
    {
        $ret = Product::create([
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
                                'name' => '蓝色',
                            ],
                        ],
                    ],
                    [
                        'name' => '尺寸',
                        'values' => [
                            [
                                'name' => 'M',
                            ],
                            [
                                'name' => 'L',
                            ],
                        ],
                    ],
                ],
            ],
            'skus' => [
                [
                    'price' => 20,
                    'stockNum' => 0,
                    'specValues' => [
                        [
                            'name' => '红色',
                            'specName' => '颜色',
                        ],
                        [
                            'name' => 'M',
                            'specName' => '尺寸',
                        ],
                    ],
                ],
                [
                    'price' => 20,
                    'stockNum' => 10,
                    'specValues' => [
                        [
                            'name' => '蓝色',
                            'specName' => '颜色',
                        ],
                        [
                            'name' => 'L',
                            'specName' => '尺寸',
                        ],
                    ],
                ],
            ],
        ]);

        $product = $ret['data'];

        $ret = Cart::create(['skuId' => $product->skus[0]->id, 1]);
        $this->assertRetErr($ret, '该商品规格已售罄');
    }
}
