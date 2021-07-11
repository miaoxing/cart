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
     * 将商品加入购物车
     * @param mixed $req
     */
    public function createAction($req)
    {
        if (!$this->curUser['id']) {
            return $this->err(wei()->setting('tips.noLogin'), -401);
        }

        $ret = wei()->cart->createOrUpdate($req);

        return $ret;
    }
}
