import { Popover } from 'bootstrap';

$(function() {
    document.addEventListener('turbo:load', handlePageLoad);
});

function handlePageLoad() {
    $('.click-sub').on('click', '.sous-menu_', function(){ 
        $('.click-sub').toggleClass('active_');           
        $(this).toggleClass('visible');
        $(this).siblings('.sub-nav-pills').toggle()
    });
    $('.navbar-toggler').on('click', function()  {
        $('.nav-top-right').toggle();
    });
    $('#menuIcon').on('click', function () {
        $('.bootstrap-light').toggleClass('sidebar-collapsed');
    });
    $('#menuMobile').on('click', function () {
        $('.sidebar-area').toggleClass('active');
    });
    $('a[href*=#]').on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({ scrollTop: $($(this).attr('href')).offset().top}, 500, 'linear');
    });
}
