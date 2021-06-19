<?php

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function put($req)
    {
        $cart = CartModel::mine()->findOrFail($req['id']);

        return $cart->updateQuantity($req['quantity']);
    }
};
