<input name="id" value="<?= (int) $req['id'] ?>" type="hidden"/>
<table class="js-cart-table record-table table table-bordered table-hover">
  <thead>
  <tr>
    <th>商品</th>
    <th style="width:80px">现价</th>
    <th style="width:80px">原价</th>
    <th style="width:80px">数量</th>
    <th style="width:80px">总价</th>
    <th style="width:200px">修改单价</th>
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
      <input placeholder="填入增加或减少的金额" name="resetCartPrice" type="text" style="width:160px;margin:0;"/>
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
