/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';
import 'bootstrap';
import { Tooltip, Toast, Popover, Modal } from 'bootstrap';

$(function() {
    document.addEventListener('turbo:load', handlePageLoad); // Attacher sur turbo:load pour le chargement initial
    document.addEventListener('turbo:frame-load', handleFrameLoad); // Attacher sur turbo:frame-load pour les chargements dans les frames

    function handleFrameLoad(event) {
        const context = event ? event.target : document;
        setupImageUpload(); 
        setupAvailabilityDropdown(); 
        setupCKEditors();
    }

    function handlePageLoad() {
        handleThemeChange();
        handleThemeInitialization();
        setupCKEditors();
        setupDynamicLinks();
        updateLogo();
        setupDeletionConfirmation();
        setupImageUpload(); 
        setupAvailabilityDropdown();  
        handleLoading();
        initiateLoadMore();
    }

    function initiateLoadMore() {
        const query = $('#hidden-query').val();

        if ($('#candidates-list').length) {
            loadMore('/result/candidates', '#candidates-list', query);
        }
        
        if ($('#joblistings-list').length) {
            loadMore('/result/joblistings', '#joblistings-list', query);
        }
        
        if ($('#prestations-list').length) {
            loadMore('/result/prestations', '#prestations-list', query);
        }
    }

    function loadMore(url, containerSelector, query) {
        let from = 10;
        let loading = false;
        const container = $(containerSelector);

        $(window).on('scroll', function() {
            if (!loading && ($(window).scrollTop() + $(window).height() >= $(document).height() - 100)) { // le seuil -100 pour déclencher juste avant le pied de page
                loading = true;
                const loader = $('<div class="text-center my-3" id="loader-spinner">' +
                    '<div class="spinner-border text-primary" role="status">' +
                    '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                    '</div>');
                container.append(loader);

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        from: from,
                        q: query
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(data) {
                        if (data.trim().length === 0) { 
                            $(window).off('scroll');
                            loader.remove();
                        } else {
                            container.append(data);
                            from += 10;
                        }
                        loading = false;
                        loader.remove();
                    },
                    error: function() {
                        loading = false;
                        loader.remove();
                    }
                });
            }
        });
    }

    function setupCKEditors() {
        $('input[name="account[type]"]').on('change', function() {
            $('#myFormT').trigger('submit');
        });
    
        $('#myFormT').on('submit', function(e) {
            e.preventDefault();
            console.log('submit')
            var url = $(this).data('action');
            var formData = new FormData(this);
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'html', 
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    console.log('success')
                    // Vérifiez si la réponse contient le target 'errorToast'
                    if (data.includes('target="errorToast"')) {
                        var errorToast = new Toast($('#errorToast')[0]);
                
                        setTimeout(function() {
                            errorToast.show();
                        }, 500);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur s\'est produite. Veuillez recommencer');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $(document).on('click', '.add-to-favorites', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                url: url,
                type: 'POST',
                contentType: false,
                processData: false,
                dataType: 'html', 
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    if (data.status === 'success') {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } 
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de l\'ajout du candidat dans vos favoris.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });
        
        $(document).on('click', '.remove-from-favorites', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                url: url,
                type: 'POST',
                contentType: false,
                processData: false,
                dataType: 'html', 
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    if (data.status === 'success') {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } 
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de l\'ajout du candidat dans vos favoris.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });
        

        const editors = document.querySelectorAll('.ckeditor-textarea');
        if (editors.length > 0) {
            editors.forEach(editorElement => {
                if (editorElement) {
                    ClassicEditor.create(editorElement, {
                        toolbar: {
                            items: [
                                'heading', '|',
                                'bold', 'italic', 'link', '|',
                                'bulletedList', 'numberedList', '|',
                                'blockQuote', 'insertTable', '|',
                                'undo', 'redo'
                            ]
                        }
                    })
                    .catch(error => {
                        console.error(error);
                    });
                }
            });
        }
    }


    function setupDeletionConfirmation() {
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        $('#delete-prestation').on('click', function() {
            const url = $(this).data('href'); 
            const confirmation = confirm("Êtes-vous sûr de vouloir supprimer cette prestation ? Cette action est irréversible.");
            
            if (confirmation) {
                window.location.href = url; 
            }
        });
    }
    
    function handleThemeChange() {
        $('#switch-theme').off('click').on('click', function() {
            const newTheme = $('body').hasClass('bootstrap-light') ? 'bootstrap-dark' : 'bootstrap-light';
            updateThemePreference(newTheme);
            updateLogo();
        });
    }
    
    function updateThemePreference(theme) {
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
        $('body').removeClass('bootstrap-dark bootstrap-light').addClass(theme);
    }

    function handleThemeInitialization() {
        const currentTheme = document.cookie.split('; ').find(row => row.startsWith('theme='));
        if (currentTheme) {
            const themeName = currentTheme.split('=')[1];
            $('body').removeClass('bootstrap-dark bootstrap-light').addClass(themeName);
            updateLogo();
        }
    }

    function updateLogo() {
        const currentTheme = $('body').hasClass('bootstrap-dark') ? 'dark' : 'light';
        const logoSrc = currentTheme === 'dark' ? '/images/logo-olona-talents-white600x200.png' : '/images/logo-olona-talents-black600x200.png';
        $('#logoOffCanvas').attr('src', logoSrc);
        $('#logo').attr('src', logoSrc);

        const themeIcon = currentTheme === 'dark' ? 'bi-brightness-high' : 'bi-moon-stars-fill';
        $('#switch-theme i').removeClass();
        $('#switch-theme i').addClass(`bi ${themeIcon}`);
    }

    function setupImageUpload() {
        const logoInput = document.getElementById('edit_entreprise_file'); 
        const imageInput = document.getElementById('prestation_file'); 
        const profileImgDiv = document.querySelector('.profile-img');
        const companyImgDiv = document.querySelector('.company-img');

        if(logoInput && companyImgDiv){
            logoInput.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        companyImgDiv.style.backgroundImage = 'url(' + e.target.result + ')';
                    };
                    reader.readAsDataURL(event.target.files[0]);
                }
            });
    
            companyImgDiv.addEventListener('click', function() {
                logoInput.click();
            });
        }
    
        if (imageInput && profileImgDiv) {
            imageInput.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
                    console.log('change')
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImgDiv.style.backgroundImage = 'url(' + e.target.result + ')';
                    };
                    reader.readAsDataURL(event.target.files[0]);
                }
            });
    
            profileImgDiv.addEventListener('click', function() {
                imageInput.click();
            });
        }

        
        const ids = ['#applyJob', '#createJob']; 
        ids.forEach(function(id) {
            $(id).on('submit', function(e) {

                $('.invalid-feedback').remove();
                $('.is-invalid').removeClass('is-invalid');
                var successToast = new Toast($('#errorToast')[0]);
                setTimeout(function() {
                    successToast.show(); 
                }, 1500);

                var modalElement = $(this).closest('.modal').get(0); 
                if (modalElement) {
                    var modal = Modal.getInstance(modalElement) || new Modal(modalElement);
                    modal.hide(); 
                }
            });
        });        

        $('a[role="menuitem"][href="#finish"]').on('click', function(){
            console.log('click')
            var successToast = new Toast($('#errorToast')[0]);
            setTimeout(function() {
                successToast.show(); 
            }, 1000);
        })
    }
    
    function setupAvailabilityDropdown() {
        const availabilityDropdown = document.getElementById('prestation_availability_nom');
        const dateField = document.getElementById('prestation_availability_dateFin');
    
        if (availabilityDropdown && dateField) {
            function toggleDateInput() {
                if (availabilityDropdown.value === 'from-date') {
                    dateField.style.display = 'block';  // Show the date field
                } else {
                    dateField.style.display = 'none';  // Hide the date field
                }
            }
    
            availabilityDropdown.addEventListener('change', toggleDateInput);
            toggleDateInput(); 
        }
    }

    function setupDynamicLinks() {
        if ($('.candidate-link').length) {
            $('.candidate-link').on('click', function(event) {
                event.preventDefault();
                var candidateId = $(this).data('id');
                var candidateContent = $('span[data-candidate="' + candidateId + '"]').html();
                $('#candidate-card-container').html(candidateContent);
            });
        }
    
        if ($('.annonce-link').length) {
            $('.annonce-link').on('click', function(event) {
                event.preventDefault();
                var annonceId = $(this).data('id');
                var annonceContent = $('span[data-annonce="' + annonceId + '"]').html();
                $('#candidate-card-container').html(annonceContent);
            });
        }
    
        if ($('.prestation-link').length) {
            $('.prestation-link').on('click', function(event) {
                event.preventDefault();
                var prestationId = $(this).data('id');
                var prestationContent = $('span[data-prestation="' + prestationId + '"]').html();
                $('#candidate-card-container').html(prestationContent);
            });
        }
    }

    function handleLoading() {

        var selectedValue = '';
        var selectedFBValue = '';
        var prestationValue = '';
        var prestationFBValue = '';
        var annonceValue = '';
        var annonceFBValue = '';
        var annonceBoostValue = '';
        var annonceBoostFBValue = '';
        var val = 0;

        $('input[name="annonce[boost]"]').on('change', function() {
            annonceValue = $(this).data('value');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#annonce_boostFacebook').show()
            }else{
                $('#annonce_boostFacebook').hide()
            }
        });

        $('input[name="annonce[boostFacebook]"]').on('change', function() {
            annonceFBValue = $(this).data('value');
            if (!annonceFBValue) {
                annonceFBValue = 0; 
            }
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
        });

        $('input[name="annonce_boost[boost]"]').on('change', function() {
            annonceBoostValue = $(this).data('value');
            var elementId = $(this).data('annonce');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#annonce_boost_boostFacebook_'+ elementId +'').show()
            }else{
                $('#annonce_boost_boostFacebook_'+ elementId +'').hide()
            }
            console.log(annonceBoostValue);
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', annonceBoostValue);
        });

        $('input[name="annonce_boost[boostFacebook]"]').on('change', function() {
            annonceBoostFBValue = $(this).data('value');
            if (!annonceBoostFBValue) {
                annonceBoostFBValue = 0; 
            }
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', annonceBoostFBValue + annonceBoostValue);
        });

        $('input[name="prestation[boost]"]').on('change', function() {
            prestationValue = $(this).data('value');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#prestation_boostFacebook').show()
            }else{
                $('#prestation_boostFacebook').hide()
            }
        });

        $('input[name="prestation[boostFacebook]"]').on('change', function() {
            prestationFBValue = $(this).data('value');
            if (!prestationFBValue) {
                prestationFBValue = 0; 
            }
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
        });

        $('input[name="prestation_boost[boost]"]').on('change', function() {
            prestationValue = $(this).data('value');
            var elementId = $(this).data('prestation');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#prestation_boost_boostFacebook_'+ elementId +'').show()
            }else{
                $('#prestation_boost_boostFacebook_'+ elementId +'').hide()
            }
            console.log(prestationValue);
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', prestationValue);
        });

        $('input[name="prestation_boost[boostFacebook]"]').on('change', function() {
            prestationFBValue = $(this).data('value');
            if (!prestationFBValue) {
                prestationFBValue = 0; 
            }
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', prestationFBValue + prestationValue);
        });

        $('input[name="candidate_boost[boost]"]').on('change', function() {
            selectedValue = $(this).data('value');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#candidate_boost_boost_boost_facebook').show()
            }else{
                $('#candidate_boost_boost_boost_facebook').hide()
            }
            console.log(selectedValue);
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', selectedValue);
        });

        $('input[name="candidate_boost[boostFacebook]"]').on('change', function() {
            selectedFBValue = $(this).data('value');
            if (!selectedFBValue) {
                selectedFBValue = 0; 
            }
            valFB = $(this).val();
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', selectedFBValue + selectedValue);
        });

        $('button[data-bs-target="#confirmationModal"]').on('click', function() {
            var packagePrice = $(this).attr('data-bs-price');
            var modalBody = $('#confirmationModal').find('.modal-body');
            modalBody.text(`Voulez-vous vraiment dépenser ${packagePrice} ?`);
        });

        $('input[name="recruiter_boost[boost]"]').on('change', function() {
            selectedValue = $(this).data('value');
            val = $(this).val();
            $('.form-check').removeClass('selected');
            $(this).closest('.form-check').addClass('selected');
            if(val > 0){
                $('#recruiter_boost_boost_boost_facebook').show()
            }else{
                $('#recruiter_boost_boost_boost_facebook').hide()
            }
            console.log(selectedValue);
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', selectedValue);
        });

        $('input[name="recruiter_boost[boostFacebook]"]').on('change', function() {
            selectedFBValue = $(this).data('value');
            if (!selectedFBValue) {
                selectedFBValue = 0; 
            }
            $('.form-check').removeClass('selected-facebook');
            $(this).closest('.form-check').addClass('selected-facebook');
            $('button[data-bs-target="#confirmationModal"]').attr('data-bs-price', selectedFBValue + selectedValue);
        });
        
        var boostProfiles = [
            { name: 'candidate_boost[boost]', type: 'boost-profile' },
            { name: 'recruiter_boost[boost]', type: 'boost-profile' }
        ];

        boostProfiles.forEach(function(boost) {
            $('input[type="radio"][name="' + boost.name + '"]').on('change', function() {
                var dataLabel = $(this).data('value');
                console.log(dataLabel);
                $('#confirmationModal .modal-body').text("Voulez-vous vraiment dépenser " + dataLabel + " crédits");
            });
        });
        
        var boosts = [
            { name: 'create_candidate_boost[boost]', type: 'boost-profile' },
            { name: 'create_recruiter_boost[boost]', type: 'boost-profile' }
        ];

        boosts.forEach(function(boost) {
            $('input[type="radio"][name="' + boost.name + '"]').on('change', function() {
                $('.card').removeClass('card-selected');
                var cardElement = $(this).closest('.col').find('.card');
                if ($(this).is(':checked') && cardElement.length) {
                    cardElement.addClass('card-selected');
                }
                var dataLabel = $(this).closest('.col').find('h2').data('label');
                console.log(dataLabel);

                var nextButton = $('#boostProfileButton'); 
                nextButton.attr('data-bs-toggle', 'modal');
                nextButton.attr('data-bs-target', '#confirmationModal');
                nextButton.attr('data-bs-price', dataLabel);
                nextButton.attr('data-bs-type', boost.type);
                nextButton.attr('data-toast', 'false');
                $('#confirmationModal .modal-body').text("Voulez-vous vraiment dépenser " + dataLabel);
            });
        });


        $('#boostProfileButton').on('click', function(){
            var dataToast = $(this).attr('data-toast');
            if (dataToast === "true") {
                $('#errorToast').find('.toast-body').text('Vous devez selectionner un boost');
                var errorToast = new Toast($('#errorToast')[0]);
                errorToast.show();
            }
        })
        
        $('#confirmButton').off('click').on('click', function() {
            var buttonType = $(this).attr('data-id');
            var prestationId = $(this).attr('data-prestation-id');
            var annonceId = $(this).attr('data-annonce-id');
            if (buttonType === "show-candidate-contact") {
                var form = $('button[data-bs-type="show-candidate-contact"]').closest('form');
                form.trigger("submit");
            } else if (buttonType === "show-recruiter-contact") {
                var form = $('button[data-bs-type="show-recruiter-contact"]').closest('form');
                form.trigger("submit");
            } else if (buttonType === "upload-cv") {
                var form = $('button[data-bs-type="upload-cv"').closest('form');
                form.trigger("submit");
            } else if (buttonType === "boost-profile") {
                var form = $('button[data-bs-type="boost-profile"]').closest('form');
                form.trigger("submit");
            } else if (buttonType === "apply-job") {
                var form = $('button[data-bs-type="apply-job"]').closest('form');
                form.trigger("submit");
            } else if (buttonType === "boost-prestation") {
                var form = $('button[data-prestation-id="'+ prestationId +'"]').closest('form');
                form.trigger("submit");
            } else if (buttonType === "boost-annonce") {
                var form = $('button[data-annonce-id="'+ annonceId +'"]').closest('form');
                form.trigger("submit");
            }
            $('#confirmationModal').modal('hide');
        });

        $('#confirmationModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var packagePrice = button.data('bs-price');
            var packageType = button.data('bs-type');
            var prestationId = button.data('prestation-id');
            var annonceId = button.data('annonce-id');
            var modalBody = $(this).find('.modal-body');
            var submitButton = $(this).find('#confirmButton');
            modalBody.text(`Voulez-vous vraiment dépenser ${packagePrice} ?`);
            submitButton.attr('data-id', packageType);
            submitButton.attr('data-prestation-id', prestationId);
            submitButton.attr('data-annonce-id', annonceId);
        });

        $('[id^=boostPrestation]').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var prestationId = button.data('bs-prestation');
            var packageType = button.data('bs-type');
            var hiddenField = $(this).find('.prestation-edit-id');
            hiddenField.val(prestationId);
            var submitButton = $(this).find('#confirmButton');
            submitButton.attr('data-id', packageType);
            submitButton.attr('data-prestation-id', hiddenField);
        });

        $('[id^=boostAnnonceForm_]').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var annonceId = button.data('bs-annonce');
            var packageType = button.data('bs-type');
            var hiddenField = $(this).find('.annonce-edit-id');
            hiddenField.val(annonceId);
            console.log(hiddenField)
            var submitButton = $(this).find('#confirmButton');
            submitButton.attr('data-id', packageType);
            submitButton.attr('data-annonce-id', hiddenField);
        });

        $('#boostPrestation').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var prestationId = button.data('bs-prestation');
            var packageType = button.data('bs-type');
            var hiddenField = $(this).find('#prestation-edit-id');
            hiddenField.val(prestationId);
            var submitButton = $(this).find('#confirmButton');
            submitButton.attr('data-id', packageType);
        });

        $('#notification').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            var id = button.attr('data-bs-id');
            var title = button.attr('data-bs-title');
            var content = button.attr('data-bs-content');
            var expediteur = button.attr('data-bs-expediteur');
    
            var modalHeader = modal.find('.modal-header');
            var modalBody = modal.find('.modal-body');
            modalHeader.html(`<h4 class="modal-title fs-5" id="notificationLabel"> ${title} </h4>`);
            modalBody.html(` ${content} <br> <small class="">De : ${expediteur} </small>`);
            $.ajax({
                url: '/v2/dashboard/notification/view/' + id,
                type: 'POST',
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data)
                    if (data.success) {
                        var trow = $('#row_notification_' + data.id )
                        var status = trow.find('.status');
                        var icone = trow.find('.icone i');
                        trow.removeClass('fw-semibold fw-lighter');
                        trow.addClass('fw-lighter');
                        icone.removeClass('bi-bell-fill bi-bell');
                        icone.addClass('bi-bell');
                        status.html('<span class="badge bg-success px-3"><i class="bi bi-check2-square"></i> Lu </span>')
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                }
            });
        });

        $('#createPrestation').on('submit', function(e){
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    console.log(data); 
                    if (data.success) {
                        // Redirection vers l'URL spécifiée dans data.redirect
                        if (data.redirect) {
                            window.location.href = data.redirect; 
                        }
                    }else if(data.success === false){
                        $('#errorToast .toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Modal($('#lowCreditModal')[0]);
                        errorToast.show();
                    } else {
                        Turbo.renderStreamMessage(data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    Turbo.renderStreamMessage(data);
                }
            });
        })
        
        $('#boostProfileForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data); // Assurez-vous que c'est `success` et non `succes`
                    if (data.success) {
                        $('#successToast .toast-body').text(data.message);
                        console.log($('#newCheckBoost').length); 
                        $('#newCheckBoost').html(data.detail);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                        var boostProfileModal = Modal.getInstance($('#boostProfile')[0]) || new Modal($('#boostProfile')[0]);
                        boostProfileModal.hide();
                    } else {
                        $('#errorToast .toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Modal($('#lowCreditModal')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast .toast-body').text('Une erreur s\'est produite.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });
        
        $('.boost-prestation-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data); 
                    if (data.success) {
                        $('#successToast .toast-body').text(data.message);
                        var part = $('#col_prestation_recruiter_' + data.id)
                        part.html(data.detail);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                        var boostProfileModal = Modal.getInstance($('#boostProfile')[0]) || new Modal($('#boostProfile')[0]);
                        boostProfileModal.hide();
                    } else {
                        $('#errorToast .toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Modal($('#lowCreditModal')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast .toast-body').text('Une erreur s\'est produite.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });
        
        $('.boost-annonce-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var form = $(this);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data); 
                    if (data.success) {
                        $('#successToast .toast-body').text(data.message);
                        var part = $('#col_annonce_recruiter_' + data.id)
                        part.html(data.detail);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                        var boostProfileModal = Modal.getInstance($('#boostProfile')[0]) || new Modal($('#boostProfile')[0]);
                        boostProfileModal.hide();
                    } else {
                        $('#errorToast .toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Modal($('#lowCreditModal')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast .toast-body').text('Une erreur s\'est produite.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#applyJob').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    console.log(data)
                    if (data.success) {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                        var boostProfileModal = Modal.getInstance($('#boostProfile')[0]) || new Modal($('#boostProfile')[0]);
                        boostProfileModal.hide();
                    } else {
                        $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la tentative de boost de votre profil.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#show-recruiter-contact').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    console.log(data)
                    if (data.success) {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } else {
                        $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la tentative de boost de votre profil.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#show-candidate-contact').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    console.log(data)
                    if (data.success) {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } else {
                        $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la tentative de boost de votre profil.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#delete-contact').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: this.action,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    console.log(data)
                    if (data.success) {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } else {
                        $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la tentative de boost de votre profil.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#package').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            var packageName = button.data('bs-package');
            var packagePrice = button.data('bs-price');
            var packageId = button.data('bs-id');

            var modalTitle = modal.find('.modal-title');
            var modalBodySelect = modal.find('#transaction_package');
            var modalBodyRecap = modal.find('#recapCommand');
            var modalBodyRecapPrice = modal.find('#recapCommandPrice');
            var packagePriceNumber = parseFloat(packagePrice.replace(/\./g, ''));
            modalTitle.text(`Achat sécurisé : ${packagePrice} Ariary (${(packagePriceNumber / 5072.95).toFixed(2)} €) | ${packageName} `);
            modalBodyRecap.html(`<span class="fw-light fs-6">Produit :</span> <span class="fw-bold fs-5">${packageName}</span> `);
            modalBodyRecapPrice.html(`<span class="fw-light fs-6">Prix :</span> <span class="fw-bold fs-5">${(packagePriceNumber / 5072.95).toFixed(2)} €</span>`);
            modalBodySelect.val(packageId);
                      
        });

        $('input[name="order[paymentMethod]"]').on('change', function() {
            $('#mobileMoneySubmit').prop('disabled', false);
        })  

        $('#package').on('hide.bs.modal', function (event) {
            setTimeout(function() {
                $('#bankCard').show();
                $('#mobileMoney').show();
                $('input[name="transaction[typeTransaction]"]').prop('checked', false); 
                $('input[name="transaction[reference]"]').val('');
                $('input[name="transaction[amount]"]').val('');
                $('#pointMarchand').hide();
                $('#bankApi').hide();
                $('.invalid-feedback').remove();
                $('.is-invalid').removeClass('is-invalid');
            }, 500);
        })

        $('#transactionForm').on('submit', function(e) {
            e.preventDefault();
            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');
            var url = $(this).data('action');
            var formData = new FormData(this);
            var packageModal = Modal.getInstance($('#package')[0]);
            if (!packageModal) {
                packageModal = new Modal($('#package')[0]);
            }
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'html', 
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    console.log('Response processed by Turbo:', data);
                    Turbo.renderStreamMessage(data);
        
                    // Vérifiez si la réponse contient le target 'errorToast'
                    if (data.includes('target="errorToast"')) {
                        var errorToast = new Toast($('#errorToast')[0]);
                        var packageModal = Modal.getInstance($('#package')[0]) || new Modal($('#package')[0]);
                        packageModal.hide();
                
                        setTimeout(function() {
                            errorToast.show();
                        }, 500);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur s\'est produite. Veuillez recommencer');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                }
            });
        });

        $('#cvForm').on('submit', function(e) {
            e.preventDefault();
            var toast = new Toast($('#loadingToast')[0], {
                autohide: false 
            });
            $('#loader-container').show()
            toast.show();
            var formData = new FormData(this);
            var actionUrl = $(this).data('action');
            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html'
                },
                success: function(data) {
                    Turbo.renderStreamMessage(data);
                    if (data.success) {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } else {
                        $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erreur:', textStatus, errorThrown);
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la tentative de boost de votre profil.');
                    var errorToast = new Toast($('#errorToast')[0]);
                    errorToast.show();
                },
                complete: function() {
                    toast.hide();
                    $('#loader-container').hide()
                }
            });
        });

        var fileInput = $('#cvForm input[type="file"]');
        var uploadButton = $('#upload-button');
        var submitButton = $('#submit-button');
        var fileNameDisplay = $('#file-name');

        uploadButton.on('click', function() {
            fileInput.trigger("click");
        });

        fileInput.on('change', function() {
            var fileName = fileInput[0].files[0] ? fileInput[0].files[0].name : 'No file chosen';
            fileNameDisplay.text(fileName);

            uploadButton.hide()
            submitButton.show()
        });
    }

    $('#experience').on('shown.bs.modal', function () {
        function handleFieldGroup(baseId) {
            for (let i = 0; i < 10; i++) {  
                const $currentlyCheckbox = $(`#${baseId}_${i}_enPoste`);
                const $endDateFieldContainer = $(`#${baseId}_${i}_dateFin`).closest('div');
    
                if (!$currentlyCheckbox.length) {
                    break; 
                }
    
                $endDateFieldContainer.parent().toggle(!$currentlyCheckbox.is(':checked'));
    
                $currentlyCheckbox.off('change').change(function() {
                    $endDateFieldContainer.parent().toggle(!$(this).is(':checked'));
                });
            }
        }
    
        handleFieldGroup('step_two_experiences');
        handleFieldGroup('step_three_experiences');
    });
    

    var modalIds = ['experience', 'technicalSkill', 'language'];

    modalIds.forEach(function(modalId) {
        $('#' + modalId).on('hidden.bs.modal', function () {
            $(this).find('ul[data-form-collection-target="collectionContainer"]').empty();
        });
    });
});