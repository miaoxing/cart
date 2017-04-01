<?php

namespace Miaoxing\Cart\Migration;

use Miaoxing\Plugin\BaseMigration;

class V20170331192907CreateCartTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('cart')
            ->id()
            ->int('userId')
            ->int('productId')
            ->int('skuId')
            ->string('orderId', 32)
            ->int('promotionId')
            ->int('promotionTierId')
            ->string('name', 255)
            ->int('quantity')
            ->decimal('price', 10)
            ->decimal('origPrice', 10)
            ->int('scores')
            ->string('specs', 255)
            ->tinyInt('free', 1)->comment('是否为赠送商品')
            ->tinyInt('paid', 1)->comment('购物车是否已付款')
            ->string('image', 255)
            ->text('configs')
            ->timestampsV1()
            ->userstampsV1()
            ->softDeletableV1()
            ->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('cart');
    }
}
