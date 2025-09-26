
$(document).on('click', '.auto_fill_description', function () {
    const $button = $(this);
    const lang = $button.data('lang');
    const route = $button.data('route');
    const type = $button.data('type');

    const $editorContainer = $('#editor-container-' + lang);
    const $editor = $('#description-' + lang + '-editor');
    const description = $('#description-' + lang);
    const requestType = $('#request_type').val();
    const restaurant_id = $('#restaurant_id').val();

 let $input = (type === 'default')
        ? $('#default_name')
        : $('#' + lang + '_name');

    let name = $input.val();

    if (!name) {
        if($('#default_name').val()){
            name = $('#default_name').val();
        } else
        {
            toastr.error($button.data('error'));
            return;
        }
    }



    $editorContainer.addClass('animating');
    $button.prop('disabled', true);
    $button.find('.btn-text').text('');
    const $aiText = $button.find('.ai-text-animation');
    $aiText.removeClass('d-none').addClass('ai-text-animation-visible');
    let $wrapper = $(this).closest('.des_wrapper').find('.outline-wrapper');
    $wrapper.addClass('outline-animating');

    $.ajax({
        url: route,
        type: 'GET',
        dataType: 'json',
        data: {
            name: name,
            langCode: lang,
            requestType: requestType,
            restaurant_id:restaurant_id
        },
        success: function (response) {
            if(type === 'default'){
                $('#description-default').val(response.data.description);
            }else{
                description.val(response.data.description);
            }

            replaceSVGs();
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error('An unexpected error occurred.');
            }
        },

        complete: function () {
            setTimeout(function () {
                $editorContainer.removeClass('animating');
                $wrapper.removeClass('outline-animating');
            }, 500);

            $button.prop('disabled', false);
            $button.find('.btn-text').text('Re-generate');
            $aiText.addClass('d-none').removeClass('ai-text-animation-visible');
        }
    });
});
