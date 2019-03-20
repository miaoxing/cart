<input name="id" value="<?= (int) $req['id'] ?>" type="hidden"/>
<table class="js-cart-table record-table table table-bordered table-hover">
  <thead>
  <tr>
    <th>商品</th>
    <th class="t-5">现价</th>
    <th class="t-5">原价</th>
    <th class="t-5">数量</th>
    <th class="t-5">总价</th>
    <th class="t-12">修改单价</th>
  </tr>
  </thead>
  <tbody>
  <tr>
    <td class="product-info">

    </td>
    <td><?= $cart->getCurPrice() ?></td>
    <td><?= $cart['origPrice'] ?></td>
    <td><?= $cart['quantity'] ?></td>
    <td><?= sprintf('%.2f', $cart['quantity'] * $cart->getCurPrice()) ?></td>
    <td>
      <input class="form-control" placeholder="填入增加或减少的金额" name="resetCartPrice" type="text"/>
    </td>
  </tr>
  </tbody>
</table>

<script>
  var recordTable = $('.js-order-cart-table').dataTable({
    dom: "t<'row hide'<'col-sm-6'ir><'col-sm-6'pl>>"
  });

  var html = template.render('product-tpl', <?= json_encode($product->toArray()); ?>);
  $('.product-info').html(html);
</script>
