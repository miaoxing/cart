define([], function () {
  var Carts = function () {

  };

  $.extend(Carts.prototype, {
    /**
     * 商品控制器对象
     */
    products: null,
    score: 0,
    scoreTitle: null,
    $el: $('body'),
    $: function (selector) {
      return this.$el.find(selector);
    },
    indexAction: function (options) {
      $.extend(this, options);

      var self = this;

      // 点击删除
      this.$('.cart-delete').click(function () {
        self.destroy($(this).data('id'));
      });

      // 选择或取消选择购物车,更新合计
      this.$('.js-cart-checkbox').click(function () {
        var e = $.Event('cart:select', {
          relatedTarget: this
        });
        $(document).trigger(e);
        self.updateAmount();
      });

      // 全选
      this.$('.cart-toggle-all').click(function () {
        var $checkboxes = self.$('.js-cart-checkbox:not([disabled])').prop('checked', $(this).prop('checked'));
        var e = $.Event('cart:selectAll', {
          $checkboxes: $checkboxes
        });
        $(document).trigger(e);
        self.updateAmount();
      });

      // 结算
      this.$('.cart-pay').click(function () {
        var ids = self.getSelectedCartIds();
        if (!ids.length) {
          return $.err('请至少选择一个商品');
        }

        var score = parseInt($('.js-cart-score').html(), 10);
        if (score > self.score) {
          return $.err('积分不足，您当前可用积分为' + self.score + '，还差' + (score - self.score) + '!');
        }

        window.location = $.url('orders/new', {cartId: ids, showwxpaytitle: '1'});
      });

      // 更改数量
      this.initSpinner();

      // 更改规格
      template.helper('$', $);
      $('.js-picker-show').click(function () {
        var $specs = $(this);
        self.products.showCartPicker({
          selectedSkuId: $specs.data('sku-id'),
          cartId: $specs.data('cart-id'),
          productId: $specs.data('product-id'),
          quantity: $specs.closest('.js-cart-item').find('.js-quantity').val()
        });
      });
    },

    /**
     * 获取选中购物车的编号
     */
    getSelectedCartIds: function () {
      var ids = [];
      this.$('.js-cart-checkbox:checked').each(function () {
        ids.push($(this).val());
      });
      return ids;
    },

    /**
     * 更新选中购物车金额
     */
    updateAmount: function () {
      var amount = 0;
      var scores = 0;
      var productCount = 0;

      this.$('.js-cart-checkbox:not([disabled])').each(function () {
        var $this = $(this);
        if ($this.prop('checked')) {
          var quantity = parseInt($(this).closest('.js-cart-item').find('.js-quantity').val());
          amount += quantity * parseFloat($this.data('price'));
          scores += quantity * parseInt($this.data('scores'));
          productCount++;
        }
      });

      amount = amount.toFixed(2);
      var amountText = this.generateAmountText(amount, scores);
      this.$('.js-cart-amount').html(amountText);
      this.$('.cart-product-count').html(productCount);
    },

    initSpinner: function () {
      var self = this;
      $('.spinner-button').click(function () {
        var btn = $(this),
          input = btn.parent().find('.spinner-input'),
          oldVal = parseInt(input.val()),
          newVal = 0;

        if (btn.hasClass('spinner-plus')) {
          newVal = oldVal + 1;
        } else {
          if (oldVal > 1) {
            newVal = oldVal - 1;
          } else {
            newVal = 1;
          }
        }
        triggerSpinnerUpdate(input, oldVal, newVal);
      });

      $('.spinner-input').on('focusin', function () {
        $(this).data('val', $(this).val());
      }).change(function () {
        var $this = $(this);
        var oldVal = $this.data('val');
        var newVal = parseInt($this.val(), 10);
        if (isNaN(newVal) || newVal < 1) {
          newVal = 1;
        }
        $this.val(newVal);
        triggerSpinnerUpdate($this, oldVal, newVal);
      });

      function triggerSpinnerUpdate($input, oldVal, newVal) {
        if (oldVal == newVal) {
          return;
        }
        var e = $.Event('update.spinner', {val: newVal, oldVal: oldVal});
        $input.trigger(e);
        $input.val(newVal);
      }

      $('.js-quantity').on('update.spinner', function (e) {
        $.ajax({
          url: $.url('carts/update-quantity'),
          type: 'post',
          dataType: 'json',
          data: {
            id: $(this).data('id'),
            quantity: e.val
          },
          beforeSend: function () {
            $.loading('show');
          },
          success: function (ret) {
            if (ret.code !== 1) {
              $(e.target).val(e.oldVal);
              $.msg(ret);
            } else {
              self.updateAmount();
            }
          },
          complete: function () {
            $.loading('hide');
          }
        });
      });
    },

    /**
     * 根据金额和积分生成展示文案
     */
    generateAmountText: function (amount, scores) {
      var text = '';
      var hasAmount = amount != '0.00';

      if (hasAmount) {
        text += '￥' + amount;
      }

      if (hasAmount && scores) {
        text += ' + ';
      }

      if (scores) {
        text += '<span class="js-cart-score">' + scores + '</span>' + this.scoreTitle;
      }

      if (text == '') {
        text = '0'
      }

      return text;
    },

    /**
     * 根据编号删除购物车
     */
    destroy: function (id) {
      $.confirm('确认删除商品?', function (result) {
        if (result) {
          $.post($.url('carts/destroy'), {id: id}, function (ret) {
            $.msg(ret, function () {
              window.location.reload();
            });
          }, 'json');
        }
      });
    }
  });

  return new Carts;
})
;
