<?php

use Miaoxing\Cart\Service\Cart;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function patch($req)
    {
        return Cart::update($req);
    }
};
