jQuery.preloadImages = function() {
    for(var i = 0; i<arguments.length; i++)
        jQuery("<img>").attr("src", arguments[i]);
}

// Preload various background images
$.preloadImages('images/personas-bg.jpg',
                'images/personas-bg-browsers.jpg',
                'images/personas-table-foot.png',
                'images/personas-table-middle.png',
                'images/personas-btn-get-ovr.png',
                'images/personas-btn-avail-ovr.png',
                'images/personas-table-design-ovr.png',
                'images/personas-table-faq-ovr.png',
                'images/macFFBgHack.png',
                'images/personas-avail-box-side-bg.png',
                'images/personas-avail-footb.png',
                'images/personas-avail-dots.png',
                'images/personas-desig-sides.png',
                'images/personas-desig-foot-waves.png',
                'images/personas-faq-side-bg.png',
                'images/personas-faq-foot.png');

// Document load functions
$(document).ready(function () {
    // setup hover images
    $('#install-button').hover(
        function () { $(this).attr('src', 'images/personas-btn-get-ovr.png'); },
        function () { $(this).attr('src', 'images/personas-btn-get.png'); }
    );
    
    $('#link-available').hover(
        function () { $(this).attr('src', 'images/personas-table-avail-ovr.png'); },
        function () { $(this).attr('src', 'images/personas-table-avail.png'); }
    );
    
    $('#link-design').hover(
        function () { $(this).attr('src', 'images/personas-table-design-ovr.png'); },
        function () { $(this).attr('src', 'images/personas-table-design.png'); }
    );
    
    $('#link-faq').hover(
        function () { $(this).attr('src', 'images/personas-table-faq-ovr.png'); },
        function () { $(this).attr('src', 'images/personas-table-faq.png'); }
    );

    // Check if using Firefox
    var ua = navigator.userAgent.toLowerCase();
    if (ua.indexOf('firefox') == -1 &&
        ua.indexOf('minefield') == -1 &&
        ua.indexOf('bonecho') == -1 &&
        ua.indexOf('granparadiso') == -1) {
        // If not using Firefox, change install link and show notice
        $('#install-link').attr('href', 'http://www.mozilla.com/en-US/firefox/?ref=personas');
        $('#install-nonfirefox').show();
    }
});

