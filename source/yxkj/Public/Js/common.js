$('.skin_peeler').click(function() {
  $('[stylesheet_skin]').attr('disabled', true);
  $('[stylesheet_skin="'+ $(this).attr('rel') +'"]').removeAttr('disabled');
});

$('.dropdown-menu a').click(function() {
	$(this).closest('.btn-group').find('.text').html($(this).html());
});
$('.aside-nav').on('click', 'a', function() {
	$(this).closest('.aside-nav').find('a').removeClass('on');
	$(this).addClass('on');
});


/*二级菜单
----------------------------------------------------------------------------------------------------*/
$('.subnav').on('click', '[rel="link"]', function() {
	$('.subnav').find('li').removeClass('on');
	$(this).closest('li').addClass('on');
	$('.cur_title').html($(this).html());
});


/*单选-多选
----------------------------------------------------------------------------------------------------*/
	$('body').on('click', 'input[type="radio"]', function() {
		console.log($('input[type="radio"]').prop('checked'));
		console.log($(this).prop('checked'));
		var _name = $(this).attr('name');
		$('[name="'+ _name +'"]').closest('.checkbox-container').removeAttr('checked');
		if($(this).prop('checked')) {
			$(this).closest('.checkbox-container').attr('checked', true);
		} else {
			$(this).closest('.checkbox-container').removeAttr('checked');
		}
	});


	$('body').on('click', 'input[type="checkbox"]', function() {
		// console.log($('input[type="checkbox"]').prop('checked'));
		// console.log($(this).prop('checked'));
		var _checkboxContainer = $(this).closest('.checkbox-container');
		var _name = $(this).attr('name');
		if($(this).prop('checked')) {
			_checkboxContainer.attr("checked",true);
			$(this).prop("checked",true);
            // 循环加载数据
            var room_nums = 0;
            $(".kefangleixing").each(function () {
                if ($(this).attr('checked') == 'checked') {
                    var num = $(this).find('.checkbox_txt input').val();
                    if (parseInt(num)>=0) {
                        room_nums += parseInt(num);
                    }
                }
            });
            $("#room_numsss").val(room_nums);
		} else {
			_checkboxContainer.removeAttr("checked");
			$(this).prop("checked",false);
            $(this).siblings('.checkbox_txt').find('input').val('');
            // 循环加载数据
            var room_nums = 0;
            $(".kefangleixing").each(function () {
                if ($(this).attr('checked') == 'checked') {
                    var num = $(this).find('.checkbox_txt input').val();
                    if (parseInt(num)>=0) {
                        room_nums += parseInt(num);
                    }
                }
            });
            $("#room_numsss").val(room_nums);
		}
		if(_checkboxContainer.is('[data-relationName]')) { // 全选框
			var _relationName = _checkboxContainer.attr('data-relationName');
			var _relationInputs = $('input[name='+ _relationName +']');
			var _relationContainer = _relationInputs.closest('.checkbox-container');
			if($(this).prop('checked')) {
				_relationInputs.prop('checked', true);
				_relationContainer.attr('checked', true);
			} else {				
				_relationInputs.prop('checked', false);
				_relationContainer.removeAttr('checked');
			}
		}
		var _allCheckbox = $('.checkbox-container[data-relationName='+ _name +']');
		if(_allCheckbox.length) {
			var _allInputs = $('input[name='+ _name +']');
			if(_allInputs.closest('.checkbox-container[checked]').length == _allInputs.length) { // 全选
				_allCheckbox.prop('checked', true);
				_allCheckbox.attr('checked', true);
			} else {				
				_allCheckbox.prop('checked', false);
				_allCheckbox.removeAttr('checked');
			}
		}
	});

/*
----------------------------------------------------------------------------------------------------*/
$('.screening-btns').on('click', '.btn', function() {
	$(this).closest('.screening-btns').find('.btn').removeClass('on');
	$(this).addClass('on');
});
$('.ssqy').on('click', '.show-more', function() {
	$(this).closest('.ssqy').toggleClass('on');
	$(this).toggleClass('tog')
	// $(this).addClass('on');
});



/*进度条
----------------------------------------------------------------------------------------------------*/
$('.progress-container').find('.progress').each(function() {
	$(this).width($(this).attr('data-width'));
});
