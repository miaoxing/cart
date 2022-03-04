<?php

namespace Miaoxing\Cart\Migration;

use Wei\Migration\BaseMigration;

class V20210619204059CreateCartConfigsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('cart_configs')->tableComment('购物车配置')
            ->bigId()
            ->uBigInt('app_id')->comment('应用编号')
            ->uBigInt('user_id')->comment('用户编号')
            // 4096 / 19 ≈ 215，预估最大 200 个购物车
            ->string('selected_ids', 4096)->comment('选中的购物车编号')
            ->timestamps()
            ->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('cart_configs');
    }
}
