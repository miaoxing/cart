<?php $view->layout() ?>

<?php $event->trigger('cartsIndex', [$carts]) ?>

<?php require $view->getFile('@cart/carts/index-tab.php') ?>

<?= $block->css() ?>
<link rel="stylesheet" href="<?= $asset('plugins/product/css/products.css') ?>">
<link rel="stylesheet" href="<?= $asset('plugins/cart/css/carts.css') ?>">
<?= $block->end() ?>

<?php $carts = $types[$req['type']]; ?>
<ul class="list list-intended cart-list">
  <?php foreach ($carts as $i => $cart) :
    $product = $cart->getProduct();
    $sku = $cart->getSku();
    $payable = $cart->checkPayable();
    ?>
    <li class="js-cart-item list-item">
      <div class="list-col align-self-center cart-list-checkbox">
        <div class="custom-control custom-checkbox custom-checkbox-success">
          <input class="js-cart-checkbox custom-control-input" type="checkbox" name="id[]" value="<?= $cart['id'] ?>"
            id="cart-<?= $cart['id'] ?>"
            data-type-id="<?= $product['config']['typeId'] ?>"
            data-price="<?= $cart->getSkuPrice() ?>"
            data-scores="<?= $sku['score'] ?>" <?= $payable['code'] === 1 ? '' : 'disabled' ?>>
          <label class="custom-control-label" for="cart-<?= $cart['id'] ?>"></label>
        </div>
      </div>
      <div class="list-col align-self-center cart-thumb">
        <a href="<?= $url('products/%s', $product['id']) ?>">
          <img src="<?= $asset->thumb($cart->getImage()) ?>">
        </a>
      </div>
      <div class="list-col">
        <div class="d-flex align-items-center">
          <div class="cart-content">
            <h4 class="cart-title list-title truncate-2">
              <a class="js-cart-title" href="<?= $url('products/%s', $product['id']) ?>"><?= $product['name'] ?></a>
            </h4>

            <div class="js-picker-show list-text cart-specs" data-product-id="<?= $product['id'] ?>"
              data-sku-id="<?= $sku['id'] ?>" data-cart-id="<?= $cart['id'] ?>">
              <?php if (!$product->isSingleSku()) : ?>
                <?php foreach ($sku->getSpecsFromCache() as $specName => $specValue) : ?>
                  <?= $specName ?>: <?= $specValue ?>
                <?php endforeach ?>
              <?php else : ?>
                详情
              <?php endif ?>
              <span class="cart-chevron-down"></span>
            </div>
            <?php if ($payable['code'] !== 1) : ?>
              <div class="text-danger cart-tips"><?= $payable['message'] ?></div>
            <?php endif ?>
          </div>

          <div class="text-primary text-right cart-price">
            <?php if ($showPrice) : ?>
              <?= $cart->getPriceText() ?>

              <?php if ($cart['price'] != '0.00') : ?>
                <del class="small text-muted">￥<?= $sku['price'] ?></del>
              <?php endif ?>
            <?php endif ?>
          </div>
        </div>

        <div class="cart-actions">
          <div class="spinner">
            <button class="spinner-button spinner-minus" type="button"></button>
            <input type="text" class="spinner-input js-quantity" id="quantity" value="<?= $cart['quantity'] ?>"
              data-id="<?= $cart['id'] ?>">
            <button class="spinner-button spinner-plus" type="button"></button>
          </div>
          <a class="cart-delete float-right" href="javascript:" data-id="<?= $cart['id'] ?>"></a>
        </div>
      </div>
    </li>
  <?php endforeach ?>

  <?php if (!$carts->length()) : ?>
    <li class="list-empty cart-empty">
      <?= $setting('cart.title') ?: '购物车' ?>空空如也
      <?php if (!$setting('cart.notEnableShowProductsBtn')) : ?>
        <a class="btn btn-secondary btn-block" href="<?= $url('products') ?>">去逛逛</a>
      <?php endif ?>
    </li>
  <?php endif ?>
</ul>

<div class="js-cart-actions border-top cart-footer-bar footer-bar d-flex justify-content-between align-items-center">
  <div class="custom-control custom-checkbox custom-checkbox-success cart-toggle-item">
    <input class="custom-control-input cart-toggle-all" type="checkbox" id="cart-toggle-all">
    <label class="custom-control-label" for="cart-toggle-all">全选</label>
  </div>

  <div class="d-flex align-items-center justify-content-end">
    <?php if ($showPrice) : ?>
      <div class="cart-total-text">
        合计：
      </div>
      <strong class="js-cart-amount cart-amount text-primary">
        0
      </strong>
    <?php endif ?>
    <button class="btn btn-block btn-md btn-primary cart-pay"><?= $setting('order.titleCheckout') ?: '结算' ?>
      (<span class="cart-product-count">0</span>)
    </button>
  </div>
</div>

<?php require $view->getFile('@product/products/picker.php') ?>
<?= $block->js() ?>
<script>
  require([
    'plugins/cart/js/carts',
    'plugins/product/js/products',
    'plugins/app/libs/artTemplate/template.min'
  ], function (carts, products) {
    carts.indexAction({
      products: products,
      score: <?= (int) wei()->curUserV2->score ?>,
      scoreTitle: '<?= $e($setting('score.title', '积分')) ?>'
    });
  });
</script>
<?= $block->end() ?>
