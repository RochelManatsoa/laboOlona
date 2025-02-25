$(function() {
    document.addEventListener('turbo:load', handlePageLoad);
});

function handlePageLoad() {
    $('.click-sub').on('click', '.sous-menu_', function(){            
        $(this).toggleClass('visible');
        $(this).siblings('.sub-nav-pills').toggle()
    });
}
