<?php

namespace Miaoxing\Cart\Resource;

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\Resource\BaseResource;
use Miaoxing\Plugin\Resource\MissingValue;
use Miaoxing\Product\Resource\ProductResource;
use Miaoxing\Product\Resource\SkuResource;
use Wei\Ret;

class CartResource extends BaseResource
{
    public function transform(CartModel $cart): array
    {
        return [
            $this->extract($cart, [
                'id',
                'productId',
                'skuId',
                'quantity',
                'changedPrice',
                'addedPrice',
                'configs',
                'updatedAt',
            ]),
            'sku' => SkuResource::whenLoaded($cart, 'sku'),
            'product' => call_user_func(function () use ($cart) {
                /** @internal */
                $product = ProductResource::whenLoaded($cart, 'product');
                if ($product instanceof MissingValue) {
                    $product = ProductResource::whenLoaded($cart, 'withDeletedProduct');
                }
                return $product;
            }),
        ];
    }

    public function includeCreateOrder(CartModel $cart): Ret
    {
        return $cart->checkCreateOrder();
    }
}
