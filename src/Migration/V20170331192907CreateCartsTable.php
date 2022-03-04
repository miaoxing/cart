<?php

namespace Miaoxing\Cart\Migration;

use Wei\Migration\BaseMigration;

class V20170331192907CreateCartsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('carts')
            ->bigId()
            ->uBigInt('app_id')->comment('应用编号')
            ->uBigInt('user_id')->comment('用户编号')
            ->uBigInt('product_id')->comment('商品编号')
            ->uBigInt('sku_id')->comment('SKU编号')
            ->uBigInt('order_id')->comment('订单编号')
            ->tinyInt('status')->defaults(1)->comment('状态。1:正常;2:已下单;3:已删除')
            ->uInt('quantity')->comment('数量')
            ->uDecimal('changed_price')->nullable()->defaults(null)->comment('修改后价格')
            ->uDecimal('added_price')->comment('加入价格')
            ->string('configs', 1024)->defaults('{}')->comment('配置')
            ->timestamps()
            ->userstamps()
            ->softDeletable()
            ->index('user_id')
            ->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('carts');
    }
}
