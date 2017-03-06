<?php $view->layout() ?>

<?= $block('css') ?>
<link rel="stylesheet" href="<?= $asset('plugins/admin/css/filter.css') ?>"/>
<?= $block->end() ?>

<div class="page-header">
  <a id="export-csv" class="btn btn-default pull-right" href="javascript:void(0);">导出</a>

  <h1>
    购物车管理
  </h1>
</div>

<div class="row">
  <div class="col-xs-12">
    <!-- PAGE CONTENT BEGINS -->
    <div class="table-responsive">
      <form class="form-horizontal filter-form" id="search-form" role="form">
        <div class="well form-well m-b">
          <div class="form-group form-group-sm">

            <label class="col-md-1 control-label" for="status">状态</label>

            <div class="col-md-3">
              <select class="form-control" name="status" id="status">
                <option value="all">全部状态</option>
                <option value="notOrdered" selected>未下订单</option>
                <option value="ordered">已下订单</option>
                <option value="cancel">已取消</option>
              </select>
            </div>

            <label class="col-md-1 control-label" for="startTime">开始时间：</label>

            <div class="col-md-3">
              <input type="text" class="form-control" name="startTime" id="startTime">
            </div>

            <label class="col-md-1 control-label" for="endTime">结束时间：</label>

            <div class="col-md-3">
              <input type="text" class="form-control" name="endTime" id="endTime">
            </div>

          </div>

        </div>

      </form>

      <table id="record-table" class="record-table table table-bordered table-hover js-export-table">
        <thead>
        <tr>
          <th style="width: 180px">用户</th>
          <th>商品</th>
          <th style="width: 56px">数量</th>
          <th style="width: 120px">商品总价(元)</th>
          <th style="width: 138px">加入时间</th>
          <th style="width: 80px">状态</th>
          <th style="width: 80px">操作</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <!-- /.table-responsive -->
    <!-- PAGE CONTENT ENDS -->
  </div>
  <!-- /col -->
</div>
<!-- /row -->

<div class="modal fade js-reset-price-modal" tabindex="-1" role="dialog" aria-labelledby="show-reset-price-modal-label"
     aria-hidden="true">
  <div class="modal-dialog" style="width:800px;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="show-reset-price-modal-label">购物车价格修改</h4>
      </div>

      <div class="modal-body">
        <form id="js-form-reset-price" class="form-horizontal" role="form" action="<?= $url('admin/carts/update') ?>">
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary" id="reset-price-submit">确定</button>
      </div>
    </div>
  </div>
</div>

<div id="log-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">订单日志</h4>
      </div>
      <div class="modal-body">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- 购物车操作 -->
<script id="actions-tpl" type="text/html">
  -
  <!--  <% if (paid == 0 && orderId == 0) { %>-->
  <!--    <a class="reset-price" href="javascript:;" data-id="<%= id %>">修改价格</a><br>-->
  <!--  <% } %>-->
  <!--  <a class="log" href="javascript:;" data-id="<%= id %>">查看日志</a><br>-->
</script>

<?php require $this->getFile('cart:admin/carts/richInfo.php') ?>
<?php require $this->getFile('product:admin/products/richInfo.php') ?>
<?php require $this->getFile('user:admin/user/richInfo.php') ?>
<?php require $this->getFile('mall:admin/orders/export.php') ?>

<?php $event->trigger('adminCartsIndexRender') ?>

<?= $block('js') ?>
<script>
  require(['dataTable', 'form', 'jquery-deparam', 'assets/dateTimePicker'], function () {
    var recordTable = $('#record-table').dataTable({
      ajax: {
        url: $.url('admin/carts?_format=json')
      },
      columns: [
        {
          data: 'user',
          render: function (data, type, full) {
            return template.render('user-info-tpl', data);
          }
        },
        {
          data: 'id',
          render: function (data, type, full) {
            return template.render('cartInfoTpl', {carts: [full], template: template});
          }
        },
        {
          data: 'quantity'
        },
        {
          data: 'productAmount'
        },
        {
          data: 'createTime',
          render: function (data, type, full) {
            return data.substr(0, 16);
          }
        },
        {
          data: 'statusText'
        },
        {
          data: 'id',
          sClass: 'text-center',
          render: function (data, type, full) {
            return template.render('actions-tpl', full);
          }
        }
      ]
    });

    $('#search-form').update(function () {
      recordTable.reload($(this).serialize(), false);
    });

    // 修改价格modal
    recordTable.on('click', '.reset-price', function () {
      var modal = $('.js-reset-price-modal');
      modal.find('#js-form-reset-price').load($.url('admin/carts/show', {id: $(this).data('id')}));
      modal.modal('show');
    });

    $('#js-form-reset-price').ajaxForm({
      dataType: 'json',
      success: function (ret) {
        $.msg(ret, function () {
          if (ret.code > 0) {
            recordTable.dataTable().reload();
            $('.js-reset-price-modal').modal('hide');
          }
        });
      }
    });

    $('#reset-price-submit').click(function () {
      $('#js-form-reset-price').submit();
    });

    // 开始结束时间使用日期时间范围选择器
    $('#startTime, #endTime').rangeDateTimePicker({
      showSecond: true,
      dateFormat: 'yy-mm-dd',
      timeFormat: 'HH:mm:ss'
    });

    // 加载订单日志
    recordTable.on('click', '.log', function () {
      var logModal = $('#log-modal');
      logModal.find('.modal-body').load($.url('admin/carts/log', {cartId: $(this).data('id')}));
      logModal.modal('show');
    });

  });
</script>
<?= $block->end() ?>
