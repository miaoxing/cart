<?php

namespace plugins\cart\docs {

    /**
     * @property    \Miaoxing\Cart\Service\Cart $cart 购物车服务
     * @method      \Miaoxing\Cart\Service\Cart|\Miaoxing\Cart\Service\Cart[] cart() 创建一个购物车对象
     */
    class AutoComplete
    {
    }
}

namespace {

    $cart = wei()->cart;

    $carts = wei()->cart();

    /**
     * @return \plugins\cart\docs\AutoComplete
     */
    function wei()
    {
    }
}
