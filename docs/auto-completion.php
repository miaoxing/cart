<?php

/**
 * @property    Miaoxing\Cart\Service\Cart $cart
 */
class CartMixin {
}

/**
 * @property    Miaoxing\Cart\Service\CartConfig $cartConfig
 */
class CartConfigMixin {
}

/**
 * @property    Miaoxing\Cart\Service\CartModel $cartModel
 * @method      Miaoxing\Cart\Service\CartModel cartModel() 返回当前对象
 */
class CartModelMixin {
}

/**
 * @mixin CartMixin
 * @mixin CartConfigMixin
 * @mixin CartModelMixin
 */
class AutoCompletion {
}

/**
 * @return AutoCompletion
 */
function wei()
{
    return new AutoCompletion;
}

/** @var Miaoxing\Cart\Service\Cart $cart */
$cart = wei()->cart;

/** @var Miaoxing\Cart\Service\CartConfig $cartConfig */
$cartConfig = wei()->cartConfig;

/** @var Miaoxing\Cart\Service\CartModel $cart */
$cart = wei()->cartModel;

/** @var Miaoxing\Cart\Service\CartModel|Miaoxing\Cart\Service\CartModel[] $carts */
$carts = wei()->cartModel();
