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
    confirm("您确定要进行此项操作吗?",function(){
        var url = obj.attr('href'); //获取标签上面的href的url地址,该地址就是我们要发送的请求地址
        $.get(url, function (data) {
            //>>2.使用layer提示
            showLayer(data);
        });
    });
    return false;//取消默认操作
});

//>>1.向带有class='ajax-get'的标签上加上点击事件,事件处理函数发送ajax的get请求
$('.ajax-get').click(function () {
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

/* 返回指定页 */
$(".backs").click(function(){
    now_href($(this).attr("data-url"));
})

//删除用户
$(".delbtn").click(function(){
    var check = $("input[name='item']:checked");
    //是否已经有复选框被选中
    if(check.length==0){
        alert('请选择操作项！')
        return false;
    }

    var obj = $(this);
    confirm("您确定要进行此操作吗？",function(){
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
    layer.open({content:data.info,time:800});
    if (data.status) {  //成功的时候跳转到指定的url地址
         location.href = data.data;
     }
 }

/**
 * 页面直接跳转
 */
function now_href(url){
  window.location.href = url;
}