<?php

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    public function get()
    {
        $count = CartModel::mine()->toOrder()->cnt();

        return suc([
            'data' => [
                'count' => $count,
            ],
        ]);
    }
};
