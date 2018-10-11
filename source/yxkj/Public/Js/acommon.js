//列表中复选框的特效
//>>通过全选的状态控制下面的状态
$('.all').change(function(){
    $('.id').prop('checked',$(this).prop('checked'));
});
//>>在所有的class=id的复选框上加上change事件
$('.id').change(function(){
    $('.all').prop('checked',$('.id:not(:checked)').length==0);
});

//>>1.向带有class='ajax-get'的标签上加上点击事件,事件处理函数发送ajax的get请求
$('.ajax-oper-get').click(function () {
    var obj = $(this);
    layer.confirm("您确定要进行此项操作吗?",function(){
        var url = obj.attr('href'); //获取标签上面的href的url地址,该地址就是我们要发送的请求地址
        $.get(url, function (data) {
            //>>2.使用layer提示
            showLayer(data);
        });
    });
    return false;//取消默认操作
});

//>>1.向带有class='ajax-get'的标签上加上点击事件,事件处理函数发送ajax的get请求
$("#article").on("click",'.ajax-get',function () {
    var url = $(this).attr('href'); //获取标签上面的href的url地址,该地址就是我们要发送的请求地址
    $.get(url, function (data) {
        //>>2.使用layer提示
        showLayer(data);
    });
    return false;//取消默认操作
});


//>>2.页面加载完之后找到 class='ajax-post' 的标签加上点击事件,并且发送ajax的post请求
$('.ajax-post').click(function () {
    var form = $(this).closest('form');//如果找到form,说明提交的是表单
    var url = form.length==0?$(this).attr('url'):form.attr('action');  //找到form上的action属性作为url
    var param = form.length==0?$('.id').serialize():form.serialize();  //获取form上的所有请求参数
    //>>2.1发送post请求
    layer.load(2);
    $.post(url, param, function (data) {
        //>>2.使用layer提示
        showLayer(data);
    });
    return false;//取消默认提交
});

//全选/全不选
$("#items").click(function(){
    if(this.checked){
        $("input[name='item']").prop("checked","true");
    }else{
       $("input[name='item']").removeAttr("checked"); 
    }
});

/* 返回上一页 */
$(".back").click(function(){
    window.history.back();
})

//删除用户
$(".delbtn").click(function(){
    var check = $("input[name='item']:checked");
    //是否已经有复选框被选中
    if(check.length==0){
        layer.msg("请选择操作项！", {
            offset: 0,
            icon: 0,
            time: 1500   //1.5秒钟之后执行下面的函数
        });
        return false;
    }

    var obj = $(this);
    layer.confirm("您确定要进行此操作吗？",function(){
        var text_value= [];
        check.each(function() {
            text_value.push(this.value);
        });
        //用ajax的Post方式提交所先的数据
        var url = obj.parent().attr('href');
        $.post(url,{ids:text_value},function(info){
            showLayer(info);
        });
    });
    return false;
});

/**
 * 根据data中的值弹出提示框
 * @param data
 */
 function showLayer(data){
    if(typeof data != "object"){
        var data = eval("("+data+")");
    }
    layer.closeAll('loading');
     layer.msg(data.info, {
         icon: data.status ? 1 : 0,
         offset: 0,
         //shift: 6,
         time: 1000   //1秒钟之后执行下面的函数
     }, function () {
         if (data.status) {  //成功的时候跳转到指定的url地址
             //loadurl(data.data);
             location.href = data.data;
         }
     });
 }

//$("#article").on("click",".load-page",function(){
//  loadurl($(this).attr('url-href'));
//})


/********** 该方法在IE9里面报错，导致头部各种点击功能不能实现，且该方法没有使用，所以注释 ***** 2017-12-05 *******************/
//function loadurl(url){
//     $.ajax({
//        type: "GET",
//        url: url,
//        async: true,
//        complete(XHR, TS) {
//            if(XHR.responseText == 'noAccess'){
//                layer.msg('没有操作权限');
//            }else{
//                $("#article").html(XHR.responseText);
//                var event = document.createEvent('HTMLEvents');
//                event.initEvent("load", true, true);
//                window.dispatchEvent(event);
//                //ie
//                if (document.createEventObject) {
//                    var event = document.createEventObject();
//                    window.fireEvent('onload', event);
//                }
//            }
//        }
//    })
//}

/**
 * 页面直接跳转
 */
function now_href(url){
  window.top.right.location.href = url;
}

/* 退出登录 */
$("#loginout").click(function(){
  $.get("/Pc/Login/login_out",function(data){
    var data=eval('(' + data + ')');
    if(data.status == 1){
      layer.msg(data.info,{time:1000, offset: 0, icon:1});
      window.top.location.href = data.data;
      //parent.location.href=data.data;
      return;
    }
  })
})

/*PC图片点击----------------------------------------------------*/
// $('body').on('click','.img-rounded',function(){
//   var $img = $(this).clone();
//   $img.css({
//       'height': '50%',
//       'width': '50%',
//       'margin-top':'20%'
//   })
//   var $div = $('<div></div>');
//   $div.css({
//     'position': 'fixed',
//     'z-index': '999999999',
//     'width': '100%',
//     'height': '100%',
//     'background': 'rgba(0,0,0,.6)',
//     'top': '0',
//     'left': '0',
//     'text-align': 'center'
//   });
//   $img.appendTo($div);
//   $div.appendTo($('body'));
//   $div.click(function(){
//     $(this).remove();
//   })
// });
/*$("body").on('click', '.img-rounded', function () {
    // $("embed").css('visibility', 'hidden');
    // layer.photos({
    //     photos: {
    //         "title": "", //相册标题
    //         "id": "aimg", //相册id
    //         "start": 0, //初始显示的图片序号，默认0
    //         "data": [   //相册包含的图片，数组格式
    //             {
    //                 // "alt": "图片名",
    //                 "pid": 666, //图片id
    //                 "src": $(this).attr("src"), //原图地址
    //                 "thumb": "" //缩略图地址
    //             }
    //         ]
    //     },
    //     anim: 5, //0-6的选择，指定弹出图片动画类型，默认随机
    // });
    // $(".layui-layer-shade").click(function() {
    //     $("embed").css('visibility', 'visible');
    // });
    var html = "<img src='" + $(this).attr('src') + "' style='min-width:90% '>";
    layer.open({
        type: 1,
        title: false,
        closeBtn: 1,
        skin: 'layui-layer-nobg', //没有背景色
        shadeClose: true,
        scrollbar: false,
        content: html
    });
    
    var html = "<div class='modal bs-example-modal-lg jiudiantupian_modal' id='myModal' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' style='display:block;text-align:center;width:100%;height:100%;background-color:#ccc;'>\
        <div class='' role='document' style='overflow:scroll;height:100%;width:100%;background-color:#FFF;'>\
            <div class=''>\
                <div class='modal-header'>\
                    <button onclick='close_open(this)' type='button' class='close' style='background-color:#ff9600;color:#FFF;margin-left:-35px;margin-bottom:-35px;' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>\
                </div>\
                <div class='' style='padding:15px;'>\
                    <img class='' src='"+$(this).attr('src')+"' style='max-height:85%;max-width:85%;'>\
                </div>\
            </div>\
        </div>\
    </div>";
    $("body").append(html);
});*/

function close_open(obj){
    $(obj).closest("#myModal").remove();
}