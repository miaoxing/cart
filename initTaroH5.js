import React from 'react';
import ReactDOM from 'react-dom';
import {createReactApp} from '@tarojs/runtime';
import {createRouter} from '@tarojs/router';

class App extends React.Component {
  render() {
    return this.props.children;
  }
}

const Page = () => {
  return 'page';
};

const config = {
  pages: [
    'pages/index/index',
    'pages/about/index',
  ],
  window: {
    backgroundTextStyle: 'light',
    navigationBarBackgroundColor: '#fff',
    navigationBarTitleText: 'WeChat',
    navigationBarTextStyle: 'black',
  },
  tabBar: {
    color: '#333',
    selectedColor: '#409EFF',
    backgroundColor: '#fff',
    borderStyle: 'black',
    list: [{
      pagePath: '/pages/index/index', text: '首页',
    }, {
      pagePath: '/pages/about/about', text: '关于我们',
    }],
    mode: 'hash',
    basename: '/test/app',
    customRoutes: {
      '/pages/about/index': '/about',
    },
  },
  router: {mode: 'hash'},
};
config.routes = config.pages?.map(path => ({
  path, load: () => Page,
}));

const inst = createReactApp(App, React, ReactDOM, config);
createRouter(inst, config, 'React');
