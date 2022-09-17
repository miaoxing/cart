<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Cart\Metadata\CartTrait;
use Miaoxing\Plugin\BaseModel;
use Miaoxing\Plugin\Model\HasAppIdTrait;
use Miaoxing\Plugin\Model\MineTrait;
use Miaoxing\Plugin\Model\ModelTrait;
use Miaoxing\Plugin\Model\SnowflakeTrait;
use Miaoxing\Plugin\Service\User;
use Miaoxing\Product\Model\BelongsToProductTrait;
use Miaoxing\Product\Service\ProductModel;
use Miaoxing\Product\Service\SkuModel;
use Wei\Event;
use Wei\IsPositiveInteger;
use Wei\Model\SoftDeleteTrait;
use Wei\Ret;
use Wei\V;

/**
 * @property SkuModel $sku
 * @property ProductModel $product
 */
class CartModel extends BaseModel
{
    use BelongsToProductTrait;
    use CartTrait;
    use HasAppIdTrait;
    use MineTrait;
    use ModelTrait;
    use SnowflakeTrait;
    use SoftDeleteTrait;

    public const STATUS_NORMAL = 1;

    public const STATUS_ORDERED = 2;

    public const STATUS_DELETED = 3;

    /**
     * @var string
     */
    protected $deleteStatusColumn = 'status';

    protected $columns = [
        'configs' => [
            'cast' => 'object',
        ],
    ];

    public function sku(): SkuModel
    {
        return $this->belongsTo(SkuModel::class);
    }

    /**
     * @return ProductModel
     * @internal 待改为关联支持 unscoped
     */
    public function withDeletedProduct(): ProductModel
    {
        return $this->belongsTo(ProductModel::withDeleted());
    }

    /**
     * 增加待下单查询条件
     *
     * @return $this
     * @svc
     */
    protected function toOrder(): self
    {
        return $this->where('status', static::STATUS_NORMAL);
    }

    /**
     * 计算唯一标识，用于比较两个购物车商品是否相同
     *
     * @return string
     */
    public function calIdentifier(): string
    {
        $ids = [];
        foreach (['skuId', 'status', 'configs'] as $column) {
            $ids[] = $this->{$column};
        }
        return json_encode($ids);
    }

    /**
     * 初始化购物车对象
     *
     * @param mixed $data
     * @return Ret
     * @svc
     */
    protected function init($data = []): Ret
    {
        // 1. 初始化 SKU 对象
        $sku = SkuModel::find($data['skuId'] ?? null);
        if (!$sku) {
            return err('商品不存在');
        }

        // 2. 检查商品是否可以加入购物车
        $product = $sku->product;
        $ret = $product->checkCreateCart();
        if ($ret->isErr()) {
            return $ret;
        }

        // 3. 检查选择规格是否库存足够
        $ret = $sku->checkCreateCart();
        if ($ret->isErr()) {
            return $ret;
        }

        // 4. 检查数量参数是否合法，并且低于库存
        $v = V::new();
        $v->positiveInteger('quantity', '商品数量')
            ->lessThanOrEqual($sku->stockNum)->message('%name%不能超过库存数量');
        $ret = $v->check($data);
        if ($ret->isErr()) {
            return $ret;
        }

        // 5. 检查所有购物车中该商品的总量是否超过限制
        $ret = $this->checkLimitation($product, $data['quantity']);
        if ($ret->isErr()) {
            return $ret;
        }

        // 6. 检查积分
        if ($sku->score && $sku->score * $data['quantity'] > User::cur()->score) {
            $score = User::cur()->score;
            return err([
                '积分不足，您当前可用积分为%s，还差%s！',
                $score,
                $sku['score'] * $data['quantity'] - $score,
            ]);
        }

        // 7. 触发初始化购物车的回调
        $ret = Event::until('cartInit', [$this, $sku, $data, $product]);
        if ($ret) {
            return $ret;
        }

        // 8. 初始化数据
        $this->setAttributes([
            'userId' => User::id(),
            'skuId' => $sku->id,
            'productId' => $product->id,
            'addedPrice' => $sku->price,
            'quantity' => $data['quantity'],
        ]);
        return suc('初始化成功');
    }

    /**
     * 检查要加入购物车的商品数量是否超过最大购买数量
     *
     * @param ProductModel $product
     * @param int $quantity 要加入购物车的数量
     * @return Ret
     */
    public function checkLimitation(ProductModel $product, int $quantity): Ret
    {
        if (!$product->maxOrderQuantity) {
            return suc('数量不限制,可以购买');
        }

        $orderCount = $this->getOrderCount($product);
        $cartCount = $this->getCartCount($product);
        $refundCount = $this->getRefundCount($product);

        // 组装出具体的错误信息
        $count = $orderCount + $cartCount + $quantity - $refundCount;
        if ($count > $product->maxOrderQuantity) {
            $message = $this->buildLimitationMessage($product->maxOrderQuantity, $cartCount, $orderCount);
            return err($message);
        }

        return suc('数量不超过限制,可以购买');
    }

    /**
     * 检查当前购物车能否购买
     *
     * @return Ret
     */
    public function checkCreateOrder(): Ret
    {
        // 1. 检查商品是否可购买
        if (!$this->product || $this->product->isDeleted()) {
            return err('该商品已失效');
        }
        $ret = $this->product->checkCreateOrder();
        if ($ret->isErr()) {
            return $ret;
        }

        // 2. 检查SKU是否可购买
        if (!$this->sku) {
            return err('该规格已失效');
        }
        $ret = $this->sku->checkCreateCart();
        if ($ret->isErr()) {
            return $ret;
        }

        // 3. 检查购物车是否可购买
        if ($this->quantity <= 0) {
            return err('请选择数量');
        }

        // 4. 库存是否足够
        if ($this->sku->stockNum < $this->quantity) {
            return err('库存不足');
        }

        $ret = Event::until('cartCheckCreateOrder', [$this]);
        if ($ret) {
            return $ret;
        }

        return suc('可以购买');
    }

    /**
     * 当前已下订单中购买过该产品的数量
     *
     * @param ProductModel $product
     * @return int
     * @todo
     */
    public function getOrderCount(ProductModel $product)
    {
        return 0;
    }

    /**
     * 当前购物车中已有该产品的数量
     *
     * @param ProductModel $product
     * @return int
     */
    public function getCartCount(ProductModel $product): int
    {
        return (int) self
            ::selectRaw('SUM(quantity)')
                ->mine()
                ->where('productId', $product->id)
                ->fetchColumn();
    }

    /**
     * @param ProductModel $product
     * @return int
     * @todo
     */
    public function getRefundCount(ProductModel $product): int
    {
        return 0;
    }

    /**
     * 更改购物车中的商品数量
     *
     * @param int|string $quantity 更改后的数量
     * @return Ret
     */
    public function updateQuantity($quantity): Ret
    {
        // 1. 数据合法性检查
        $ret = IsPositiveInteger::check($quantity, '商品数量');
        if ($ret->isErr()) {
            return $ret;
        }

        // 2.1 数量减少,直接更新
        if ($quantity < $this->quantity) {
            $this->save(['quantity' => $quantity]);
            return suc('更改成功');
        }

        // 2.2 数量不变
        if ($quantity === $this->quantity) {
            return err('数量未更改');
        }

        // 2.3 数量增加
        $sku = $this->sku;
        $product = $sku->product;
        if ($quantity > $sku->stockNum) {
            return err('商品数量不能超过库存');
        }

        // 检查所有购物车中该商品的总量是否超过限制
        $ret = $this->checkLimitation($product, $quantity - $this->quantity);
        if ($ret->isErr()) {
            return $ret;
        }

        $this->save(['quantity' => $quantity]);

        return suc('更改成功');
    }

    /**
     * 根据限购数量和用户购物车,订单中数量,构造不同的提示消息
     *
     * @param int $limitation
     * @param int $cartCount
     * @param int $orderCount
     * @return array
     */
    protected function buildLimitationMessage(int $limitation, int $cartCount, int $orderCount): array
    {
        $args = [];

        // 1. 先提醒限制
        $message = '此商品每人限购%s件，';
        $args[] = $limitation;

        // 2. 接着提醒购物车和订单里已有的数量
        if ($cartCount) {
            $message .= CartConfig::name() . '已有%s件，';
            $args[] = $cartCount;
        }
        if ($orderCount) {
            $message .= '订单已有%s件，';
            $args[] = $orderCount;
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

        array_unshift($args, $message);
        return $args;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDeleteStatusValue(): int
    {
        return static::STATUS_DELETED;
    }

    /**
     * {@inheritDoc}
     */
    protected function getRestoreStatusValue(): int
    {
        return $this->orderId ? static::STATUS_NORMAL : static::STATUS_ORDERED;
    }
}
