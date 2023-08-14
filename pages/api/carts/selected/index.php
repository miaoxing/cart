<?php

use Miaoxing\Cart\Service\CartConfigModel;
use Miaoxing\Plugin\BasePage;

return new class () extends BasePage {
    /**
     * 更新选中的购物车编号
     * @param mixed $req
     */
    public function put($req)
    {
        CartConfigModel::findOrInitMine()->save([
            'selectedIds' => $req['ids'],
        ]);

        return suc();
    }
};
