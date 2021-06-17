<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Plugin\BaseService;
use Miaoxing\Product\Service\Product;
use Miaoxing\Product\Service\Sku;
use Miaoxing\Product\Service\SkuModel;
use Wei\Event;
use Wei\Req;
use Wei\Ret;

class Cart extends BaseService
{
    public const TYPE_FREE = 1;

    public const TYPE_REDEMPTION = 2;

    protected $types = [
        self::TYPE_FREE => '赠品',
        self::TYPE_REDEMPTION => '换购',
    ];

    protected $attributes = [
        'specs' => [],
        'configs' => [],
    ];

    /**
     * 获取订单中商品的总价(优惠后)
     *
     * @return float
     */
    public function getProductAmount()
    {
        if (!$this->coll) {
            if ((float) $this['price'] > 0) {
                return (float) ($this['price'] * $this['quantity']);
            }

            return !$this['free'] ? (float) ($this->getSku()->get('price') * $this['quantity']) : 0;
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->attributes as $cart) {
                $amount += $cart->getProductAmount();
            }

            return (float) $amount;
        }
    }

    /**
     * 获取订单中商品的总价(优惠前)
     *
     * @return int
     */
    public function getProductOrigAmount()
    {
        if (!$this->coll) {
            return $this['origPrice'] * $this['quantity'];
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->attributes as $cart) {
                $amount += $cart->getProductOrigAmount();
            }

            return $amount;
        }
    }

    /**
     * 获取购物车中的总积分
     *
     * @return int
     */
    public function getScoresAmount()
    {
        if (!$this->coll) {
            return $this['scores'] * $this['quantity'];
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->attributes as $cart) {
                $amount += $cart->getScoresAmount();
            }

            return $amount;
        }
    }

    /**
     * 获取购物车的商品总数
     *
     * @return int
     */
    public function getTotalQuantity()
    {
        $quantity = 0;
        foreach ($this->attributes as $cart) {
            $quantity += $cart['quantity'];
        }

        return $quantity;
    }

    public function getStatusText()
    {
        switch (true) {
            case $this->isSoftDeleted():
                return '已取消';

            case $this['orderId'] != 0:
                return '已下订单';

            default:
                return '未下订单';
        }
    }

    /**
     * QueryBuilder: 筛选未下订单
     */
    public function notOrdered()
    {
        return $this->andWhere('orderId = 0');
    }

    /**
     * Record: 从指定的数据,创建一个新的购物车
     *
     * @param array|Req $data
     * @param array $attributes 直接存储的数据,如自定义价格
     * @return Ret|array{data:CartModel}
     * @svc
     */
    protected function create($data = [], array $attributes = []): Ret
    {
        $cart = CartModel::new();
        $ret = $cart->init($data);
        if ($ret->isErr()) {
            return $ret;
        }

        $cart->save($attributes);

        $ret['data'] = $cart;
        $ret['message'] = '加入成功';
        return $ret;
    }

    /**
     * 修改购物车价格
     */
    public function resetCartProductPrice($changePrice)
    {
        if (trim($changePrice) == '') {
            return ['code' => 1, 'message' => '操作成功'];
        }

        $this['price'] = ($changePrice != 0 ? ((float) $this['price'] ?: $this['origPrice']) + $changePrice : 0);
        $this->save();

        if ((float) $this['price']) {
            $message = '修改购物车商品价格[' . $this['name'] . ']：原价为￥' . ($this['origPrice']) . '，现价为￥' . sprintf(
                    '%.2f',
                    $this['price']
                );
        } else {
            $message = '修改购物车商品价格[' . $this['name'] . ']：重置价格为￥' . ($this['origPrice']);
        }
        wei()->db('cartLog')->save([
            'cartId' => $this['id'],
            'note' => $message,
            'createUser' => wei()->curUser['id'],
        ]);

        return ['code' => 1, 'message' => '操作成功'];
    }

    /**
     * Repo: 创建或更新购物车
     *
     * @param array|Req $data
     * @return Ret|array{data:CartModel}
     * @svc
     */
    protected function createOrUpdate($data): Ret
    {
        // 1. 初始化购物车
        $newCart = CartModel::new();
        $ret = $newCart->init($data);
        if ($ret->isErr()) {
            return $ret;
        }

        // 2. 如果有相同的购物车，只增加数量
        $newIdentifier = $newCart->calIdentifier();
        $carts = CartModel::mine()->findAllBy('skuId', $newCart->skuId);
        foreach ($carts as $cart) {
            if ($cart->calIdentifier() === $newIdentifier) {
                $cart->incrSave('quantity', $newCart->quantity);
                $newCart = $cart;
                break;
            }
        }

        // 3. 未找到相同的购物车
        if ($newCart->isNew()) {
            $newCart->save();
        }

        return $newCart->toRet([
            'message' => '加入成功',
            'exists' => !$newCart->wasRecentlyCreated(),
        ]);
    }

    /**
     * 判断当前购物车对应的商品,库存是否足够
     *
     * @return bool
     */
    public function isQuantityEnough()
    {
        return (int) $this->getSku()->get('quantity') >= (int) $this['quantity'];
    }

    /**
     * Record: 检查当前购物车能否购买
     *
     * @return array
     */
    public function checkPayable()
    {
        // 1. 检查商品是否可购买
        $ret = $this->getProduct()->checkPayable();
        if ($ret['code'] !== 1) {
            return $ret;
        }

        // 2. 检查SKU是否可购买
        $ret = $this->getSku()->checkPayable();
        if ($ret['code'] !== 1) {
            return $ret;
        }

        // 3. 检查购物车是否可购买
        if ($this['quantity'] <= 0) {
            return $this->err('请选择数量', -21);
        }

        if (!$this->isQuantityEnough()) {
            return $this->err('库存不足', -22);
        }

        $ret = wei()->event->until('cartCheckPayable', [$this]);
        if ($ret) {
            return $ret;
        }

        return $this->suc('可以购买');
    }

    /**
     * 获取现价,用于下单后
     *
     * @return string
     */
    public function getCurPrice()
    {
        if ($this['price'] != '0.00') {
            return $this['price'];
        } else {
            return $this['origPrice'];
        }
    }

    /**
     * 获取价格,用于下单前
     *
     * @return string
     */
    public function getSkuPrice()
    {
        if ($this['free'] == self::TYPE_FREE) {
            return '0.00';
        }

        if ((float) $this['price']) {
            return $this['price'];
        } else {
            return $this->getSku()['price'];
        }
    }

    /**
     * 生成价格文案
     *
     * @return string
     */
    public function getPriceText()
    {
        return wei()->product->getPriceText($this->getSkuPrice(), $this['scores']);
    }

    /**
     * Coll: 检查购物车是否允许使用优惠券/卡券
     *
     * @return bool
     */
    public function isAllowCoupon()
    {
        /** @var Cart $cart */
        foreach ($this as $cart) {
            if (!$cart->getProduct()->get('allowCoupon')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Coll: 检查购物车是否全为虚拟商品
     *
     * @return bool
     */
    public function isVirtual()
    {
        /** @var Cart $cart */
        foreach ($this as $cart) {
            if (!$cart->getProduct()->get('isVirtual')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Coll: 检查购物车是否全为自提商品
     *
     * @return bool
     */
    public function isSelfPickUp()
    {
        /** @var Cart $cart */
        foreach ($this as $cart) {
            if (!$cart->getProduct()['config']['selfPickUp']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取产品缩略图
     *
     * 兼容下单前后两种情况
     *
     * @return string
     */
    public function getImage()
    {
        if ($this['orderId']) {
            return $this['image'];
        } else {
            $thumb = wei()->event->until('cartGetThumb', [$this]);

            return $thumb ?: $this->getProduct()->getThumb();
        }
    }

    /**
     * 获取规格字符串
     *
     * @return string
     */
    public function getSpecsContent()
    {
        $content = '';
        foreach ($this['specs'] as $key => $spec) {
            $content .= $key . ':' . $spec . ' ';
        }

        return $content;
    }

    public function afterSave()
    {
        $this->clearTagCacheByUser();
    }

    public function afterDestroy()
    {
        $this->clearTagCacheByUser();
    }

    /**
     * 设置配置字段的内容
     *
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function setConfig($name, $value = null)
    {
        $config = $this['configs'];

        if (is_array($name)) {
            $config += $name;
        } else {
            $config[$name] = $value;
        }

        $this['configs'] = $config;

        return $this;
    }

    public function notTemp()
    {
        return $this->andWhere(['temp' => 0]);
    }

    public function getTypeName()
    {
        return isset($this->types[$this['free']]) ? $this->types[$this['free']] : '';
    }

    public function getOrigPrice()
    {
        if (!wei()->money->isZero($this['origPrice'])) {
            return $this['origPrice'];
        }

        // 为0是不能确定是否为免费,改为读取商品的原价
        return $this->getProduct()['originalPrice'];
    }
}
