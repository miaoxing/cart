<?php

namespace Miaoxing\Cart\Controller\Admin;

class Carts extends \Miaoxing\Plugin\BaseController
{
    protected $controllerName = '购物车管理';

    protected $actionPermissions = [
        'index' => '列表',
        'show' => '查看',
        'update' => '编辑',
        'log' => '日志列表',
    ];

    public function indexAction($req)
    {
        switch ($req['_format']) {
            case 'json':
            case 'csv':
                $carts = wei()->cart();

                // 分页
                $carts->limit($req['rows'])->page($req['page']);

                // 排序
                $carts->desc('id');

                // 状态
                switch ($req['status']) {
                    case 'ordered':
                        $carts->notDeleted()->andWhere('orderId != 0');
                        break;

                    case 'cancel':
                        $carts->deleted();
                        break;

                    case 'notOrdered':
                        // TODO orderId改为int的话,这里要变0
                        $carts->notDeleted()->andWhere(['orderId' => '']);
                        break;
                    default:
                        break;
                }

                if ($req['startTime']) {
                    $carts->andWhere('cart.createTime >= ?', $req['startTime']);
                }

                if ($req['endTime']) {
                    $carts->andWhere('cart.createTime < ?', $req['endTime']);
                }

                if ($req['minAmount']) {
                    $carts->andWhere('(price = 0.00 AND origPrice >= ?) or (price != 0.00 AND price >= ?)', [
                        $req['minAmount'], $req['minAmount'],
                    ]);
                }

                if ($req['maxAmount']) {
                    $carts->andWhere('(price = 0.00 AND origPrice <= ?) or (price != 0.00 AND price <= ?)', [
                        $req['maxAmount'], $req['maxAmount'],
                    ]);
                }

                $data = [];
                foreach ($carts as $cart) {
                    $data[] = $cart->toArray() + [
                            'statusText' => $cart->getStatusText(),
                            'product' => $cart->getProduct()->toArray(),
                            'productAmount' => $cart->getProductAmount(),
                            'user' => $cart->getUser()->toArray(),
                        ];
                }

                if ($req['_format'] == 'csv') {
                    return $this->renderCsv($data);
                } else {
                    return $this->suc([
                        'message' => '读取列表成功',
                        'data' => $data,
                        'page' => (int) $req['page'],
                        'rows' => (int) $req['rows'],
                        'records' => $carts->count(),
                    ]);
                }

            default:
                return get_defined_vars();
        }
    }

    protected function renderCsv($carts)
    {
        set_time_limit(0);
        $data = [];
        $data[0] = ['用户', 'openId', '商品', '数量', '现价', '原价', '总价', '加入时间', '状态', '常用地址', '常用联系人', '常用联系方式'];

        foreach ($carts as $cart) {
            $address = wei()->address->getDefaultAddress($cart['user']['id']);
            $data[] = [
                $cart['user']['nickName'],
                $cart['user']['wechatOpenId'],
                $cart['name'],
                $cart['quantity'],
                (float) $cart['price'] ?: $cart['origPrice'],
                $cart['product']['originalPrice'],
                $cart['amount'],
                $cart['createTime'],
                $cart['statusText'],
                $address['province'] . $address['city'] . $address['address'],
                $address['name'],
                $address['contact'],
            ];
        }

        return wei()->csvExporter->export('carts', $data);
    }

    public function updateAction($req)
    {
        wei()->cart()->findOneById($req['id'])->resetCartProductPrice($req['resetCartPrice']);

        return $this->suc();
    }

    public function showAction($req)
    {
        $cart = wei()->cart()->findOneById($req['id']);

        switch ($req['_format']) {
            case 'json':
                return $this->suc([
                    'data' => $cart,
                ]);

            default:
                $product = $cart->getProduct();

                return get_defined_vars();
        }
    }

    public function logAction($req)
    {
        switch ($req['_format']) {
            case 'json':
                $logs = wei()->db('cartLog');

                // 分页
                $logs->limit($req['rows'])->page($req['page']);

                $logs->desc('id');

                $logs->where(['cartId' => $req['cartId']]);

                $logs->findAll();

                $data = [];
                foreach ($logs as $log) {
                    $data[] = $log->toArray() + [
                            'user' => wei()->user()->findOrInitById($log['createUser'])->toArray(),
                        ];
                }

                return $this->suc([
                    'message' => '读取成功',
                    'data' => $data,
                    'page' => $req['page'],
                    'rows' => $req['rows'],
                    'records' => $logs->count(),
                ]);

            default:
                return get_defined_vars();
        }
    }
}
