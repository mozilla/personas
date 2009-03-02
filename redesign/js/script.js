$.fn.slider = function (options) {
    var settings = jQuery.extend({
        "slides"        : "#slides li",
        "start"         : 1,
        "nav"           : "#slideshow-nav",
        "previous"      : "#slideshow-previous",
        "next"          : "#slideshow-next",
        "nextImg"       : "/img/nav-next.png",
        "nextOverImg"   : "/img/nav-next-over.png",
        "prevOverImg"   : "/img/nav-prev-over.png",
        "prevImg"       : "/img/nav-prev.png"
    }, options);
    
    var items = jQuery(settings['slides']);
    var index = 0;
    var viewportWidth = jQuery("#slides li:first").css("width");
    viewportWidth = viewportWidth.substring(0,viewportWidth.length-2);
    var numItems = jQuery("#slides li").length-1;

    jQuery("<img>").attr("src", settings['nextOverImg']);
    jQuery("<img>").attr("src", settings['prevOverImg']);

    jQuery(settings['next']).click(function() {
        if(numItems != index) {
            jQuery("#slides").animate({left:"-="+viewportWidth+"px"});
            index++; 
            setNav(index);
        }
        return false;
    });
    
    jQuery(settings['previous']).click(function() {
        if(index != 0) {
            jQuery("#slides").animate({left:"+="+viewportWidth});
            index--;
            setNav(index);
        }
        return false;
    });
    
    jQuery(settings['nav']+' li a').click(function(event) {
        var value = Number(this.innerHTML)-1;
        var diff = index - value;
        if(diff != 0) {
            jQuery("#slides").animate({left:"+="+diff*viewportWidth+"px"});
            setNav(value);
            index = value;
        }
        return false;
    });
    
    jQuery(settings['next']).hover(
        function() {
            jQuery(this).children('img').attr('src', settings['nextOverImg']);
        },
        function() {
            jQuery(this).children('img').attr('src', settings['nextImg']);
        }
    );
    
    jQuery(settings['previous']).hover(
        function() {
            jQuery(this).children('img').attr('src', settings['prevOverImg']);
        },
        function() {
            jQuery(this).children('img').attr('src', settings['prevImg']);
        }
    );
    
    function setNav(newIndex) {
        jQuery(settings['nav'] + ' li a.active').removeClass('active');
        jQuery(settings['nav'] + ' li a:eq('+newIndex+')').addClass('active');
    }
    
};