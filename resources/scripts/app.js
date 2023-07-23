import domReady from '@roots/sage/client/dom-ready';
import { siteInitialize } from './sitemain.mjs';

/**
 * Application entrypoint
 */
domReady(async () => {
  // ...
  siteInitialize();
});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
