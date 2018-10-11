//弹框
$(function () {
    $('.fangjianflow').click(function (event) {
        var oper = parseInt($('input[name="Opener"]').val());
        if(oper ==0){
            $('#flow').slideUp();
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
                content:'请先开启设备',
                time: 2000,
            });
            return false;
        }
        //取消事件冒泡
        event.stopPropagation();
        //对应内容
        $('#flow').slideDown();
        timeUserFun(0.1);
    });
    $('body').bind('click', function (e) {
        var o = e.target;
        if($(o).closest('.fangjianxinxitanchuang_temp').length==0)//不是特定区域
            $('#flow').slideUp();
    });
    $('body').bind('touchmove', function (e) {
        var o = e.target;
        if($(o).closest('.fangjianxinxitanchuang_temp').length==0)//不是特定区域
            $('#flow').slideUp();
    });

});

//风速数据更换
$('#speed').bind('click',(function () {
        var num =parseInt( $('input[name="speed"]').val());
        if(num < 3 ){
            num++;
        }else{
           num = 0;
        }
        $('input[name="speed"]').val(num);
        if(0< num && num < 4){
           var in_num = in_get_num(parseInt(num));
            var values =  '风速'+in_num;
        }else{
            var values = '风机关闭';
        }
        speeding(values);


    }
));
function in_get_num(num) {
    if(num == 1){
        num = 'Ⅰ';
    }else if(num == 2){
        num = 'Ⅱ';
    }else if(num == 3){
        num = 'Ⅲ';
    }else{
        num = '关闭';
    }
    return num;
}
 function speeding(values) {
    var val = parseInt( $('input[name="Opener"]').val());
     var num =parseInt( $('input[name="speed"]').val());
    if(val ==0){
        layer.closeAll();
        layer.open({
            type: 1,
            area: ['auto', '6vh'],
            shade: false,
            title: false,
            skin: 'yourclass',
            content:'请先开启设备',
            time: 2000,
        });
        return false;
    }
    var url =  "/Traveller/CheckinInfo/callback";
    var data ={
             'type':1,
            "value": parseInt( $('input[name="speed"]').val()),
        }
    $.post(url, data, function (data) {
         if(data.code == 405){
             layer.closeAll();
             layer.open({
                 type: 1,
                 area: ['auto', '6vh'],
                 shade: false,
                 title: false,
                 skin: 'yourclass',
                 //content:'操作频繁，请稍后再试',
                 content:'两次操作的时间差大于1秒',
                 time: 2000,
             });
            return false;
        }else if(data.code == 200){
             layer.closeAll();
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
                content:values,
                time: 2000,
            });
             if(num == 0){
                 $('#speed img').attr("src","/Public/Images/speed001.png");
                 $('.speedings').removeClass('n2_click');
             }else{
                 $('#speed img').attr("src","/Public/Images/speed002.png");
                 $('.speedings').addClass('n2_click');
             }
             $('.speedings').text(values);
        }else{
             layer.closeAll();
             layer.open({
                 type: 1,
                 area: ['auto', '6vh'],
                 shade: false,
                 title: false,
                 skin: 'yourclass',
                 content:'操作失败',
                 time: 2000,
             });
             return false;
         }

    },'json')
 }
var equipmentTime = {};
equipmentTime.equipmentTime = function () {
    var val = parseInt( $('input[name="Opener"]').val());
    var open =  $('input[name="stime"]').val();
    var off =$('input[name="etime"]').val();
    if(off < open){
        layer.open({
            type: 1,
            area: ['auto', '6vh'],
            shade: false,
            title: false,
            skin: 'yourclass',
            content:'关闭时间不能低于开启时间',
            time: 2000,
        });
        return false;
    }
    var url ="/Traveller/CheckinInfo/callback";
    var data ={
        'type':3,
        'timeopen': $('input[name="stime"]').val(),
        "timeoff": $('input[name="etime"]').val()
    }
    $.post(url, data, function (data) {
        if(data.code = 200){
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
                content:'设置成功',
                time: 2000,
            });
            $('#time').slideUp();
        }else{
            layer.msg(data.message, {
                time: 2000 //2s后自动关闭
                ,yes: function(index){
                }
            });
        }
    },'json')
}
$('.temp_right').bind('click',(function () {
    var val = parseInt($('input[name="temp"]').val());
    // var ac_type = parseInt($('input[name="ac_type"]').val());
    // if(ac_type == 0){
    //     layer.msg('当前为制冷状态', {time: 2000});
    //     return false;
    // }
    if(val < 30){
        val ++;
    }else{
        layer.open({
            type: 1,
            area: ['auto', '6vh'],
            shade: false,
            title: false,
            skin: 'yourclass',
            content:'温度最高为30℃',
            time: 2000,
        });
        return false;
    }
    tempupdate(val);
}));
$('.temp_left').bind('click',(function () {
    var val = parseInt($('input[name="temp"]').val());
    // var ac_type = parseInt($('input[name="ac_type"]').val());
    // if(ac_type == 1){
    //     layer.msg('当前为制热状态', {time: 2000});
    //     return false;
    // }
    if(val > -30){
        val --;
    }else{
        layer.closeAll();
        layer.open({
            type: 1,
            area: ['auto', '6vh'],
            shade: false,
            title: false,
            skin: 'yourclass',
            content:'温度最低为-30℃',
            time: 2000,
        });
        return false;
    }
    tempupdate(val);
}));
function tempupdate(val) {
    var oper = parseInt($('input[name="Opener"]').val());
    if(oper ==0){
        layer.closeAll();
        layer.open({
            type: 1,
            area: ['auto', '6vh'],
            shade: false,
            title: false,
            skin: 'yourclass',
            content:'请先开启设备',
            time: 2000,
        });
        $('#flow').slideUp();
        return false;
    }
    parseInt($('input[name="temp"]').val(val));
    var in_val = val+'.0℃';
    $('.temp').html(in_val);
    var url =  "/Traveller/CheckinInfo/callback";
    var data ={
        'type':4,
        'ac_temp':val
    }
    $.post(url, data, function (data) {
        if(data.code == 405){
            layer.closeAll();
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
               // content:'操作频繁，请稍后再试',
                content:'两次操作的时间差大于1秒',
                time: 2000,
            });

        }else if(data.code == 200){
            // layer.msg('设置成功', {
            //     time: 2000,
            //
            // });

        }else{
            layer.closeAll();
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
                content:'操作失败',
                time: 2000,
            });
        }

    },'json')
}
var Temp = {};
Temp.tempdown = function () {
  temp(0);
}
Temp.tempup = function () {
    temp(1);
}
function temp(type) {
    $('.fangjianflow').find("img").attr("src","/Public/Images/temperature002.png");
    $('.fangjianflow .n2').addClass('n2_click');
    if(type ==1){
        // layer.msg('开始制热', {
        //     time: 2000,
        // });
        $('.fx_btn_right').addClass('fx_chick');
        $('.fx_btn_left').removeClass('fx_chick');
    }else{
        // layer.msg('开始制冷', {
        //     time: 2000,
        // });
        $('.fx_btn_right').removeClass('fx_chick');
        $('.fx_btn_left').addClass('fx_chick');
    }
    var val = parseInt($('input[name="temp"]').val());
    var url =  "/Traveller/CheckinInfo/callback";
    var data ={
            'type':4,
            'ac_type':type,
            'ac_temp':val,
    }
    $.post(url, data, function (data) {
        if(data.code == 405){
            layer.closeAll();
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
               // content:'操作频繁，请稍后再试',
                content:'两次操作的时间差大于1秒',
                time: 2000,
            });

        }else if(data.code == 200){

        }else{
            layer.open({
                type: 1,
                area: ['auto', '6vh'],
                shade: false,
                title: false,
                skin: 'yourclass',
                content:'操作失败',
                time: 2000,
            });
        }

    },'json')
}
//设备开关数据更换
$(function () {
    $('.Opener').click(function (event) {
        var val = $('input[name="Opener"]').val();
        if(val == 0){
            val = 1;
        }else{
            val = 0 ;
        }
        $('input[name="Opener"]').val(val);
        var url = "/Traveller/CheckinInfo/callback";
        var data ={
            'type':2,
            "value":  $('input[name="Opener"]').val(),
        };
        var number =parseInt( $('input[name="Opener"]').val());
        $.post(url, data, function (data) {
           if(data.code == 200){
               if(number == 1){
                   layer.closeAll();
                   layer.open({
                       type: 1,
                       area: ['auto', '6vh'],
                       shade: false,
                       title: false,
                       skin: 'yourclass',
                       content:'开启成功',
                       time: 2000,
                   });
                   $('.Opener img').attr("src","/Public/Images/switch002.png");
                   $('.Opener .n2').addClass('n2_click');
               }else{
                   layer.closeAll();
                   layer.open({
                       type: 1,
                       area: ['auto', '6vh'],
                       shade: false,
                       title: false,
                       skin: 'yourclass',
                       content:'关闭成功',
                       time: 2000,
                   });
                   $('.Opener img').attr("src","/Public/Images/switch001.png");
                   $('.Opener .n2').removeClass('n2_click');
                   $('#speed img').attr("src","/Public/Images/speed001.png");
                   $('.speedings').removeClass('n2_click');
                   $('.speedings').text('风机关闭');
                   $('input[name="speed"]').val(0);
               }
           }else if(data.code == 405){
               layer.closeAll();
               layer.open({
                   type: 1,
                   area: ['auto', '6vh'],
                   shade: false,
                   title: false,
                   skin: 'yourclass',
                   //content:'操作频繁，请稍后再试',
                   content:'两次操作的时间差大于1秒',
                   time: 2000,
               });
           }else{
               layer.open({
                   type: 1,
                   area: ['auto', '6vh'],
                   shade: false,
                   title: false,
                   skin: 'yourclass',
                   content:'开启失败 ',
                   time: 2000,
               });
           }
        },'json')
    });
})
//弹窗定时关闭
function number_show() {
    $('#time').slideUp();
}
//设备定时
$(function () {
    $('.Devicetime').click(function (event) {
        //取消事件冒泡
        event.stopPropagation();
        $('#time').slideDown();
        if($('#time').is(':visible') == true){
            timeUserFun(0.1)
        }
    });

    //点击空白处隐藏弹出层，下面为滑动消失效果和淡出消失效果。
    $('.fx_m_flow').bind('click', function (e) {
        var o = e.target;
        if($(o).closest('.fangjianxinxitanchuang').length==0)//不是特定区域
            $('#time').slideUp();
    });
    $('.fx_m_flow').bind('touchmove', function (e) {
        var o = e.target;
        if($(o).closest('.fangjianxinxitanchuang').length==0)//不是特定区域
            $('#time').slideUp();
    });
})

//实时更新数据
setInterval("show()",30*1000);
function show()
{
    var url ="/Traveller/CheckinInfo/calltime";
    $.ajax({
        type:'GET',
        async:false,
        url:url,
        dataType:'json',
        success:function (data) {
            if(data){
                $('.in_pm_num').html(data.in_pm);
                color(data.in_pm);
                $('.in_air').html(data.in_air);
                $('#in_temperature').html(data.in_temperature);
                $('.temp_in').html(data.temp_in);
                $('.in_humidity').html(data.in_humidity);
                $('.out_pm').html(data.out_pm);
                $('.out_air').html(data.out_air);
                 var speeder = in_get_num(data.speed);
                if(data.speed !==0){
                    $('#speed img').attr("src","/Public/Images/speed002.png");
                    $('.speedings').addClass('n2_click');
                    $('.speedings').text('风速'+speeder);
                    $('input[name="speed"]').val(0);
                }else{
                    $('#speed img').attr("src","/Public/Images/speed001.png");
                    $('.speedings').removeClass('n2_click');
                    $('.speedings').text('风机关闭');
                    $('input[name="speed"]').val(0);
                };
                if(data.oper == 1){
                    $('input[name="Opener"]').val(data.oper);
                    $('.Opener img').attr("src","/Public/Images/switch002.png");
                    $('.Opener .n2').addClass('n2_click');
                }else{
                    $('input[name="Opener"]').val(data.oper);
                    $('.Opener img').attr("src","/Public/Images/switch001.png");
                    $('.Opener .n2').removeClass('n2_click');
                    $('#speed img').attr("src","/Public/Images/speed001.png");
                    $('.speedings').removeClass('n2_click');
                    $('.speedings').text('风机关闭');
                    $('input[name="speed"]').val(0);
                }
            }else{
            }
        }
    })


}
function color(num) {
    if(250<num ){
        $('.num').attr("style","color:#53039a");
        $('.num>div').eq(4).css("display","block").siblings("div").css('display','none');
    }else if(35<num && num<=75){
        $('.num').attr("style","color:#fff700");
        $('.num>div').eq(0).css("display","block").siblings("div").css('display','none');
    }else if(75<num && num<=115){
        $('.num').attr("style","color:#fa620c");
        $('.num>div').eq(1).css("display","block").siblings("div").css('display','none');
    }
    else if(115<num && num<=150){
        $('.num').attr("style","color:#ff0600");
        $('.num>div').eq(2).css("display","block").siblings("div").css('display','none');
    }else if(150<num && num<=250){
        $('.num').attr("style","color:#a501ee");
        $('.num>div').eq(3).css("display","block").siblings("div").css('display','none');
    }else{
        $('.num').attr("style","color:#41ff01");
        $('.num>div').eq(5).css("display","block").siblings("div").css('display','none');


    }

}
function timeUserFun(time) {
    var time = time || 2;
    var userTime = time*60;
    var objTime = {
        init:0,
        time:function(){
            objTime.init += 1;
            if(objTime.init == userTime){
                // 用户到达未操作事件 做一些处理
                if($(".gearDate").css("display")!=='block'){
                  // $('.gearDate').hide();
                   $('#flow').slideUp();
                   $('#time').slideUp();
               };
                // $('#flow').slideUp();
                // $('#time').slideUp(); // 用户到达未操作事件 做一些处理
            }
        },
        eventFun:function(){
            clearInterval(testUser);
            objTime.init = 0;
            testUser = setInterval(objTime.time,1000);
        }
    }

    var testUser = setInterval(objTime.time,1000);

    var body = document.querySelector('html');
    body.addEventListener("click",objTime.eventFun);
    body.addEventListener("keydown",objTime.eventFun);
    body.addEventListener("touch",objTime.eventFun);
    body.addEventListener("mousemove",objTime.eventFun);
    body.addEventListener("mousewheel",objTime.eventFun);
}
function closeMobileCalendar(e) {
    e.preventDefault();
    var evt;
    try {
        evt = new CustomEvent('input');
    } catch (e) {
        //兼容旧浏览器(注意：该方法已从最新的web标准中删除)
        evt = document.createEvent('Event');
        evt.initEvent('input', true, true);
    }
    _self.trigger.dispatchEvent(evt);
    document.body.removeChild(_self.gearDate);
    _self.gearDate=null;
}
