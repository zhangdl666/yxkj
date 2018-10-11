function but_show() {
    console.log("进入方法1");
    var kg_btn = $("#kq_height");
    var btn_icon = $(".btn_kq_icon");
    var btn_length = kg_btn.children(".btn").length;
    var btn_num = parseInt(kg_btn.width() / 110);
    if (btn_length > btn_num) {
        btn_icon.addClass("btn_block");
        btn_icon.removeClass("btn_none");
    } else {
        btn_icon.addClass("btn_none");
        btn_icon.removeClass("btn_block");
    }



}

$(document).ready(function () {
    but_show();
    /***********浏览器窗口宽度发生变化时执行************/
    $(window).resize(function () {
        but_show();
    });

    $("#gj_list_label").click(function(){
        $(".gj_panel-heading label").removeClass("gj_label_click");
        $(this).addClass("gj_label_click");
        $(".gj_list").show();
        $(".gj_history_list").hide();
    });
    $("#ls_list_label").click(function(){
        $(".gj_panel-heading label").removeClass("gj_label_click");
        $(this).addClass("gj_label_click");
        $(".gj_list").hide();
        $(".gj_history_list").show();
    });

    /********房间下拉图标和向上关闭图标切换********/
    $(function () {
        var on_num = 0;
        $(".btn_kq_icon").click(function () {
            var this_icon = $(".btn_kq_icon");
            if (on_num == 0) {
                this_icon.removeClass("btn_icon_bottom");
                $("#kq_height").removeClass("pc_kq_height");
                this_icon.addClass("btn_icon_top");
                on_num = 1;
            } else if (on_num == 1) {
                $("#kq_height").addClass("pc_kq_height");
                this_icon.addClass("btn_icon_bottom");
                this_icon.removeClass("btn_icon_top");
                on_num = 0;
            }
        })
    });
});

