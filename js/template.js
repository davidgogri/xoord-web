// Preloader
$(window).on('load', function() {
    $("#preloader").delay(350).fadeOut("slow");
});

// Bloque principal de inicialización que usa jQuery(function($)
jQuery(function($) {
    // -----------------------------------------------------------
    // 1. SCROLL Y MENÚ FIJO
    // -----------------------------------------------------------
    $(window).scroll(function () {
        if ($(this).scrollTop() < 300) {
            $('#menu-wrap').hide();
        } else {
            $('#menu-wrap').show();
        }
    });
});

/*global $:false */
$(document).ready(function(){"use strict";

    // -----------------------------------------------------------
    // 2. SCROLL SUAVE A SECCIONES
    // -----------------------------------------------------------
    $(".scroll").click(function(event){
        event.preventDefault();

        var full_url = this.href;
        var parts = full_url.split("#");
        var trgt = parts[1];
        var target_offset = $("#"+trgt).offset();
        var target_top = target_offset.top - 62;

        $('html, body').animate({scrollTop:target_top}, 800);
    });
    
    // -----------------------------------------------------------
    // 3. INICIALIZACIÓN DE MODALES DE SERVICIO (CUSTOMBOX) - CORREGIDO
    // -----------------------------------------------------------
    $('.modal-link').each(function() {
        var $this = $(this);
        
        $this.on('click', function(e) {
            e.preventDefault(); 
            var targetId = $this.attr('href'); 
            
            Custombox.open({
                target: targetId, 
                effect: 'blur'    
            });
        });
    });
    
}); // Cierre del primer bloque $(document).ready()

// Home fit screen (Usando $(function) que es un alias de $(document).ready())
$(function(){"use strict";
    $('#home').css({'height':($(window).height())+'px'});
    $(window).resize(function(){
    $('#home').css({'height':($(window).height())+'px'});
    });
});


// Home text rotator	
$(".rotator > div:gt(0)").hide();
setInterval(function() { 
  $('.rotator > div:first')
    .fadeOut(0)
    .next()
    .fadeIn(1000)
    .end()
    .appendTo('.rotator');
},  3000);

// Slimmenu Toggler (Navigation)
$('ul.slimmenu').on('click',function(){
var width = $(window).width(); 
if ((width <= 800)){ 
    $(this).slideToggle(); 
}	
});		
	
// Navigation Slimmenu Initialization
$('ul.slimmenu').slimmenu(
{
    resizeWidth: '800',
    collapserTitle: '',
    easingEffect:'easeInOutQuint',
    animSpeed:'medium',
    indentChildren: true,
    childrenIndenter: '&raquo;'
});
			
// Sliders (BXSLIDER)
$(document).ready(function(){
    var sliderConfig = {
        adaptiveHeight: true, touchEnabled: true, pager: false,
        controls: true, auto: false, slideMargin: 1
    };
    $('.slider1, .slider2, .slider3, .slider4, .slider5').bxSlider(sliderConfig);
});	
	
// Blog carousel (OWL CAROUSEL)
$(document).ready(function() {
  $("#blog-slide").owlCarousel({
    navigation : false, slideSpeed : 600, autoHeight : false,
    autoPlay : false, items : 1, itemsDesktop : [1000,1],
    itemsDesktopSmall : [900,1], itemsTablet: [600,1], itemsMobile : false
  });
});	
	
// Responsive video (FITVIDS)
$(document).ready(function(){
    $(".media").fitVids();
});		
	
// Parallax effects
$(document).ready(function(){
    $('.parallax-home').parallax("50%", 0.5);
    $('.parallax').parallax("50%", 0.5);
    $('.parallax2').parallax("50%", 0.5);
    $('.parallax3').parallax("50%", 0.1);
});

// Modal windows (Custombox Demos)
$(function() {
    "use strict";
    $('#fall').on('click', function () {
        $.fn.custombox( this, { effect: 'fall' }); return false;
    });

    var slide_position = ['center'];
    $('#slide').on('click', function () {
        $.fn.custombox( this, { effect: 'slide', position: slide_position[Math.floor( Math.random() * slide_position.length )] }); return false;
    });

    $('#newspaper').on('click', function () {
        $.fn.custombox( this, { effect: 'newspaper' }); return false;
    });

    $('#sidefall').on('click', function () {
        $.fn.custombox( this, { effect: 'sidefall' }); return false;
    });
});

// Portfolio filter (Isotope) - Mantenido separado por complejidad
jQuery(document).ready(function () { 
	(function ($) { 
	    // ... (Código de Isotope) ...
	    var container = $('.all-works');
		
		function getNumbColumns() { 
			var winWidth = $(window).width(), 
				columnNumb = 1;
			// ... (lógica de cálculo) ...
			if (winWidth > 1500) { columnNumb = 3; } else if (winWidth > 1200) { columnNumb = 3; } else if (winWidth > 900) { columnNumb = 2; } else if (winWidth > 600) { columnNumb = 1; } else if (winWidth > 300) { columnNumb = 1; }
			return columnNumb;
		}
		
		function setColumnWidth() { 
			var winWidth = $(window).width(), 
				columnNumb = getNumbColumns(), 
				postWidth = Math.floor(winWidth / columnNumb);
		}
		
		$('#portfolio-filter #filter a').click(function () { 
			var selector = $(this).attr('data-filter');
			$(this).parent().parent().find('a').removeClass('current');
			$(this).addClass('current');
			container.isotope( { 
				filter : selector 
			});
			setTimeout(function () { 
				reArrangeProjects();
			}, 300);
			return false;
		});
		
		function reArrangeProjects() { 
			setColumnWidth();
			container.isotope('reLayout');
		}
		
		container.imagesLoaded(function () { 
			setColumnWidth();
			container.isotope( { 
				itemSelector : '.one-work', 
				layoutMode : 'masonry', 
				resizable : false 
			} );
		} );
		
		$(window).on('debouncedresize', function () { 
			reArrangeProjects();
		} );
	
	} )(jQuery);
} );

/* DebouncedResize Function */
	(function ($) { 
		var $event = $.event, 
			$special, 
			resizeTimeout;
		
		$special = $event.special.debouncedresize = { 
			setup : function () { 
				$(this).on('resize', $special.handler);
			}, 
			teardown : function () { 
				$(this).off('resize', $special.handler);
			}, 
			handler : function (event, execAsap) { 
				var context = this, 
					args = arguments, 
					dispatch = function () { 
						event.type = 'debouncedresize';
						$event.dispatch.apply(context, args);
					};
				
				if (resizeTimeout) {
					clearTimeout(resizeTimeout);
				}
				
				execAsap ? dispatch() : resizeTimeout = setTimeout(dispatch, $special.threshold);
			}, 
			threshold : 150 
		};
	} )(jQuery);			
	

// Colorbox single project pop-up
$(document).ready(function(){
$(".iframe").colorbox({iframe:true, width:"100%", height:"100%"});	
});

$(".group1").colorbox({rel:'group1'});		
	
// Switcher CSS
$(document).ready(function() {
"use strict";
    $("#hide, #show").click(function () {
        if ($("#show").is(":visible")) {
            $("#show").animate({"margin-left": "-500px"}, 500, function () { $(this).hide(); });
            $("#switch").animate({"margin-left": "0px"}, 500).show();
        } else {
            $("#switch").animate({"margin-left": "-500px"}, 500, function () { $(this).hide(); });
            $("#show").show().animate({"margin-left": "0"}, 500);
        }
    });
});


// Google map (Este debe ser el último en ejecutarse, para máxima estabilidad)
/*global $:false */
var map;
$(document).ready(function(){"use strict";
    map = new GMaps({
        disableDefaultUI: true,
        scrollwheel: false,
        el: '#map',
        // ¡RECUERDA: Reemplaza estas coordenadas por las de XOORD!
        lat: 44.789511,
        lng: 20.43633
    });
    map.drawOverlay({
        lat: map.getCenter().lat(), lng: map.getCenter().lng(),
        layer: 'overlayLayer', content: '<div class="overlay"></div>',
        verticalAlign: 'center', horizontalAlign: 'center'
    });
    var styles = [
        { "featureType": "poi", "stylers": [ { "visibility": "on" }, { "weight": 0.9 }, { "lightness": 37 }, { "gamma": 0.62 }, { "hue": "#ff0000" }, { "saturation": -93 } ] },
        { "featureType": "poi", "stylers": [ { "hue": "#ff0000" }, { "saturation": -1 }, { "color": "#ffffff" }, { "weight": 0.2 } ] },
        { "featureType": "road", "stylers": [ { "hue": "#ff0000" }, { "saturation": -98 } ] },
        { "featureType": "landscape", "stylers": [ { "hue": "#ff0000" }, { "saturation": -89 } ] },
        { "featureType": "water", "stylers": [ { "weight": 0.4 }, { "saturation": -38 } ] }
    ];
    map.addStyle({
        styledMapName:"Styled Map",
        styles: styles,
        mapTypeId: "map_style"
    });
    map.setStyle("map_style");
});