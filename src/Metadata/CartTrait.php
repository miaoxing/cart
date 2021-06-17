<?php

namespace Miaoxing\Cart\Metadata;

/**
 * @property int|null $id
 * @property int $appId 应用编号
 * @property int $userId 用户编号
 * @property int $productId 商品编号
 * @property int $skuId SKU编号
 * @property int $orderId 订单编号
 * @property int $status 状态。1:正常;2:已下单;3:已删除
 * @property int $quantity 数量
 * @property float|null $changedPrice 修改后价格
 * @property float $addedPrice 加入价格
 * @property string $configs 配置
 * @property string|null $createdAt
 * @property string|null $updatedAt
 * @property int $createdBy
 * @property int $updatedBy
 * @property string|null $deletedAt
 * @property int $deletedBy
 * @internal will change in the future
 */
trait CartTrait
{
}
