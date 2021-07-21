import Index from './index';
import {render, waitFor} from '@testing-library/react';
import $, {Ret} from 'miaoxing';
import {createPromise, bootstrap, setUrl, resetUrl} from '@mxjs/test';
import * as TaroTest from '@tarojs/taro';
import {createProduct} from '@miaoxing/product/test-utils';
import pretty from 'pretty';

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
});
