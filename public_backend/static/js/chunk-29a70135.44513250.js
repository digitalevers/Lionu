(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-29a70135"],{4341:function(t,e,i){},9406:function(t,e,i){"use strict";i.r(e);var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{staticClass:"app-container"},[i("div",{staticClass:"filter-container"},[i("div",{staticClass:"filter-title"},[t._v("应用管理")]),i("el-button",{attrs:{type:"primary",icon:"el-icon-plus"},on:{click:t.handleAdd}},[t._v("添加应用")])],1),i("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.listLoading,expression:"listLoading"}],attrs:{data:t.list,fit:"",stripe:"","highlight-current-row":""}},[i("el-table-column",{attrs:{align:"center",label:"应用ID",width:"105"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v(" "+t._s(e.row.id)+" ")]}}])}),i("el-table-column",{attrs:{label:"应用名称",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v(" "+t._s(e.row.app_name)+" ")]}}])}),i("el-table-column",{attrs:{label:"平台",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[1==e.row.app_os?i("div",{staticStyle:{"text-align":"left","margin-left":"40px"}},[i("svg-icon",{staticStyle:{width:"20px",height:"20px","vertical-align":"middle","margin-top":"-3px"},attrs:{"icon-class":"android"}}),t._v(" "+t._s(t._f("statusFilter")(e.row.app_os)))],1):2==e.row.app_os?i("div",{staticStyle:{"text-align":"left","margin-left":"40px"}},[i("svg-icon",{staticStyle:{width:"20px",height:"20px","vertical-align":"middle","margin-top":"-3px"},attrs:{"icon-class":"ios"}}),t._v(" "+t._s(t._f("statusFilter")(e.row.app_os)))],1):i("div",{staticStyle:{"text-align":"left","margin-left":"40px"}},[i("svg-icon",{staticStyle:{width:"20px",height:"20px","vertical-align":"middle","margin-top":"-3px"},attrs:{"icon-class":"app"}}),t._v(" "+t._s(t._f("statusFilter")(e.row.app_os)))],1)]}}])}),i("el-table-column",{attrs:{label:"包名",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v(" "+t._s(e.row.package_name)+" ")]}}])}),i("el-table-column",{attrs:{label:"创建时间",align:"center"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v(" "+t._s(e.row.add_time)+" ")]}}])},[t._v("> ")]),i("el-table-column",{attrs:{label:"状态",align:"center",width:"110px"},scopedSlots:t._u([{key:"default",fn:function(e){return[3==e.row.app_step?i("div",{staticClass:"list-type",on:{click:function(i){return t.handleOpen(e.row)}}},[i("div",{staticClass:"circle circle-finish"}),i("div",{staticClass:"circle-text"},[t._v("数据已接入")])]):i("div",{staticClass:"list-type",on:{click:function(i){return t.handleOpen(e.row)}}},[i("div",{staticClass:"circle"}),i("div",{staticClass:"circle-text"},[t._v("等待接入")])])]}}])},[t._v("> ")]),i("el-table-column",{attrs:{align:"center",label:"操作",width:"200px"},scopedSlots:t._u([{key:"default",fn:function(e){return[i("el-button",{attrs:{type:"primary",size:"mini"},on:{click:function(i){return t.handleDownload(e)}}},[t._v("下载SDK")]),i("el-button",{attrs:{type:"primary",plain:"",size:"mini"},on:{click:function(i){return t.handleDelete(e.row)}}},[t._v("删除")])]}}])})],1),i("pagination",{directives:[{name:"show",rawName:"v-show",value:t.total>0,expression:"total>0"}],attrs:{total:t.total,page:t.listQuery.page,limit:t.listQuery.pageSize},on:{"update:page":function(e){return t.$set(t.listQuery,"page",e)},"update:limit":function(e){return t.$set(t.listQuery,"pageSize",e)},pagination:t.getList}}),i("add-form",{ref:"dialogForm",on:{father:function(e){return t.getList()}}})],1)},a=[],l=(i("a9e3"),i("b562")),s=i("333d"),r=i("e3df"),o={components:{Pagination:s["a"],addForm:r["a"]},filters:{statusFilter:function(t){var e={1:"Android ",2:"iOS",3:"H5",4:"小程序",5:"Unity"};return e[t]}},data:function(){return{list:[],listLoading:!1,listQuery:{page:1,pageSize:10},total:10}},created:function(){this.getList()},methods:{getList:function(){var t=this;this.listLoading=!0,Object(l["a"])(this.listQuery).then((function(e){t.list=e.data.apps,t.total=Number(e.data.total),0===t.list.length&&t.total>0&&(t.listQuery.page=t.listQuery.page-1,t.getList()),t.listLoading=!1}))},handleDelete:function(t){var e=this;this.$confirm("确认删除该应用, 是否继续?","提示",{confirmButtonText:"确定",cancelButtonText:"取消",type:"warning"}).then((function(){var i={app_id:t.id};Object(l["c"])(i).then((function(t){200==t.code?(e.getList(),e.$notify({type:"success",message:"删除成功!",duration:2e3})):e.$message.error(t.msg)}))})).catch((function(){}))},handleDownload:function(t){window.open("/Sysin/downloadSDK?app_id="+t.row.id)},handleAdd:function(){this.$refs.dialogForm.handleOpen()},handleOpen:function(t){this.$refs.dialogForm.handleOpen(Number(t.app_step)+1,t.id,t.app_event)}}},c=o,u=(i("ec57"),i("2877")),d=Object(u["a"])(c,n,a,!1,null,"7bb7d6d0",null);e["default"]=d.exports},a9e3:function(t,e,i){"use strict";var n=i("83ab"),a=i("da84"),l=i("94ca"),s=i("6eeb"),r=i("5135"),o=i("c6b6"),c=i("7156"),u=i("c04e"),d=i("d039"),p=i("7c73"),f=i("241c").f,g=i("06cf").f,h=i("9bf2").f,_=i("58a8").trim,v="Number",m=a[v],w=m.prototype,y=o(p(w))==v,b=function(t){var e,i,n,a,l,s,r,o,c=u(t,!1);if("string"==typeof c&&c.length>2)if(c=_(c),e=c.charCodeAt(0),43===e||45===e){if(i=c.charCodeAt(2),88===i||120===i)return NaN}else if(48===e){switch(c.charCodeAt(1)){case 66:case 98:n=2,a=49;break;case 79:case 111:n=8,a=55;break;default:return+c}for(l=c.slice(2),s=l.length,r=0;r<s;r++)if(o=l.charCodeAt(r),o<48||o>a)return NaN;return parseInt(l,n)}return+c};if(l(v,!m(" 0o1")||!m("0b1")||m("+0x1"))){for(var x,S=function(t){var e=arguments.length<1?0:t,i=this;return i instanceof S&&(y?d((function(){w.valueOf.call(i)})):o(i)!=v)?c(new m(b(e)),i,S):b(e)},N=n?f(m):"MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,EPSILON,isFinite,isInteger,isNaN,isSafeInteger,MAX_SAFE_INTEGER,MIN_SAFE_INTEGER,parseFloat,parseInt,isInteger".split(","),I=0;N.length>I;I++)r(m,x=N[I])&&!r(S,x)&&h(S,x,g(m,x));S.prototype=w,w.constructor=S,s(a,v,S)}},ec57:function(t,e,i){"use strict";i("4341")}}]);