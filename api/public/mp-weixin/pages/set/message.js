(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/set/message"],{3379:function(e,t,n){"use strict";n.r(t);var r=n("6d88"),u=n.n(r);for(var i in r)"default"!==i&&function(e){n.d(t,e,(function(){return r[e]}))}(i);t["default"]=u.a},"3cbd":function(e,t,n){"use strict";n.r(t);var r=n("4c6f"),u=n("3379");for(var i in u)"default"!==i&&function(e){n.d(t,e,(function(){return u[e]}))}(i);n("cc90");var c,o=n("f0c5"),a=Object(o["a"])(u["default"],r["b"],r["c"],!1,null,null,null,!1,r["a"],c);t["default"]=a.exports},"4c6f":function(e,t,n){"use strict";var r;n.d(t,"b",(function(){return u})),n.d(t,"c",(function(){return i})),n.d(t,"a",(function(){return r}));var u=function(){var e=this,t=e.$createElement;e._self._c},i=[]},"6d88":function(e,t,n){"use strict";(function(e){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var r=i(n("0143")),u=n("2f62");function i(e){return e&&e.__esModule?e:{default:e}}function c(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function o(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?c(Object(n),!0).forEach((function(t){a(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):c(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function a(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var f={data:function(){return{user:{}}},onShow:function(){this.loginCheck(),this.getUser()},methods:o(o({},(0,u.mapMutations)(["loginCheck"])),{},{getUser:function(){var e=this;r.default.user((function(t){e.user=t}))},setNotification:function(t){var n=t.currentTarget.dataset.type;if("email"===n){if(!this.user.email)return this.$api.msg("请先绑定邮箱"),setTimeout((function(){e.navigateTo({url:"/pages/userinfo/email"})}),800),!1}else if("wechat"===n&&!this.user.wechat)return this.$api.msg("请先关注微信公众号"),setTimeout((function(){e.navigateTo({url:"/pages/public/subscribe"})}),800),!1;this.user.notification[n]=t.detail.value,r.default.setNotification(this.user,(function(e){}))}})};t.default=f}).call(this,n("543d")["default"])},"6def":function(e,t,n){"use strict";(function(e){n("ebeb");r(n("66fd"));var t=r(n("3cbd"));function r(e){return e&&e.__esModule?e:{default:e}}e(t.default)}).call(this,n("543d")["createPage"])},cc90:function(e,t,n){"use strict";var r=n("fb05"),u=n.n(r);u.a},fb05:function(e,t,n){}},[["6def","common/runtime","common/vendor"]]]);