/* eslint no-unused-vars: "off" */

import '@babel/polyfill';
import { distanceInWords, distanceInWordsToNow, isBefore } from 'date-fns';
import 'grapheme-splitter';
import $ from 'jquery';
import { debounce } from 'lodash';

window.$ = window.jQuery = $;
