import Index from './index';
import {render, waitFor, fireEvent, waitForElementToBeRemoved} from '@testing-library/react';
import $, {Ret} from 'miaoxing';
import {createPromise, bootstrap, setUrl, resetUrl} from '@mxjs/test';
import * as TaroTest from '@tarojs/taro';
import {createProduct} from '@miaoxing/product/test-utils';
import Taro from '@tarojs/taro';
import {reset} from 'use-uid';

bootstrap();
let didShow;
// eslint-disable-next-line no-import-assign
TaroTest.useDidShow = (fn) => {
  didShow = fn;
};

describe('Index', () => {
  beforeEach(() => {
    reset();
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

    expect(container).toMatchSnapshot();
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

  test('delete', async () => {
    const product = createProduct();

    const promise = createPromise();
    const promise2 = createPromise();
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
      }));

    const {getByText, queryByText} = render(<Index/>);
    didShow();

    await waitFor(() => {
      expect(getByText('结算（1）')).not.toBeNull();
    });

    const remove = getByText('移除');

    fireEvent.click(remove);

    await waitForElementToBeRemoved(() => queryByText('移除'));

    expect(getByText('购物车空空如也')).not.toBeNull();
    expect(getByText('结算（0）')).not.toBeNull();

    const amount = getByText('合计：');
    expect(amount.textContent).toBe('合计：￥0');

    expect($.http).toMatchSnapshot();
  });

  test('checkout', async () => {
    const product = createProduct();
    $.http = jest.fn()
      .mockResolvedValueOnce({
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
          selected: [],
        }),
      });
    $.err = jest.fn();

    const {container, findByText} = render(<Index/>);
    didShow();

    await findByText(product.name);
    expect($.http).toMatchSnapshot();

    const checkout = await findByText('结算（0）');
    fireEvent.click(checkout);
    expect($.err).toBeCalledWith('请选择要结算的商品');

    fireEvent.change(container.querySelectorAll('taro-checkbox-group-core')[1]);
    expect(checkout.textContent).toBe('结算（1）');

    Taro.navigateTo = jest.fn();
    fireEvent.click(checkout);
    await waitFor(() => expect(Taro.navigateTo).toBeCalled());
  });

  test('update sku', async () => {
    const product = createProduct();
    $.http = jest.fn()
      .mockResolvedValueOnce({
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
      });

    const {getByText, findByText} = render(<Index/>);
    didShow();

    await findByText(product.name);

    const amount = getByText('合计：');
    expect(amount.textContent).toBe('合计：￥' + (5 * 12));

    $.http.mockResolvedValueOnce({
      ret: Ret.suc({
        data: product,
      }),
    });
    const skuValues = getByText('M；红色');
    fireEvent.click(skuValues);

    const colorBlue = await findByText('蓝色');
    fireEvent.click(colorBlue);

    $.http.mockResolvedValueOnce({ret: Ret.suc()});
    $.http.mockResolvedValueOnce({
      ret: Ret.suc({
        data: [
          {
            id: 1,
            productId: product.id,
            skuId: product.skus[3].id,
            quantity: 5,
            changedPrice: null,
            addedPrice: '9',
            configs: {},
            // updated
            updatedAt: Date.now(),
            sku: product.skus[3],
            product: product,
            createOrder: Ret.suc(),
          },
        ],
        selected: [1],
      }),
    });
    fireEvent.click(getByText('确 定'));

    await waitFor(() => expect(amount.textContent).toBe('合计：￥' + (5 * 14)));
  });

  test('product deleted', async () => {
    const product = createProduct();
    product.deletedAt = Date.now();
    $.http = jest.fn()
      .mockResolvedValueOnce({
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
              product: product,
              createOrder: Ret.err('该商品已失效'),
            },
          ],
          selected: [],
        }),
      });
    $.err = jest.fn();

    const {container, findByText, queryByText} = render(<Index/>);
    didShow();

    await findByText(product.name);
    expect($.http).toMatchSnapshot();

    // 不显示规格，价格，步进器
    expect(queryByText('红色')).toBeNull();
    expect(container.querySelector('.mx-stepper')).toBeNull();
    expect(queryByText(product.skus[2].price)).toBeNull();

    // 显示错误提示
    expect(queryByText('该商品已失效')).not.toBeNull();
  });

  test('sku deleted', async () => {
    const product = createProduct();
    $.http = jest.fn()
      .mockResolvedValueOnce({
        ret: Ret.suc({
          data: [
            {
              id: 1,
              productId: product.id,
              skuId: null,
              quantity: 5,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: null,
              product: product,
              createOrder: Ret.err('该规格已失效'),
            },
          ],
          selected: [],
        }),
      });

    const {findByText, getByText, queryByText} = render(<Index/>);
    didShow();

    await findByText(product.name);

    $.http.mockResolvedValueOnce({
      ret: Ret.suc({
        data: product,
      }),
    });
    const reSelect = getByText('重选');
    fireEvent.click(reSelect);

    $.http.mockResolvedValueOnce({ret: Ret.suc()});
    $.http.mockResolvedValueOnce({
        ret: Ret.suc({
          data: [
            {
              id: 1,
              productId: product.id,
              skuId: 4,
              quantity: 5,
              changedPrice: null,
              addedPrice: '9',
              configs: {},
              updatedAt: '2021-06-28 16:45:59',
              sku: product.skus[3],
              product: product,
              createOrder: Ret.suc(),
            },
          ],
          selected: [],
        }),
      });
    fireEvent.click(await findByText('蓝色'));
    fireEvent.click(await findByText('M'));
    fireEvent.click(getByText('确 定'));

    await waitForElementToBeRemoved(() => queryByText('重选'));

    expect($.http).toMatchSnapshot();
  });
});
