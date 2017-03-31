<table class="cart-log-table table table-bordered table-hover">
  <thead>
  <tr>
    <th>备注</th>
    <th class="t-12">操作用户</th>
    <th class="t-12">操作时间</th>
  </tr>
  </thead>
  <tbody>
  </tbody>
</table>

<script>
  require(['dataTable', 'form', 'jquery-deparam'], function () {
    var recordTable = $('.cart-log-table').dataTable({
      ajax: {
        url: $.url('admin/carts/log.json', {cartId: '<?= (int) $req['cartId'] ?>'})
      },
      columns: [
        {
          data: 'note',
          sClass: 'text-center'
        },
        {
          data: 'user',
          render: function (data, type, full) {
            return template.render('user-info-tpl', data);
          }
        },
        {
          data: 'createTime'
        }
      ]
    });
  });
</script>
