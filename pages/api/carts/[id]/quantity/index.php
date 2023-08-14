<?php

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BasePage;

return new class () extends BasePage {
    public function put($req)
    {
        $cart = CartModel::mine()->findOrFail($req['id']);

        return $cart->updateQuantity($req['quantity']);
    }
};
