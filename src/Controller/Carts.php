<?php

namespace Miaoxing\Cart\Controller;

use Miaoxing\Services\Middleware\Lock;

class Carts extends \Miaoxing\Plugin\BaseController
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        // TODO lock服务增加最大时间解决PHP崩溃导致死锁
        $this->middleware(Lock::class, [
            'only' => 'create',
            'name' => 'carts/create-v2',
        ]);
    }

    /**
     * 我的购物车商品列表
     * @param mixed $req
     */
    public function indexAction($req)
    {
        $carts = wei()->cart()
            ->mine()
            ->notOrdered()
            ->notDeleted()
            ->notTemp()
            ->desc('id')
            ->findAll();

        // 普通商品的购物车
        $types['default'] = wei()->cart()->beColl()->setAttributes($carts);

        // 增加购物车类型
        wei()->event->trigger('addCartTypes', [$carts, &$types]);

        // 默认显示有商品的购物车
        if (!$req['type']) {
            foreach ($types as $key => $type) {
                $req['type'] = $key;
                if ($type->length() > 0) {
                    break;
                }
            }
        }

        $showPrice = !$this->setting('product.hidePrice');
        $this->page->setTitle($this->setting('cart.title', '购物车'));

        return get_defined_vars();
    }

    public function batchDeleteAction($req)
    {
        if (!$req['id']) {
            return $this->err('购物车编号不能为空');
        }

        $carts = wei()->cart()->mine()->findAll(['id' => $req['id']]);
        foreach ($carts as $cart) {
            $cart->softDelete();
        }

        return $this->suc('删除成功');
    }
}
