<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Plugin\BaseService;
use Miaoxing\Plugin\ConfigTrait;

/**
 * @property string $name
 */
class CartConfig extends BaseService
{
    use ConfigTrait;

    protected $configs = [
        'name' => [
            'default' => '购物车',
        ],
    ];

    public static function name(): string
    {
        return '购物车';
    }
}
