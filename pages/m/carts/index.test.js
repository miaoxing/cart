import Index from './index';
import {render, waitFor} from '@testing-library/react';
import $, {Ret} from 'miaoxing';
import {createPromise, bootstrap, setUrl, resetUrl} from '@mxjs/test';
import * as TaroTest from '@tarojs/taro';

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
});
