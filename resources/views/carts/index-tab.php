<!-- 超过一种购物车类型的需要显示tab -->
<?php if (count($types) > 1) : ?>
  <ul class="header-tab nav tab-underline">
    <li class="nav-item">
      <a class="nav-link <?= $req['type'] == 'default' ? 'active' : '' ?> text-active-primary border-active-primary"
        href="<?= $url('carts', ['type' => 'default']) ?>">
        普通商品(<?= $types['default']->length() ?>)</a>
    </li>
    <?php $event->trigger('showCartTab', [$types, $req]) ?>
  </ul>
<?php endif; ?>
