(function ($) {
	$.fn.gentcpdf = function (spec) {
		/** set default option */
		var options = $.extend(spec);
		var baseUrl = options.src.substring(0, options.src.lastIndexOf('/'));
		$(this).click(function(){
			var container =	"<form id='report_form' method='post' action='"+options.src+"' target='iframe'>"+
						"<input type='hidden' id='dom' name='dom' value='Could not get content...'/>"+
						"<input type='hidden' id='title' name='title' value='report' />"+
						"<input type='hidden' id='orentation' name='orentation' value='"+options.orentation+"' />"+
						"<input type='hidden' id='size' name='size' value='"+options.size+"' />"+
						"</form>";
			var reportdom =$(options.content).clone(true);
			$.when($('body').append(container)).done(function(){
				$.when(
					reportdom.find('a').each(function(){
						$(this).remove();
					}),
					reportdom.find('td, th').each(function(){
						var $this = $(this);
						if($this.hasClass('no-print')){
							$this.remove();
						}
					}),
					reportdom.find('div').each(function(){
						var $this = $(this);
						if($this.hasClass('space')){
							if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
								$this.remove();
						}
						$this.has('div').contents().unwrap();
					}),
					reportdom.find('span').each(function(){
						$(this).removeClass().removeAttr('style');
					}),
					reportdom.contents().filter(function() { return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); }).remove(),
					$('form#report_form #dom').val($.trim(reportdom.html())),
					$('form#report_form #title').val($(document).find("title").text())
				).done(function(){
					//console.log($.trim(reportdom.html()));
					$('form#report_form').submit(), $('form#report_form').submit(function(event){
						event.preventDefault();
					});	
				});
			});
		});
	}
}(jQuery));