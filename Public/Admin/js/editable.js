function  Editable() {
	this.url;
	this.unit = function (url){
		this.url=url;
	}

	//确认编辑  传输数据value和name 到url
	this.confirm_edit = function (obj){
		var input =	$(obj).prev();
		var text = input.val();
		var name = input.attr('name');
		var style = input.attr('class');
		$.ajax({
			url: this.url,
			type: 'post',
			dataType: 'json',
			data: {
				value: text,
				name: name
			},
		})
		.done(function( obj ) {
			if(obj.code=='0001'){
				console.log('fail');
				var child = '<span onclick="editable.edit(this)" class="'+style+'" name="'+ name +'" >'+ text +'</span>';
				$(obj).parent().append(child);
				$(obj).remove();	
				input.remove();				
			}

		})
		.fail(function() {
			console.log('fail');
			var child = '<span onclick="editable.edit(this)" class="'+style+'" name="'+ name +'" >'+ text +'</span>';
			$(obj).parent().append(child);
			$(obj).remove();	
			input.remove();		
		})

	},
	//再次编辑
	this.edit = function (obj){
		var text = $(obj).html();
		var name = $(obj).attr('name');
		var style = $(obj).attr('class');
		var child = '<input name="'+ name +'" class="'+style+'" value="'+ text +'" />';
		var child2 = '<span  class="confirm" onclick="editable.confirm_edit(this)" >+<span>';
		$(obj).parent().append(child);
		$(obj).parent().append(child2);
		$(obj).remove();
	}
}
var editable = new Editable();

(function ($) {
	$.fn.editable = function(url){		
		//开启编辑
		$(this).click(function(event) {
			var text = $(this).html();
			var name = $(this).attr('name');
			var style = $(this).attr('class');
			var child = '<input name="'+ name +'" class="'+style+'" value="'+ text +'" />';
			var child2 = '<span class="confirm" onclick="editable.confirm_edit(this)" >+<span>';
			$(this).parent().append(child);
			$(this).parent().append(child2);
			$(this).remove();					
		});
		if (url==null) {
			url = $(this).attr('url');
		};

		editable.unit(url);

	}	
})(jQuery);

//调用方法
// $('.editable li span').editable('Home/Index/index');
