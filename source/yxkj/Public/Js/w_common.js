$('.contenteditable').each(function() {
	if($(this).text().length) {
		$(this).removeClass('on');
	}
});
$('.main').on('input', '.contenteditable', function() {
	if($(this).text().length) {
		$(this).removeClass('on');
	}
});
$('.main').on('focus', '.contenteditable', function() {
		$(this).removeClass('on');
});
$('.main').on('blur', '.contenteditable', function() {
	if(!$(this).text().length) {
		$(this).addClass('on');
	}
});

/*单选-多选
----------------------------------------------------------------------------------------------------*/
	$('.edit_container input').each(function() {
		console.log($(this).val())
		if($(this).val().length) {
			$(this).closest('.edit_container').find('.cleartext').show();
		}
	});
	$('.edit_container').on('input', 'input', function() {
		if($(this).val().length) {
			$(this).closest('.edit_container').find('.cleartext').show();
		} else {
			$(this).closest('.edit_container').find('.cleartext').hide();
		}
	});
	$('.edit_container').on('click', '.cleartext', function() {
		$(this).closest('.edit_container').find('input').val('');
	});

/*下拉
----------------------------------------------------------------------------------------------------*/
	$('.main').on('click', '.dropdown', function() {
		var _dataRelation = $(this).attr('data-relation');
		var _selectedLayer = $('.selected_layer[data-relation="'+ _dataRelation +'"]');
	 	//页面层
		var _layer = layer.open({
			type: 1,
			content: _selectedLayer.html(),
			anim: 'up',
			style: 'position:fixed; bottom:0; left:0; width: 100%; height: 200px; padding:10px 0; border:none;',
			success: function(layero, index){
				console.log(layero, index);
			}
		});
		$('.options_container').on('click', 'li', function() {
			var _dataRelation = $(this).closest('.options_container').attr('data-relation');
			var _selectedLayer = $('.dropdown[data-relation="'+ _dataRelation +'"]');
			var _selected = $(_selectedLayer).find('.selected');
			$(_selectedLayer).find(".selectedval").val($(this).find('span').attr('data'));
			_selected.html($(this).html());
			layer.close(_layer);
		});
	});

/*单选-多选
----------------------------------------------------------------------------------------------------*/
	$('.main').on('click', 'input[type="radio"]', function() {
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


	$('.main').on('click', 'input[type="checkbox"]', function() {
		// console.log($('input[type="checkbox"]').prop('checked'));
		// console.log($(this).prop('checked'));
		var _checkboxContainer = $(this).closest('.checkbox-container');
		var _name = $(this).attr('name');
		if($(this).prop('checked')) {
			_checkboxContainer.attr('checked', true);
			$(this).prop('checked', true);
			// 循环加载数据
            var room_nums = 0;
            $(".kefangleixing").each(function () {
                if ($(this).attr('checked') == 'checked') {
                    var num = $(this).closest(".checkbox-containers").find('.checkbox-container-input input').val();
                    if (parseInt(num)>=0) {
                        room_nums += parseInt(num);
                    }
                }else{
                	$(this).closest(".checkbox-containers").find('.checkbox-container-input input').val("");
                }
            });
            $("#room_numsss").val(room_nums);
		} else {
			_checkboxContainer.removeAttr('checked');
			$(this).prop('checked',false);
			// 循环加载数据
            var room_nums = 0;
            $(".kefangleixing").each(function () {
                if ($(this).attr('checked') == 'checked') {
                    var num = $(this).closest(".checkbox-containers").find('.checkbox-container-input input').val();
                    if (parseInt(num)>=0) {
                        room_nums += parseInt(num);
                    }
                }else{
                	$(this).closest(".checkbox-containers").find('.checkbox-container-input input').val("");
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
			} else {;
				
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

/*进度条
----------------------------------------------------------------------------------------------------*/
$('.progress-container').find('.progress').each(function() {
	$(this).width($(this).attr('data-width'));
});
