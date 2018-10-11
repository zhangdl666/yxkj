/* 手机号 */
// $(".tell").blur(function(){
// 	checkTell($.trim($(this).val()));
// })
//
// function checkTell(tell){
//     if(tell != "" && !(/^1[3-9]\d{9}$/.test(tell))){
//     	layer.msg("手机号码不正确,请重填");
//     	return false;
//     }
//     return true;
// }

/* 座机号 */
// $(".mobile").blur(function(){
// 	checkTell($.trim($(this).val()));
// })
// function checkMobile(mobile){
// 	if(mobile != "" && !/^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/.test(mobile)){
// 		layer.msg("固定电话不正确,请重填");
//     	return false;
// 	}
// 	return true;
// }
//
// /* 手机号或固定电话 */
// $(".tell_mobile").blur(function(){
// 	var val = $.trim($(this).val());
// 	if(val != "" && (!checkTell(val) || !checkMobile(val))){
// 		return false;
// 	}
// })
