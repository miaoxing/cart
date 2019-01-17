<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Order\Service\Order;
use Miaoxing\Product\Service\Product;
use Miaoxing\Product\Service\Sku;

class Cart extends \Miaoxing\Plugin\BaseModel
{
    const TYPE_FREE = 1;

    const TYPE_REDEMPTION = 2;

    protected $types = [
        self::TYPE_FREE => '赠品',
        self::TYPE_REDEMPTION => '换购',
    ];

    protected $autoId = true;

    /**
     * @var \Miaoxing\Product\Service\Product
     */
    protected $product;

    /**
     * @var Sku
     */
    protected $sku;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var \Miaoxing\Plugin\Service\User
     */
    protected $user;

    protected $data = [
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
        if (!$this->isColl) {
            if ((float) $this['price'] > 0) {
                return (float) ($this['price'] * $this['quantity']);
            }

            return !$this['free'] ? (float) ($this->getSku()->get('price') * $this['quantity']) : 0;
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->data as $cart) {
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
        if (!$this->isColl) {
            return $this['origPrice'] * $this['quantity'];
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->data as $cart) {
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
        if (!$this->isColl) {
            return $this['scores'] * $this['quantity'];
        } else {
            $amount = 0;
            /** @var $cart $this */
            foreach ($this->data as $cart) {
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
        foreach ($this->data as $cart) {
            $quantity += $cart['quantity'];
        }

        return $quantity;
    }

    public function getProduct()
    {
        $this->product || $this->product = wei()->product()->findOrInitById($this['productId']);

        return $this->product;
    }

    /**
     * 设置购物车对应的商品
     *
     * @param \Miaoxing\Product\Service\Product $product
     * @return $this
     */
    public function setProduct(\Miaoxing\Product\Service\Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * 获取商品规格
     *
     * @return \Miaoxing\Product\Service\Sku|false
     */
    public function getSku()
    {
        $this->sku || $this->sku = wei()->sku()->findOrInitById($this['skuId']);

        return $this->sku;
    }

    /**
     * 设置商品规格
     *
     * @param Sku $sku
     * @return $this
     */
    public function setSku(\Miaoxing\Product\Service\Sku $sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * 获取订单
     * @return Order
     */
    public function getOrder()
    {
        $this->order || $this->order = wei()->order()->findOrInitById($this['orderId']);

        return $this->order;
    }

    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getUser()
    {
        $this->user || $this->user = wei()->user()->findOrInitById($this['userId']);

        return $this->user;
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
     * @param array $data
     * @param array $recordData 直接存储的数据,如自定义价格
     * @return array
     */
    public function create($data = [], array $recordData = [])
    {
        $ret = $this->init($data);
        if ($ret['code'] !== 1) {
            return $ret;
        }

        $this->save($recordData);

        return $this->suc([
            'message' => '加入成功',
            'data' => $this->toArray(),
        ]);
    }

    /**
     * Record: 初始化商品数据
     *
     * @param array $data
     * @return array
     */
    public function init($data = [])
    {
        // 1. 初始化SKU,商品对象
        /** @var $sku \Miaoxing\Product\Service\Sku */
        $sku = wei()->sku()->findById($data['skuId']);
        if (!$sku) {
            return ['code' => -1, 'message' => '商品不存在'];
        }

        // 2. 检查商品是否售罄(前台有拦截,一般扫二维码时触发)
        $product = $sku->getProduct();
        if ($product->isSoldOut()) {
            return ['code' => -4, 'message' => sprintf('商品"%s"已售罄', $product['name'])];
        }

        // 3. 检查商品是否过期
        if ($product->isEnd()) {
            return ['code' => -4, 'message' => sprintf('商品"%s"已到下架时间', $product['name'])];
        }

        // 4. 检查选择规格是否库存足够
        if ($sku['quantity'] == 0) {
            return [
                'code' => -5,
                'message' => sprintf('很抱歉,商品"%s"中,规格"%s"已售罄', $product['name'], implode(',', $sku->getSpecs())),
            ];
        }

        // 5. 检查是否到上架时间
        if ($product->isWillStart()) {
            return ['code' => -6, 'message' => '抢购未开始'];
        }

        // 6. 检查数量参数是否合法,并且低于库存
        $validator = wei()->validate([
            'data' => $data,
            'rules' => [
                'quantity' => [
                    'positiveInteger' => true,
                    'lessThanOrEqual' => $sku['quantity'],
                ],
            ],
            'names' => [
                'quantity' => '商品数量',
            ],
            'messages' => [
                'quantity' => [
                    'lessThanOrEqual' => '%name%不能超过库存数量',
                ],
            ],
        ]);

        if (!$validator->isValid()) {
            return ['code' => -2, 'message' => $validator->getFirstMessage()];
        }

        // 7. 检查所有购物车中该商品的总量是否超过限制
        $ret = $this->checkLimitation($product, $data['quantity']);
        if ($ret['code'] !== 1) {
            return $ret;
        }

        // 8. 检查积分
        if ($sku['score'] && $sku['score'] * $data['quantity'] > wei()->curUserV2->score) {
            return $this->err([
                '积分不足，您当前可用积分为%s，还差%s！',
                wei()->curUserV2->score,
                $sku['score'] * $data['quantity'] - wei()->curUserV2->score,
            ]);
        }

        // 9. 触发初始化购物车的回调
        $ret = wei()->event->until('cartInit', [$this, $sku, $data, $product]);
        if ($ret) {
            return $ret;
        }

        // 10. 初始化数据
        $this->setData([
            'userId' => wei()->curUser['id'],
            'skuId' => $sku['id'],
            'origPrice' => $sku['price'],
            'productId' => $product['id'],
            'scores' => $sku['score'],
            'name' => $product['name'],
            'quantity' => (int) $data['quantity'],
            'temp' => (int) $data['temp'],
        ]);

        return ['code' => 1, 'message' => '初始化成功'];
    }

    /**
     * Record: 更改购物车中的商品数量
     *
     * @param int $quantity 更改后的数量
     * @return array
     */
    public function updateQuantity($quantity)
    {
        // 1. 数据合法性检查
        if (!wei()->isPositiveInteger($quantity)) {
            return $this->err(wei()->isPositiveInteger->getFirstMessage('商品数量'), -1);
        }

        // 2.1 数量减少,直接更新
        if ($quantity < $this['quantity']) {
            $this->save(['quantity' => $quantity]);

            return $this->suc('更改成功');
        }

        // 2.2 数量不变
        if ($quantity == $this['quantity']) {
            return $this->err('数量未更改', -2);
        }

        // 2.3 数量增加
        $sku = $this->getSku();
        $product = $sku->getProduct();
        if ($quantity > $sku['quantity']) {
            return $this->err('商品数量不能超过库存', -3);
        }

        // 检查所有购物车中该商品的总量是否超过限制
        $ret = $this->checkLimitation($product, $quantity - $this['quantity']);
        if ($ret['code'] !== 1) {
            return $ret;
        }

        $this->save(['quantity' => (int) $quantity]);

        return $this->suc('更改成功');
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
     * 检查要加入购物车的商品数量是否超过最大购买数量
     *
     * @param \Miaoxing\Product\Service\Product $product
     * @param int $quantity 要加入购物车的数量
     * @return array
     */
    public function checkLimitation(\Miaoxing\Product\Service\Product $product, $quantity)
    {
        if (!$product['limitation']) {
            return $this->suc('数量不限制,可以购买');
        }

        $orderCount = $this->getOrderCount($product);
        $cartCount = $this->getCartCount($product);
        $refundCount = $this->getRefundCount($product);

        // 组装出具体的错误信息
        $count = $orderCount + $cartCount + (int) $quantity - $refundCount;
        if ($count > (int) $product['limitation']) {
            $message = $this->buildLimitationMessage($product['limitation'], $cartCount, $orderCount);

            return $this->err($message, -4);
        }

        return $this->suc('数量不超过限制,可以购买');
    }

    /**
     * 当前已下订单中购买过该产品的数量
     * @param \Miaoxing\Product\Service\Product $product
     * @return int
     */
    public function getOrderCount(\Miaoxing\Product\Service\Product $product)
    {
        // 获取已下单的数量
        $orderCount = (int) wei()->cart()
            ->select('SUM(quantity)')
            ->mine()
            ->notDeleted()
            ->andWhere(['productId' => $product['id']])
            ->andWhere("orderId != ''")
            ->fetchColumn();

        return $orderCount;
    }

    /**
     * 当前购物车中购买过该产品的数量
     * @param Product $product
     * @return int
     */
    public function getCartCount(\Miaoxing\Product\Service\Product $product)
    {
        // 获取购物车中的数量
        $cartCount = (int) wei()->cart()
            ->select('SUM(quantity)')
            ->mine()
            ->notDeleted()
            ->notTemp()
            ->andWhere(['productId' => $product['id']])
            ->andWhere(['orderId' => ''])
            ->fetchColumn();

        return $cartCount;
    }

    public function getRefundCount(\Miaoxing\Product\Service\Product $product)
    {
        // 获取退款中的数量
        $refunds = wei()->refund()->curApp()
            ->mine()
            ->andWhere(['productId' => $product['id']])
            ->andWhere(['status' => 4])
            ->findAll();

        $refundCount = 0;
        foreach ($refunds as $refund) {
            $calCount = $product['price'] == 0 ? $product['price'] : floor($refund['fee'] / $product['price']);
            // 计算的退款数量不能超过购物车上的数量
            $cart = wei()->cart()->findOneById($refund['cartId']);
            if ($cart['quantity'] < $calCount) {
                $calCount = $cart['quantity'];
            }

            $refundCount += $calCount;
        }

        return $refundCount;
    }

    /**
     * 根据限购数量和用户购物车,订单中数量,构造不同的提示消息
     *
     * @param int $limitation
     * @param int $cartCount
     * @param int $orderCount
     * @return string
     */
    protected function buildLimitationMessage($limitation, $cartCount, $orderCount)
    {
        // 1. 先提醒限制
        $message = '此商品每人限购' . $limitation . '件，';

        // 2. 接着提醒购物车和订单里已有的数量
        if ($cartCount) {
            $cartTitle = wei()->setting('cart.title', '购物车');
            $message .= $cartTitle . '已有' . $cartCount . '件，';
        }
        if ($orderCount) {
            $message .= '订单已有' . $orderCount . '件，';
        }

        // 3. 最后指出推荐的做法
        switch (true) {
            // 可能还可以再买,但是购物车里已有,推荐去购物车查看
            case $cartCount:
                $message .= '请去购物车继续购买';
                break;

            // 已经不能再买了,引导到订单中确认
            case $orderCount == $limitation:
                $message .= '请去订单中查看';
                break;

            default:
                $message .= '请返回修改';
        }

        return $message;
    }

    /**
     * Repo: 创建或更新购物车
     *
     * @param array $data
     * @return array
     */
    public function createOrUpdate($data)
    {
        // 1. 初始化购物车
        $cur = wei()->cart();
        $ret = $cur->init($data);
        if ($ret['code'] !== 1) {
            return $ret;
        }

        // 2. 如果有相同的购物车,只增加数量
        $found = false;
        $curId = $cur->getIdentifier();
        $carts = wei()->cart()->mine()->notDeleted()->notTemp()->findAll(['skuId' => $cur['skuId']]);
        foreach ($carts as $cart) {
            if ($cart->getIdentifier() == $curId) {
                $cart->incr('quantity', $cur['quantity']);
                $cart->save();
                $cur = $cart;
                $found = true;
                break;
            }
        }

        // 3. 未找到相同的商品,直接保存
        if (!$found) {
            $cur->save();
        }

        return $this->suc([
            'message' => '加入成功',
            'found' => $found,
            'data' => $cur,
        ]);
    }

    /**
     * Record: 生成当前购物车的唯一ID,用于判断两个购物车是否一致
     *
     * @return string
     */
    public function getIdentifier()
    {
        $ids = [];
        foreach (['price', 'skuId', 'orderId', 'configs'] as $field) {
            // 将0统一转为空字符串
            $ids[] = $this[$field] ?: '';
        }

        return json_encode($ids);
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

    /**
     * {@inheritdoc}
     */
    public function toArray($returnFields = [])
    {
        $data = parent::toArray($returnFields);

        // 如果未下订单,数据要从原商品中读取
        if (!$this->isColl && !$this['orderId']) {
            $product = $this->getProduct();
            $data['image'] = $this->getImage();
            $data['name'] = $product['name'];
            $data['specs'] = $product->isSingleSku() ? [] : $this->getSku()->getSpecsFromCache();
        }

        return $data;
    }

    public function afterFind()
    {
        parent::afterFind();

        $this['specs'] = (array) json_decode($this['specs'], true);
        $this['configs'] = (array) json_decode($this['configs'], true);
        $this->event->trigger('postImageDataLoad', [&$this, ['image']]);
    }

    public function beforeSave()
    {
        parent::beforeSave();

        $this['specs'] = json_encode($this['specs'], JSON_UNESCAPED_UNICODE);
        $this['configs'] = json_encode($this['configs'], JSON_UNESCAPED_UNICODE);
        $this->event->trigger('preImageDataSave', [&$this, ['image']]);
    }

    public function afterSave()
    {
        parent::afterSave();

        $this['specs'] = (array) json_decode($this['specs'], true);
        $this['configs'] = (array) json_decode($this['configs'], true);
        $this->event->trigger('postImageDataLoad', [&$this, ['image']]);

        $this->clearTagCacheByUser();
    }

    public function afterDestroy()
    {
        parent::afterDestroy();
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
