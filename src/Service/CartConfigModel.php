<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Plugin\BaseModel;
use Miaoxing\Plugin\Model\HasAppIdTrait;
use Miaoxing\Plugin\Model\MineTrait;
use Miaoxing\Plugin\Model\ModelTrait;
use Miaoxing\Plugin\Model\SnowflakeTrait;

/**
 * @property array $selectedIds 选中的购物车编号
 * @property string|null $id
 * @property string $appId 应用编号
 * @property string $userId 用户编号
 * @property string|null $createdAt
 * @property string|null $updatedAt
 * @property string|null $id
 * @property string $appId 应用编号
 * @property string $userId 用户编号
 * @property string|null $createdAt
 * @property string|null $updatedAt
 */
class CartConfigModel extends BaseModel
{
    use HasAppIdTrait;
    use MineTrait;
    use ModelTrait;
    use SnowflakeTrait;

    protected $columns = [
        'selectedIds' => [
            'cast' => [
                'list',
                'type' => 'int',
            ],
        ],
    ];
}
