(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-userinfo-email"],{"0143":function(e,t,n){"use strict";var i=n("4ea4");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a=i(n("2edd")),s={goLogin:function(e,t,n){a.default.setPostMessage("login",e,"登录中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},goRegister:function(e,t,n){a.default.setPostMessage("register",e,"处理中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},toVerifyEmail:function(e,t,n){a.default.setPostMessage("verifyEmail",e,"处理中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},findPassword:function(e,t,n){a.default.setPostMessage("findPassword",e,"处理中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},authorizedPhone:function(e,t,n){a.default.setPost("authorizedPhone",e,(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},getRegisterCellphoneCode:function(e,t,n){a.default.setPost("getRegisterCellphoneCode",e,(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},getRegisterEmailCode:function(e,t,n){a.default.setPostMessage("getRegisterEmailCode",e,"处理中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},logout:function(e,t,n){a.default.setPostMessage("logout",{},"退出中",(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},user:function(e,t){a.default.setGetMessage("user",{},"加载中",(function(t){e(t)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},setUser:function(e,t,n){a.default.setPost("user",e,(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},setNotification:function(e,t,n){a.default.setPost("userNotification",e,(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))},unsubscribe:function(e,t,n){a.default.setPost("unsubscribe",e,(function(e){t(e)}),(function(e){uni.showToast({title:e.message,icon:"none",duration:2e3})}))}};t.default=s},"158b":function(e,t,n){"use strict";n.r(t);var i=n("9f6d"),a=n("e4ed");for(var s in a)"default"!==s&&function(e){n.d(t,e,(function(){return a[e]}))}(s);n("66b4");var o,u=n("f0c5"),d=Object(u["a"])(a["default"],i["b"],i["c"],!1,null,"09f06077",null,!1,i["a"],o);t["default"]=d.exports},"2edd":function(e,t,n){"use strict";var i=n("4ea4");n("4d63"),n("ac1f"),n("25f0"),n("5319");n("ffe2");var a=i(n("ee1d"));function s(e,t,n,i,a,s){this.requestLoading(e,t,n,i,"",a,s)}function o(e,t,n,i){this.requestLoading(e,"get",t,"","",n,i)}function u(e,t,n,i,a){this.requestLoading(e,"get",t,"",n,i,a)}function d(e,t,n,i){this.requestLoading(e,"post",t,"","",n,i)}function c(e,t,n,i,a){this.requestLoading(e,"post",t,"",n,i,a)}function r(e,t,n,i){this.requestLoading(e,"put",t,"","",n,i)}function f(e,t,n,i,a){this.requestLoading(e,"put",t,"",n,i,a)}function l(e,t,n,i){this.requestLoading(e,"delete",t,"","",n,i)}function g(e,t,n,i,a){this.requestLoading(e,"delete",t,"",n,i,a)}function m(e,t,n,i,s,o,u){uni.showNavigationBarLoading(),""!=s&&uni.showLoading({title:s});var d=uni.getStorageSync("dsshopApplytoken");uni.request({url:a.default.BaseURL+e,data:n,header:i||{"content-type":"application/json","apply-secret":a.default.secret,openid:uni.getStorageSync("applyDsshopOpenid"),Authorization:"Bearer "+d},method:t||"get",success:function(e){uni.hideNavigationBarLoading(),""!=s&&uni.hideLoading(),200==e.statusCode?"ok"===e.data.result?o(e.data.message):u({message:e.data}):500==e.statusCode?u({message:"服务器异常，请重新尝试"}):302==e.statusCode?u({message:"登录超时，请重新登录"}):401==e.statusCode?u({message:e.data.message}):u({message:"服务器异常，请重新尝试"})},fail:function(e){uni.hideNavigationBarLoading(),""!=s&&uni.hideLoading(),e.data?u({message:e.data.message}):u({message:"服务器异常"})},complete:function(e){}})}e.exports={request:s,setGet:o,setGetMessage:u,setPost:d,setPostMessage:c,setPut:r,setPutMessage:f,setDelete:l,setDeleteMessage:g,requestLoading:m}},"3dc5":function(e,t,n){var i=n("99c4");"string"===typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);var a=n("4f06").default;a("69a85550",i,!0,{sourceMap:!1,shadowMode:!1})},"4d36":function(e,t,n){"use strict";var i=n("4ea4");Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a=i(n("5530")),s=i(n("0143")),o=n("2f62"),u={data:function(){return{codename:"获取验证码",disabled:!1,unit:"",seconds:"",data:{oldEmail:"",email:"",code:""}}},onLoad:function(e){e.email?(this.data.oldEmail=e.email,uni.setNavigationBarTitle({title:"修改邮箱"})):uni.setNavigationBarTitle({title:"绑定邮箱"}),this.loginCheck()},methods:(0,a.default)((0,a.default)({},(0,o.mapMutations)(["loginCheck"])),{},{getCode:function(){var e=this;if(!this.data.email)return this.$api.msg("请填写邮箱"),!1;var t=/^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;if(!t.test(this.data.email))return this.$api.msg("邮箱格式有误"),!1;s.default.getRegisterEmailCode(this.data,(function(t){e.$api.msg("发送成功"),e.seconds=60,e.codename="",e.unit="s",e.disabled=!0,e.timer=setInterval((function(){e.seconds=e.seconds-1,0==e.seconds&&(clearInterval(e.timer),e.seconds="",e.codename="获取验证码",e.unit="",e.disabled=!1)}),1e3)}))},toVerifyEmail:function(){var e=this.data,t=this;if(!e.email)return this.$api.msg("请填写邮箱"),!1;var n=/^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;return n.test(e.email)?e.code?5!=e.code.length?(this.$api.msg("验证码长度有误"),!1):void s.default.toVerifyEmail(e,(function(e){t.$api.msg("绑定成功"),setTimeout((function(){uni.navigateBack({delta:1})}),800)})):(this.$api.msg("验证码必须"),!1):(this.$api.msg("邮箱格式有误"),!1)}})};t.default=u},"66b4":function(e,t,n){"use strict";var i=n("3dc5"),a=n.n(i);a.a},"99c4":function(e,t,n){var i=n("24fb");t=i(!1),t.push([e.i,'@charset "UTF-8";\n/* 页面左右间距 */\n/* 文字尺寸 */\n/*文字颜色*/\n/* 边框颜色 */\n/* 图片加载中颜色 */\n/* 行为相关颜色 */\n/* 文章场景相关 */uni-page-body[data-v-09f06077]{background:#f8f8f8}body.?%PAGE?%[data-v-09f06077]{background:#f8f8f8}',""]),e.exports=t},"9f6d":function(e,t,n){"use strict";var i;n.d(t,"b",(function(){return a})),n.d(t,"c",(function(){return s})),n.d(t,"a",(function(){return i}));var a=function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("v-uni-view",{staticClass:"container"},[n("v-uni-form",[e.data.oldEmail?n("v-uni-view",{staticClass:"cu-form-group"},[n("v-uni-view",{staticClass:"title"},[e._v("当前绑定邮箱")]),e._v(e._s(e.data.oldEmail))],1):e._e(),n("v-uni-view",{staticClass:"cu-form-group"},[n("v-uni-view",{staticClass:"title"},[e._v("新邮箱")]),n("v-uni-input",{attrs:{placeholder:"请输入新邮箱",name:"email"},model:{value:e.data.email,callback:function(t){e.$set(e.data,"email",t)},expression:"data.email"}})],1),n("v-uni-view",{staticClass:"cu-form-group"},[n("v-uni-view",{staticClass:"title"},[e._v("验证码")]),n("v-uni-input",{attrs:{maxlength:"5",placeholder:"请输入验证码",name:"code"},model:{value:e.data.code,callback:function(t){e.$set(e.data,"code",t)},expression:"data.code"}}),n("v-uni-button",{staticClass:"cu-btn bg-red shadow round getcode",attrs:{disabled:e.disabled},on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.getCode.apply(void 0,arguments)}}},[e._v(e._s(e.codename+e.seconds+e.unit))])],1),n("v-uni-view",{staticClass:" flex flex-direction padding"},[n("v-uni-button",{staticClass:"cu-btn round bg-red shadow lg",on:{click:function(t){arguments[0]=t=e.$handleEvent(t),e.toVerifyEmail.apply(void 0,arguments)}}},[e._v("提交")])],1)],1)],1)},s=[]},e4ed:function(e,t,n){"use strict";n.r(t);var i=n("4d36"),a=n.n(i);for(var s in i)"default"!==s&&function(e){n.d(t,e,(function(){return i[e]}))}(s);t["default"]=a.a}}]);