window.$ = window.jQuery = require('jquery');
require('imports-loader?define=>false!jquery-ui');
require('bootstrap');
require('imports-loader?define=>false&exports=>false!bootstrap-datepicker');
require('bootstrap-datepicker/js/locales/bootstrap-datepicker.de');
require('bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css');
require('imports-loader?this=>window!chart.js');
require('imports-loader?this=>window&define=>false&exports=>false!moment');
require('imports-loader?this=>window&define=>false&exports=>false!moment/locale/de');
require('./forms');
require('./sticky-headers');
require('./moment-countdown');

$(function () {
    moment.locale($('html').attr('lang'));
});
