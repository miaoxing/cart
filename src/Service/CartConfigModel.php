<?php

namespace Miaoxing\Cart\Service;

use Miaoxing\Cart\Metadata\CartConfigTrait;
use Miaoxing\Plugin\BaseModel;
use Miaoxing\Plugin\Model\HasAppIdTrait;
use Miaoxing\Plugin\Model\MineTrait;
use Miaoxing\Plugin\Model\ModelTrait;
use Miaoxing\Plugin\Model\SnowflakeTrait;

/**
 * @property array $selectedIds
 */
class CartConfigModel extends BaseModel
{
    use CartConfigTrait;
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
