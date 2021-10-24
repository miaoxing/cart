<?php

use Miaoxing\Cart\Resource\CartResource;
use Miaoxing\Cart\Service\Cart;
use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BaseController;

return new class () extends BaseController {
    public function patch($req)
    {
        $ret = Cart::update($req);

        $ret->transform(CartResource::class);

        return $ret;
    }

    public function delete($req)
    {
        CartModel::mine()->destroyOrFail($req['id']);

        return suc();
    }
};
