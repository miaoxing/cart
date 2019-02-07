<!-- 超过一种购物车类型的需要显示tab -->
<?php if (count($types) > 1) : ?>
  <ul class="header-tab nav tab-underline border-bottom">
    <li class="nav-item border-primary <?= $req['type'] == 'default' ? 'active' : '' ?>">
      <a class="nav-link text-active-primary" href="<?= $url('carts', ['type' => 'default']) ?>">
        普通商品(<?= $types['default']->length() ?>)</a>
    </li>
    <?php $event->trigger('showCartTab', [$types, $req]) ?>
  </ul>
<?php endif; ?>
