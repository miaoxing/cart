<?php

use Miaoxing\Cart\Resource\CartResource;
use Miaoxing\Cart\Service\Cart;
use Miaoxing\Cart\Service\CartConfigModel;
use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function get()
    {
        $carts = CartModel::mine()
            ->toOrder()
            ->desc('id')
            ->all()
            // @internal
            ->load(['withDeletedProduct.spec', 'sku']);

        return $carts->toRet(CartResource::includes(['createOrder']))
            ->with('selected', CartConfigModel::findOrInitMine()->selectedIds);
    }

    public function post($req)
    {
        $ret = Cart::createOrUpdate($req);

        if ($ret->isSuc()) {
            $ret['data'] = CartResource::transformData($ret['data']);
        }

        return $ret;
    }
};
