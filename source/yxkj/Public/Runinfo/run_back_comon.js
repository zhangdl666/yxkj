var runInfoIndex = {};
/**
 * 加载选中酒店运行信息
 * @constructor
 */
runInfoIndex.LoadingHotelInfo = function (hotel_id) {
    $.post('/Pc/RunInfo/screeningHotel', {hotel_id: hotel_id}, function (f) {

    }, 'html');
};

/**
 * 加载酒店房间
 * @param hotel_id
 * @constructor
 */
runInfoIndex.LoadingHotelRoom = function (hotel_id) {
    $.post('/Pc/RunInfo/getHotelRooms', {hotel_id: hotel_id}, function (f) {
        if (f.code == 0) {
            var strings = '';
            for (var i in f.data) {
                strings += '<button type="button" class="btn ' + f.data[i].status + ' rooms" value="' + f.data[i].equipment_sno
                    + '">' + f.data[i]['room_sno'] + '</button>'
            }
            $(".gezi-group").html(strings);
            but_show();
        }
    });
};

/**
 * 记载统计图表
 * 1 酒店 2 单独设备 3默认图表
 * @param hotel_id
 */
runInfoIndex.loadingHotelInfo = function (value, type, now_time, types) {
    if (type == 1) {
        $.post('/Pc/RunInfo/getHotelechartsData', {hotel_id: value, time: now_time, type: types}, function (f) {
            $('.sales').html(f);
        }, 'html');
    } else if (type == 3) {
        $.post('/Pc/RunInfo/defaultChart', {hotel_id: value}, function (f) {
            $('.sales').html(f);
        }, 'html');
    }
};


/**
 * 加载选中酒店运行信息和指定时间
 * @constructor
 */
runInfoIndex.LoadingHotelInfoTime = function (hotel_id, now_time, types) {
    // 加载酒店房间
    runInfoIndex.LoadingHotelRoom(hotel_id);
    // 加载信息表
    runInfoIndex.loadingHotelInfo(hotel_id, 1, now_time, types);
};

/**
 * 单独设备开始加载
 * @param hotel_id
 * @param now_time
 * @constructor
 */
runInfoIndex.LoadingRoomInfoTime = function (rooms, now_time) {
    $.post('/Pc/RunInfo/getDevicerhartsData', {rooms: rooms, time: now_time}, function (f) {
        $('.sales').html(f);
    }, 'html');
};

runInfoIndex.loadingRoomsDevices = function (type,value){
    if(type == 1){
        $.post('/Pc/RunInfo/getDeviceTimes', {type: 1 ,rooms: '',hotel_id:value}, function (f) {
            $('.devtime').html(f);
        }, 'html');
    }else{
        $.post('/Pc/RunInfo/getDeviceTimes', {type: 2 ,rooms: value}, function (f) {
            $('.devtime').html(f);
        }, 'html');
    }

};


/**
 * 加载数据
 */
$(function () {
    // 点击展示数据
    $("#kq_height").on('click', ".rooms", function () {
        $(".rooms").removeClass("pitch_on");
        $(this).addClass("pitch_on");
        var value = $(this).attr('value');
        var time = $('.editdate').val();
        // 加载房间显示信息
        runInfoIndex.loadingRoomsDevices(2,value);
        runInfoIndex.LoadingRoomInfoTime(value, time);
    });
    // 加载一个默认图表
    runInfoIndex.loadingHotelInfo(0, 3);
    $('select[name="hotels"]').on('change', function () {
        var index = layer.load(1, {
            shade: [0.5,'#fff'] //0.1透明度的白色背景
        });
        var now_time = $('input[name="now_time"]').val();
        runInfoIndex.loadingRoomsDevices(1,$(this).val());
        if (now_time && $(this).val()) {
            runInfoIndex.LoadingHotelInfoTime($(this).val(), now_time, 2);
            layer.close(index);
        } else {
            runInfoIndex.LoadingHotelInfo($(this).val());
            layer.close(index);
        }
    });
});