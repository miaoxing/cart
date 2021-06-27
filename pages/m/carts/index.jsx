import {useState, useEffect} from 'react';
import {View, Image, Label, Navigator, Block} from '@fower/taro';
import $ from 'miaoxing';
import Page from '@mxjs/m-page';
import './index.scss';
import Taro, {useDidShow} from '@tarojs/taro';
import Icon from '@mxjs/m-icon';
import FooterBar from '@mxjs/m-footer-bar';
import Card from '@mxjs/m-card';
import Button from '@mxjs/m-button';
import ButtonTheme from '@mxjs/m-button/ButtonTheme';
import Price from '@miaoxing/product/components/m/Price';
import SkuPicker from '@miaoxing/product/components/m/SkuPicker';
import Ret from '@mxjs/m-ret';
import {AtSwipeAction} from "taro-ui";
import {List, ListItem, ListCol} from '@mxjs/m-list';
import money from '@mxjs/money';
import Empty from '@mxjs/m-empty';
import Stepper from '@mxjs/m-stepper';
import {Checkbox, CheckboxGroup} from '@mxjs/m-checkbox';

const getSpecValueNames = (specValueIds, specs) => {
  const names = [];
  specs.forEach(specs => {
    specs.values.forEach(value => {
      if (specValueIds.includes(value.id)) {
        names.push(value.name);
      }
    });
  });
  return names.join('；');
};

const CartItem = ({cart, selected, quantity, onChangeQuantity, onClickSpec, width, onRemoveCart, index}) => {
  const updateQuantity = (value) => {
    onChangeQuantity(value, cart);
    $.put({
      url: $.apiUrl('carts/%s/quantity', cart.id),
      data: {
        quantity: value,
      },
    }).then(({ret}) => {
      if (ret.isErr()) {
        onChangeQuantity(quantity, cart);
        $.ret(ret);
      }
    });
  };

  return (
    <Card>
      <List>
        <AtSwipeAction
          maxDistance={75}
          areaWidth={width}
          key={cart.id}
          options={[{text: '移除', style: {backgroundColor: 'var(--mx-colors-danger-500)'}}]}
          onClick={() => {
            onRemoveCart(index);
          }}
        >
          <ListItem w="100%" px3>
            <ListCol flex="0">
              <View toCenterY>
                <Checkbox value={cart.id} checked={selected} disabled={cart.createOrder.code !== 0}/>
                <Navigator ml3 text0 rounded2 overflowHidden url={$.url('products/show', {id: cart.product.id})}>
                  <Image src={cart.product.image} h20 w20 mode="aspectFit"/>
                </Navigator>
              </View>
            </ListCol>

            <ListCol pl3 column toBetween>
              <Navigator hoverClass="none" textSM truncate2 url={$.url('products/show', {id: cart.product.id})}>
                {cart.product.name}
              </Navigator>

              {cart.sku && !cart.product.spec.isDefault && <View mt2>
                <View inlineBlock p1 pl2 textXS gray500 bgGray100 rounded1 onClick={onClickSpec}>
                  {getSpecValueNames(cart.sku.specValueIds, cart.product.spec.specs)}
                  <Icon ml1 type="chevron-down"/>
                </View>
              </View>}

              {cart.sku && <View mt2 row toCenterY toBetween>
                <Price>{cart.changedPrice || cart.sku.price}</Price>
                <View>
                  <Stepper value={quantity} onChange={updateQuantity} min={1} max={cart.sku.stockNum}/>
                </View>
              </View>}

              {cart.createOrder.code !== 0 && <View mt2 red500 textSM>{cart.createOrder.message}</View>}

              {!cart.sku && <View mt2 onClick={onClickSpec} textRight>
                <Button variant="outline-primary" size="xs">重选</Button>
              </View>}

            </ListCol>
          </ListItem>
        </AtSwipeAction>
      </List>
    </Card>
  );
};

const Index = () => {
  const [ret, setRet] = useState({});
  const carts = ret.data || [];

  // 选中的购物车编号
  const [selected, setSelected] = useState([]);

  // 是否选中所有购物车
  const [selectedAll, setSelectedAll] = useState(false);

  // 购物车中的数量
  const [quantities, setQuantities] = useState({});

  // 选中购物车的总计金额
  const [amount, setAmount] = useState('0');

  const [isOpened, setIsOpened] = useState(false);
  const [skuPicker, setSkuPicker] = useState({});

  // 所有购物车的最后更新时间
  const updatedAt = carts.map(cart => cart.updatedAt).join();

  // 更新合计金额
  useEffect(() => {
    const amount = carts.reduce((amount, cart) => {
      if (selected.includes(cart.id)) {
        return money(cart.changedPrice || cart.sku.price).mul(quantities[cart.id]).add(amount);
      } else {
        return amount;
      }
    }, '0');
    setAmount(amount);
    // 选中购物车时，进入加载购物车时，数量更改时
  }, [selected.join(), updatedAt, Object.values(quantities).join()]);

  // 更新全选状态
  useEffect(() => {
    setSelectedAll(selected.length > 0 && selected.length === getAvailableCartIds().length);
  }, [selected.join(), updatedAt]);

  useDidShow(() => {
    load();
  });

  const [width, setWidth] = useState(0);
  useEffect(() => {
    Taro.getSystemInfo({
      success: function (res) {
        setWidth(res.screenWidth);
      },
    });
  }, []);

  const load = () => {
    $.http({
      url: $.apiUrl('carts'),
    }).then(({ret}) => {
      if (ret.isErr()) {
        $.ret(ret);
        return;
      }

      const selected = [];
      ret.data.forEach(cart => {
        quantities[cart.id] = cart.quantity;
        // 过滤失效的编号
        if (ret.selected.includes(cart.id) && cart.createOrder.code === 0) {
          selected.push(cart.id);
        }
      });
      setQuantities(quantities);
      setSelected(selected);
      setRet(ret);
    });
  };

  const changeQuantity = (quantity, cart) => {
    quantities[cart.id] = quantity;
    setQuantities({...quantities});
  };

  const getAvailableCartIds = () => {
    return carts.reduce((ids, cart) => {
      if (cart.createOrder.code === 0) {
        ids.push(cart.id);
      }
      return ids;
    }, []);
  };

  const changeCheckbox = (e) => {
    const selected = e.detail.value.map(value => parseInt(value, 10));
    updateSelected(selected);
  };

  const toggleAll = () => {
    const selected = selectedAll ? [] : getAvailableCartIds();
    updateSelected(selected);
  };

  const updateSelected = (selected) => {
    setSelected(selected);
    $.put($.apiUrl('carts/selected'), {data: {ids: selected}});
  };

  const checkout = () => {
    if (!selected.length) {
      return $.err('请选择要结算的商品');
    }
    Taro.navigateTo({
      url: $.url('orders/new', {cartIds: selected.join(',')}),
    });
  };

  const handleClickSpec = async (cart) => {
    const {ret} = await $.get($.apiUrl('products/%s', cart.productId));
    if (ret.isErr()) {
      return $.ret(ret);
    }

    setSkuPicker({
      product: ret.data,
      quantity: cart.quantity,
      // 删除 SKU 则无值
      selectedValueIds: cart.sku?.specValueIds,
      cartId: cart.id,
    });
    setIsOpened(true);
  };

  const removeCart = (index) => {
    const id = carts[index].id;
    $.delete({
      url: $.apiUrl('carts/%s', id),
    }).then(({ret}) => {
      if (ret.isErr()) {
        $.ret(ret);
        return;
      }

      carts.splice(index, 1);
      ret.data = carts;
      setRet({...ret});

      const selectedIndex = selected.indexOf(id);
      if (-1 !== selectedIndex) {
        selected.splice(selectedIndex, 1);
      }
      setSelected([...selected]);
    });
  };

  return (
    <Page>
      <Ret ret={ret}>
        <CheckboxGroup onChange={changeCheckbox} pt2>
          {carts.map((cart, index) => {
            return (
              <CartItem
                key={cart.id}
                width={width}
                cart={cart}
                selected={selected.includes(cart.id)}
                quantity={quantities[cart.id]}
                onChangeQuantity={changeQuantity}
                onClickSpec={handleClickSpec.bind(this, cart)}
                onRemoveCart={removeCart}
                index={index}
              />
            );
          })}
        </CheckboxGroup>

        {carts.length === 0 && <Empty description="购物车空空如也">
          <Navigator url={$.url('products')}>
            <Button variant="primary">去逛逛</Button>
          </Navigator>
        </Empty>}

        <Block>
          {skuPicker.product && <SkuPicker
            isOpened={isOpened}
            action="updateCart"
            product={skuPicker.product}
            quantity={skuPicker.quantity}
            selectedValueIds={skuPicker.selectedValueIds}
            cartId={skuPicker.cartId}
            onClose={() => {
              setIsOpened(false);
            }}
            onAfterRequest={(ret) => {
              ret.isSuc() && load();
            }}
          />}
        </Block>
      </Ret>
      <FooterBar toCenterY toBetween>
        <CheckboxGroup onChange={toggleAll}>
          <Label toCenterY ml3 textSM gray500 leadingNone>
            <Checkbox checked={selectedAll} mr2/>
            全选
          </Label>
        </CheckboxGroup>

        <View toCenterY>
          <View mr2 textSM gray500>
            合计：<Price>{amount}</Price>
          </View>
          <ButtonTheme>
            <Button variant="primary" onClick={checkout}>结算（{selected.length}）</Button>
          </ButtonTheme>
        </View>
      </FooterBar>
    </Page>
  );
};

export default Index;
