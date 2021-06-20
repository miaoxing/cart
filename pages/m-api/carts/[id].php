<?php

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function patch($req)
    {
        return Cart::update($req);
    }

    public function delete($req)
    {
        CartModel::mine()->findOrFail($req['id'])->destroy();

        return suc();
    }
};
