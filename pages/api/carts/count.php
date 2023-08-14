<?php

use Miaoxing\Cart\Service\CartModel;
use Miaoxing\Plugin\BasePage;

return new class () extends BasePage {
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
