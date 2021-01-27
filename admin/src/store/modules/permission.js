import { constantRouterMap } from '@/router'
import Layout from '@/views/layout/Layout'
// 模板
export const componentsMap = {
  Dashboard: () => import('@/views/Dashboard/index'),
  Admin: () => import('@/views/User/Admin/index'),
  AdminList: () => import('@/views/User/Admin/list'),
  Manage: () => import('@/views/User/Manage/index'),
  ManageList: () => import('@/views/User/Manage/list'),
  Member: () => import('@/views/User/Member/index'),
  MemberList: () => import('@/views/User/Member/list'),
  Power: () => import('@/views/User/Power/index'),
  PowerList: () => import('@/views/User/Power/list'),
  RedisServices: () => import('@/views/Tool/RedisService/index'),
  RedisServicesList: () => import('@/views/Tool/RedisService/list'),
  RedisPanel: () => import('@/views/Tool/RedisService/panel'),
  Specification: () => import('@/views/GoodsManage/Specification/index'),
  SpecificationList: () => import('@/views/GoodsManage/Specification/list'),
  SpecificationGroup: () => import('@/views/GoodsManage/SpecificationGroup/index'),
  SpecificationGroupList: () => import('@/views/GoodsManage/SpecificationGroup/list'),
  Brand: () => import('@/views/GoodsManage/Brand/index'),
  BrandList: () => import('@/views/GoodsManage/Brand/list'),
  ResourceData: () => import('@/views/Tool/Resource/index'),
  ResourceDataList: () => import('@/views/Tool/Resource/list'),
  Category: () => import('@/views/GoodsManage/Category/index'),
  CategoryList: () => import('@/views/GoodsManage/Category/list'),
  // 商品管理
  Product: () => import('@/views/GoodsManage/Product/index'),
  ProductList: () => import('@/views/GoodsManage/Product/list'),
  CreateProduct: () => import('@/views/GoodsManage/Product/create'),
  EditProduct: () => import('@/views/GoodsManage/Product/edit'),
  // 运费模板
  Freight: () => import('@/views/Freight/index'),
  FreightList: () => import('@/views/Freight/list'),
  CreateFreight: () => import('@/views/Freight/create'),
  EditFreight: () => import('@/views/Freight/edit'),
  // 运费模板
  Dhl: () => import('@/views/Dhl/index'),
  DhlList: () => import('@/views/Dhl/list'),
  // 订单管理
  Indent: () => import('@/views/Indent/index'),
  IndentList: () => import('@/views/Indent/list'),
  Shipment: () => import('@/views/Indent/shipment'),
  // 轮播
  Banner: () => import('@/views/Tool/Banner/index'),
  BannerList: () => import('@/views/Tool/Banner/list'),
  // 统计
  StatisticsVisit: () => import('@/views/statistics/visit'),
  StatisticsAgeAndSex: () => import('@/views/statistics/user'),
  StatisticsPay: () => import('@/views/statistics/pay'),
  // 栏目文章_s
  Column: () => import('@/views/tool/Article/Column/index'),
  ColumnList: () => import('@/views/tool/Article/Column/list'),
  CreateColumn: () => import('@/views/tool/Article/Column/create'),
  EditColumn: () => import('@/views/tool/Article/Column/edit'),
  Article: () => import('@/views/tool/Article/Article/index'),
  ArticleList: () => import('@/views/tool/Article/Article/list'),
  CreateArticle: () => import('@/views/tool/Article/Article/create'),
  EditArticle: () => import('@/views/tool/Article/Article/edit'),
  // 栏目文章_e
  // 评价_s
  Comment: () => import('@/views/tool/Comment/index'),
  CommentList: () => import('@/views/tool/Comment/list'),
  // 评价_e
  // 优惠券_s
  Coupon: () => import('@/views/tool/Coupon/index'),
  CouponList: () => import('@/views/tool/Coupon/list'),
  // 优惠券_e
  // 分销_s
  Distribution: () => import('@/views/tool/Distribution/index'),
  DistributionList: () => import('@/views/tool/Distribution/list'),
  // 分销_e
  // 插件列表
  // 插件
  PlugIn: () => import('@/views/Plugin/index'),
  PlugInList: () => import('@/views/Plugin/list')
}
const permission = {
  state: {
    routers: constantRouterMap,
    addRouters: []
  },
  mutations: {
    SET_ROUTERS: (state, routers) => {
      state.addRouters = routers
      state.routers = constantRouterMap.concat(routers)
    }
  },
  actions: {
    GenerateRoutes({ commit }, data) {
      return new Promise(resolve => {
        const { asyncRouterMap } = data
        const accessedRouters = generateAsyncRouter(componentsMap, asyncRouterMap)
        accessedRouters.push({ path: '*', redirect: '/404', hidden: true })
        commit('SET_ROUTERS', accessedRouters)
        // console.log(accessedRouters)
        resolve()
      })
    }
  }
}
function generateAsyncRouter(componentsMap, serverRouterMap) {
  serverRouterMap.forEach(function(item, index) {
    if (!item.redirect) {
      delete item.redirect
    }
    // console.log('item.component',item.component)
    item.component = item.component === 'Layout' ? Layout : componentsMap[item.component]
    if (item.children && item.children.length > 0) {
      generateAsyncRouter(componentsMap, item.children)
    }
  })
  return serverRouterMap
}
export default permission
