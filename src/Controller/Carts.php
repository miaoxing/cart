<?php

namespace Miaoxing\Cart\Controller;

use Miaoxing\Plugin\Middleware\CheckReferrer;
use Miaoxing\Plugin\Middleware\HttpMethod;
use Miaoxing\Plugin\Middleware\Lock;

class Carts extends \Miaoxing\Plugin\BaseController
{
    protected $guestPages = [
        'carts/count',
    ];

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->middleware(CheckReferrer::class, [
            'only' => 'create',
        ]);

        $this->middleware(HttpMethod::class, [
            'only' => 'create',
            'actions' => [
                'create' => 'post',
            ],
        ]);

        // TODO lock服务增加最大时间解决PHP崩溃导致死锁
        $this->middleware(Lock::class, [
            'only' => 'create',
            'name' => 'carts/create-v2',
        ]);
    }

    /**
     * 我的购物车商品列表
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
        $types['default'] = wei()->cart()->beColl()->setData($carts);

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
        $headerTitle = $this->setting('cart.title', '购物车');
        $this->layout->hideFooter();

        return get_defined_vars();
    }

    /**
     * 将商品加入购物车
     */
    public function createAction($req)
    {
        if (!$this->curUser['id']) {
            return $this->err(wei()->setting('tips.noLogin'), -401);
        }

        $ret = wei()->cart->createOrUpdate($req);

        return $ret;
    }

    /**
     * 更新购物车信息
     */
    public function updateAction($req)
    {
        if (!$this->curUser['id']) {
            return $this->err(wei()->setting('tips.noLogin'), -401);
        }

        $cart = wei()->cart()->mine()->findOneById($req['id']);

        // 1. 检查SKU和数量是否合法
        $sku = wei()->sku()->findById($req['skuId']);
        if (!$sku) {
            return $this->err('该商品不存在');
        }
        if ($sku['productId'] != $cart['productId']) {
            return $this->err('该商品属性不存在');
        }
        $cart['skuId'] = $sku['id'];
        $cart['origPrice'] = $sku['price'];

        // 2. 如果增加了数量,需要检查所有购物车中该商品的总量是否超过限制
        $quantity = (int) $req['quantity'];
        $addedQuantity = $quantity - $cart['quantity'];
        if ($addedQuantity > 0) {
            $product = $cart->getProduct();
            $ret = $cart->checkLimitation($product, $quantity);
            if ($ret['code'] !== 1) {
                return $this->ret($ret);
            }
        }
        $cart['quantity'] = $quantity;

        // 3. 检查新的购物车是否可支付
        $ret = $cart->checkPayable();
        if ($ret['code'] !== 1) {
            return $this->ret($ret);
        }

        // 4. 更新到购物车
        $ret = wei()->event->until('preCartUpdate', [$cart, $sku, $req]);
        if ($ret) {
            return $ret;
        }

        $cart->save();

        return $this->suc('更新成功');
    }

    /**
     * 更改购物车商品数量
     */
    public function updateQuantityAction($req)
    {
        $cart = wei()->cart()->mine()->findOneById($req['id']);

        $ret = $cart->updateQuantity($req['quantity']);

        return $this->ret($ret);
    }

    /**
     * 将商品移出购物车
     */
    public function destroyAction($req)
    {
        $cart = wei()->cart()->mine()->findOneById($req['id']);

        $cart->softDelete();

        return $this->suc('删除成功');
    }

    public function showAction($req)
    {
        $cart = wei()->cart()->mine()->notDeleted()->findOneById($req['id']);

        return $this->suc([
            'data' => $cart,
        ]);
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

    public function countAction()
    {
        $count = wei()->cart()->mine()->notOrdered()->notDeleted()->count();

        return $this->suc(['count' => $count]);
    }
}
