import Index from './index';
import {render, waitFor, fireEvent} from '@testing-library/react';
import $, {Ret} from 'miaoxing';
import {createPromise, bootstrap, setUrl, resetUrl} from '@mxjs/test';
import * as TaroTest from '@tarojs/taro';
import {createProduct} from '@miaoxing/product/test-utils';
import pretty from 'pretty';
import Taro from '@tarojs/taro';

bootstrap();
let didShow;
// eslint-disable-next-line no-import-assign
TaroTest.useDidShow = (fn) => {
  didShow = fn;
};

describe('Index', () => {
  beforeEach(() => {
    setUrl('/carts');
  });

  afterEach(() => {
    resetUrl();
  });

  test('empty', async () => {
    const promise = createPromise();

    $.http = jest.fn()
      .mockImplementationOnce(() => promise.resolve({
        ret: Ret.suc({
          data: [],
        }),
      }));

    const {container, getByText} = render(<Index/>);
    didShow();

    await waitFor(() => {
      expect(getByText('购物车空空如也')).not.toBeNull();
    });

    expect(container).toMatchSnapshot();
    expect($.http).toMatchSnapshot();
  });

  test('list', async () => {
    const product = createProduct();

    const promise = createPromise();

    $.http = jest.fn()
      .mockImplementationOnce(() => promise.resolve({
        ret: Ret.suc({
          data: [
            {
              id: 1,
              productId: product.id,
              skuId: product.skus[0].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[0],
              product: product,
              createOrder: Ret.suc(),
            },
            {
              id: 2,
              productId: product.id,
              skuId: product.skus[1].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[1],
              product: product,
              createOrder: Ret.err('该商品已失效'),
            },
            {
              id: 3,
              productId: product.id,
              skuId: product.skus[2].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[2],
              product: product,
              createOrder: Ret.err('该规格已失效'),
            },
          ],
          selected: [1, 3],
        }),
      }));

    const {container, getByText} = render(<Index/>);
    didShow();

    await waitFor(() => {
      expect(getByText('该商品已失效')).not.toBeNull();
    });

    const html = pretty(container.innerHTML.replace(/id="swipeAction(.+?)"/g, 'id="REPLACED"'));

    expect(html).toMatchSnapshot();
    expect($.http).toMatchSnapshot();
  });

  test('select', async () => {
    const product = createProduct();

    const promise = createPromise();

    $.http = jest.fn()
      .mockImplementationOnce(() => promise.resolve({
        ret: Ret.suc({
          data: [
            {
              id: 1,
              productId: product.id,
              skuId: product.skus[0].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[0],
              product: product,
              createOrder: Ret.suc(),
            },
            {
              id: 2,
              productId: product.id,
              skuId: product.skus[1].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[1],
              product: product,
              createOrder: Ret.err('该商品已失效'),
            },
            {
              id: 3,
              productId: product.id,
              skuId: product.skus[2].id,
              quantity: 9,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[2],
              product: product,
              createOrder: Ret.suc(),
            },
          ],
          selected: [1, 3],
        }),
      }));

    const {container, getByText, findByText} = render(<Index/>);
    didShow();

    const checkout = await findByText('结算（2）');
    const amount = getByText('合计：');

    const checkboxes = container.querySelectorAll('taro-checkbox-core');
    const checkboxGroups = container.querySelectorAll('taro-checkbox-group-core');
    expect(checkboxes.length).toBe(4);

    // 默认选中1，3
    expect(checkboxes[0].checked).toBeTruthy();
    expect(checkboxes[0].disabled).toBeUndefined();

    expect(checkboxes[1].checked).toBeUndefined();
    expect(checkboxes[1].disabled).toBeTruthy();

    expect(checkboxes[2].checked).toBeTruthy();
    expect(checkboxes[2].disabled).toBeUndefined();

    expect(checkout.textContent).toBe('结算（2）');
    expect(amount.textContent).toBe('合计：￥' + (9 * 9 + 12 * 9));

    // 取消选择1
    fireEvent(checkboxGroups[0], new CustomEvent('change', {
      detail: {
        value: [3],
      },
    }));
    expect(checkboxes[0].checked).toBeUndefined();
    expect(checkboxes[1].checked).toBeUndefined();
    expect(checkboxes[2].checked).toBeTruthy();

    expect(checkout.textContent).toBe('结算（1）');
    expect(amount.textContent).toBe('合计：￥' + (12 * 9));

    // 全选
    fireEvent.change(checkboxGroups[1]);
    expect(checkboxes[0].checked).toBeTruthy();
    expect(checkboxes[1].checked).toBeUndefined();
    expect(checkboxes[2].checked).toBeTruthy();

    expect(checkout.textContent).toBe('结算（2）');
    expect(amount.textContent).toBe('合计：￥' + (9 * 9 + 12 * 9));

    // 取消全选
    fireEvent.change(checkboxGroups[1]);
    expect(checkboxes[0].checked).toBeUndefined();
    expect(checkboxes[1].checked).toBeUndefined();
    expect(checkboxes[2].checked).toBeUndefined();

    expect(checkout.textContent).toBe('结算（0）');
    expect(amount.textContent).toBe('合计：￥0');

    // 选择1
    fireEvent(checkboxGroups[0], new CustomEvent('change', {
      detail: {
        value: [1],
      },
    }));
    expect(checkboxes[0].checked).toBeTruthy();
    expect(checkboxes[1].checked).toBeUndefined();
    expect(checkboxes[2].checked).toBeUndefined();

    expect(checkout.textContent).toBe('结算（1）');
    expect(amount.textContent).toBe('合计：￥' + (9 * 9));

    // 提交
    Taro.navigateTo = jest.fn();
    fireEvent.click(checkout);
    await waitFor(() => expect(Taro.navigateTo).toBeCalled());

    expect($.http).toMatchSnapshot();
  });

  test('quantity', async () => {
    const product = createProduct();

    const promise = createPromise();
    const promise2 = createPromise();
    const promise3 = createPromise();

    $.http = jest.fn()
      .mockImplementationOnce(() => promise.resolve({
        ret: Ret.suc({
          data: [
            {
              id: 1,
              productId: product.id,
              skuId: product.skus[2].id,
              quantity: 5,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[2],
              product: product,
              createOrder: Ret.suc(),
            },
          ],
          selected: [1],
        }),
      }))
      .mockImplementationOnce(() => promise2.resolve({
        ret: Ret.suc(),
      }))
      .mockImplementationOnce(() => promise3.resolve({
        ret: Ret.suc(),
      }));

    const {container, getByText} = render(<Index/>);
    didShow();

    await waitFor(() => {
      expect(getByText('结算（1）')).not.toBeNull();
    });

    const amount = getByText('60');

    const stepper = container.querySelector('.mx-stepper-input');

    const minus = container.querySelector('.mx-stepper-minus');
    fireEvent.click(minus);
    expect(stepper.value).toBe(4);
    expect(amount.textContent).toBe('￥48');

    const plus = container.querySelector('.mx-stepper-plus');
    fireEvent.click(plus);
    expect(stepper.value).toBe(5);
    expect(amount.textContent).toBe('￥60');

    expect($.http).toMatchSnapshot();
  });
});
