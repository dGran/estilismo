$( document ).ready(function() {
    $(document).on('click', '.edit_button', function () {
        let url = $(this).data('url');
        let container = $("#modal-static-content");

        container.html('');

        $.ajax({
            type: 'POST',
            url: url,
            data: {
                pathIndex: $(this).data('path-index'),
            },
            success: function(data) {
                container.html(data);
            },
            error: function() {
                Toast.fire({
                    icon: 'error', title: 'Se ha producido un error'
                });
            }
        });
    });

    $(document).on('click', '#update-button', function (e){
        e.preventDefault();

        if (formIsValid()) {
            // let form = $(this).parents('form');
            // let formData = form.serialize();

            let form = $(this).parents('form');
            let formData = new FormData(form[0]);

            $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.reload();
                    } else {
                        $('.ajax-form').replaceWith(response);
                    }
                },
            });
        } else {
            Toast.fire({
                icon: 'error', title: 'Comprueba los errores del formulario'
            });
        }
    });

    $(document).on('change', '#pet_name', function (e){
        if (!$(this).val()) {
            markErrorElement($(this))
        } else {
            unmarkErrorElement($(this));
        }
    });

    $(document).on('change', '#pet_category', function (e){
        if (!$(this).val()) {
            markErrorElement($(this))
        } else {
            unmarkErrorElement($(this));
        }
    });

    function markErrorElement(element) {
        element.removeClass('border-slate-300').addClass('border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500').focus();
        $('#update-button').prop('disabled', true).removeClass('bg-blue-500 hover:bg-blue-600 focus:bg-blue-600').addClass('bg-blue-300 hover:bg-blue-300 focus:bg-blue-300 pointer-events-none');
    }

    function unmarkErrorElement(element) {
        element.addClass('border-slate-300').removeClass('border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500').focus();
        $('#update-button').prop('disabled', false).addClass('bg-blue-500 hover:bg-blue-600 focus:bg-blue-600').removeClass('bg-blue-300 hover:bg-blue-300 focus:bg-blue-300 pointer-events-none');
    }

    function formIsValid() {
        return !(!$('#pet_name').val() || !$('#pet_category').val());
    }

    $(document).on('change', '#pet_imageFile', function (e){
        previewImage(event, '#preview')
    });

    function previewImage(event, querySelector){
        const input = event.target;
        imgPreview = document.querySelector(querySelector);

        if (!input.files.length) return

        objectURL = URL.createObjectURL(input.files[0]);
        imgPreview.src = objectURL;
    }

    $(document).on('click', '#load-image-button', function(){
        $('#pet_imageFile').trigger('click');
    });

    $(document).on('click', '#delete-image-button', function(){
        let deleteImage = $('#deleteImage');

        if (!deleteImage.val()) {
            deleteImage.val(true);
        }

        console.log(deleteImage.val());
    });

});