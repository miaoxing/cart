<?php

use Miaoxing\Cart\Resource\CartResource;
use Miaoxing\Cart\Service\Cart;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function post($req)
    {
        $ret = Cart::createOrUpdate($req);

        if ($ret->isSuc()) {
            $ret['data'] = CartResource::transformData($ret['data']);
        }

        return $ret;
    }
};
