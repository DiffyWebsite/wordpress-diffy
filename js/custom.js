( function( $ ) {
    var pluginPath = params.pluginPath;
    
    $( document ).ready( function() { 
        $('.menu-top .toplevel_page_wordpress-diffy')
        .append(`<img class="wordpress-diffy-arrow" src="${pluginPath}img/arrow.svg">`)
        .ready( function() {
            $('.wordpress-diffy-arrow').addClass("wordpress-diffy-arrow-active");
        } )
    });

})( jQuery );
