/**
 * @class elfindernav - elFinder container for diretories tree and places
 *
 * @author Dmitry (dio) Levashov
 **/
$.fn.elfindernavbar = function(fm, opts) {

	this.not('.elfinder-navbar').each(function() {
		var nav    = $(this).addClass('ui-state-default elfinder-navbar'),
			parent = nav.parent()
				.resize(function() {
					nav.height(wz.height() - delta);
				}),
			wz     = parent.children('.elfinder-workzone').append(nav),
			delta  = nav.outerHeight() - nav.height(),
			ltr    = fm.direction == 'ltr',
			handle;

		
		if ($.fn.resizable) {
			handle = nav.resizable({
					handles : ltr ? 'e' : 'w',
					minWidth : opts.minWidth || 150,
					maxWidth : opts.maxWidth || 500
				})
				.bind('resize scroll', function() {
					var offset = (fm.UA.Opera && nav.scrollLeft())? 20 : 2;
					handle.css({
						top  : parseInt(nav.scrollTop())+'px',
						left : ltr ? 'auto' : parseInt(nav.scrollLeft() + offset),
						right: ltr ? parseInt(nav.scrollLeft() - offset) * -1 : 'auto'
					});
				})
				.find('.ui-resizable-handle').zIndex(nav.zIndex() + 10);

			if (!ltr) {
				nav.resize(function() {
					nav.css('left', null).css('right', 0);
				});
			}

			fm.one('open', function() {
				setTimeout(function() {
					nav.trigger('resize');
				}, 150);
			});
		}
	});
	
	return this;
};
