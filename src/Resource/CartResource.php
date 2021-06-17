<?php

namespace Miaoxing\Cart\Resource;

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\Resource\BaseResource;

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
            ]),
        ];
    }
}
