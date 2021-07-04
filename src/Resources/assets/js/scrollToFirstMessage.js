function scrollToFirstMessage(scrollToError, scrollToFlashMessage, openTableIfMessage)
{
    if( typeof(scrollToError) == 'undefined' ){
        scrollToError = true;
    }
    if( typeof(scrollToFlashMessage) == 'undefined' ){
        scrollToFlashMessage = true;
    }
    if( typeof(openTableIfMessage) == 'undefined' ){
        openTableIfMessage = true;
    }

    $(document).ready(function() {
        if (scrollToFlashMessage) {
            var cible = $('.flash-message:first');
            if(cible.length > 0) {
                if (openTableIfMessage) {
                    openTable(cible);
                }
                var popupWrapper = $(cible).parents('.popup_wrapper_visible:first');
                if (popupWrapper.length > 0) {
                    height = getPositionTocenter(getCiblePositionInModal(cible));
                    $(popupWrapper).animate({scrollTop:height});
                } else {
                    height = getPositionTocenter(cible.offset().top);
                    $('html,body').animate({scrollTop:height});
                }
                return;
            }
        }

        if (scrollToError) {
            var cible = $('li.form_error_message:first');
            if(cible.length > 0) {
                if (openTableIfMessage) {
                    openTable(cible);
                }

                var popupWrapper = $(cible).parents('.popup_wrapper_visible:first');
                if (popupWrapper.length > 0) {
                    height = getPositionTocenter(getCiblePositionInModal(cible));
                    $(popupWrapper).animate({scrollTop:height});
                } else {
                    height = getPositionTocenter(cible.offset().top);
                    $('html,body').animate({scrollTop:height});
                }
                return;
            }
        }
    });
}

function openTable(cible)
{
    pane = cible.parents('div.pane-auto-display-first-message');
    table = cible.parents('div.tab-auto-display-first-message');
    if(pane.length > 0 && table.length > 0) {
        idTable = table.attr('id');
        indexPane = pane.prevAll("#"+idTable+" div.pane-auto-display-first-message").length;
        api = $("#"+idTable).tabs();
        api.click(indexPane);
    }
}

function getCiblePositionInModal(cible)
{
    var divModal = $(cible).parents('.crud_modal:first');
    var cibleOffset = parseInt(cible.offset().top);
    var divModalOffset = parseInt(divModal.offset().top);
    var divModalMarginTop = parseInt(divModal.css('margin-top'));

    return cibleOffset - divModalOffset + divModalMarginTop;
}

function getPositionTocenter(errorPosition)
{
    var positionToCenter =  errorPosition - ($(window).height() / 2);
    if (positionToCenter < 0) {
        positionToCenter = 0;
    }

    return positionToCenter;
}
