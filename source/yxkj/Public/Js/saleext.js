$(function(){
    $("#rprice").blur(function(){
        var rprice = $('#rprice').val();
        var rprices = $('#rprices').val();
        //var mprice = (rprice-100)>0?(rprice)*0.01:0;
        if(isNaN(parseFloat(rprice))){
            layer.open({content:"请重新填写提现金额",time:2});
            return false;
        }else if((parseFloat(rprice))<=0){
            layer.open({content:"申请金额必须大于0元",time:2});
            return false;
        }else if((rprice) > (parseFloat(rprices))){
            layer.open({content:"超出可提现金额",time:2});
            return false;
        }else{
            // var url = 'Wx/SaleExt/in_sate_money';
            // var data = {};
            // data.rprice = rprice;
            // $.post(url,data,function () {
            //
            // })
            //var aaa='代扣个人所得税'+mprice+'元 ';
           // var price = rprice - mprice;
           //  var aaa='代扣个人所得税'+0+'元 ';
           //  var price = rprice;
        }
        // $('#price').text(price);
        if($('#mprice')){
            $('#mprice').remove();
        }
        /***********提现金额页面，添加该段，另起一行********/
        // $(this).parents("li").after('<li class="layoutlr form_item" style="padding-bottom: 0.1rem;color: rgb(102,102,102);text-align: right"><span class="le" id="mprice">'+aaa+'</span></div>');
        // $(this).parents("li").css("border-bottom","0")
    });


});

