<?php

use Miaoxing\Cart\Service\CartConfigModel;
use Miaoxing\Plugin\BaseController;

return new class extends BaseController {
    /**
     * 更新选中的购物车编号
     */
    public function put($req)
    {
        CartConfigModel::findOrInitMine()->save([
            'selectedIds' => $req['ids'],
        ]);

        return suc();
    }
};
