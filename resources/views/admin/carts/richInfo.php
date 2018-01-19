<script id="cartInfoTpl" type="text/html">
  <%
    $.each(carts, function (i, cart) {
      // TODO 后台对空数组需转为对象?
      if (cart.specs.length === 0) {
        cart.specs = {};
      }

      cart.specs['数量'] = cart.quantity;
      if (cart.price !== '0.00') {
        cart.specs['现价'] = cart.price;
      }
      cart.specs['原价'] = cart.origPrice;

      if (cart.scores && cart.scores != '0') {
        cart.specs['积分'] = cart.scores;
      }

      var product = {
        id: cart.productId,
        name: cart.name,
        free: cart.free,
        specs: cart.specs,
        images: [cart.image],
        content: ''
      };

      <?php if ($wei->plugin->isInstalled('refund')) : ?>
        if (cart.refundRet) {
          if (cart.refundRet.code > 0) {
            product.content +=
        '<a class="refund-link" href="' + $.url('admin/carts/%s/refunds/new', cart.id) +'">退款</a> ';
          } else if (cart.refundRet.refundId) {
            product.content +=
        '<a class="refund-link" href="' + $.url('admin/refunds?status=0', {id: cart.refundRet.refundId}) + '">'
          + cart.refundRet.processText + '</a> ';
          }
        }
      <?php endif ?>
      <?php $event->trigger('adminOrdersIndexCartJs') ?>
  %>

    <%== template.render('product-tpl', product) %>

    <% if (i != carts.length - 1) { %>
      <hr class="order-cart-hr">
    <% } %>
  <% }) %>
</script>
