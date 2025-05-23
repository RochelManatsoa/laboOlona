/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';
import 'bootstrap';
import { Tooltip, Toast, Carousel, Modal } from 'bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

$(function() {
    setupDynamicLinks();
    document.addEventListener('turbo:load', handlePageLoad); // Attacher sur turbo:load pour le chargement initial
    document.addEventListener('turbo:frame-load', handleFrameLoad); // Attacher sur turbo:frame-load pour les chargements dans les frames

    $(document).on('click', 'a', function(event) {
        var url = $(this).attr('href');
        if (url) {
            $.ajax({
                url: '/v2/activity/log/click',  
                method: 'POST',
                data: {
                    page: url,
                }
            });
        }
    });

    function handleFrameLoad(event) {
        const context = event ? event.target : document;
        setupImageUpload(); 
        setupAvailabilityDropdown(); 
        setupCKEditors();
        setupDynamicLinks();
        toggleFields();
        contestFacebook();
    }

    function handlePageLoad() {
        handleThemeChange();
        setupDynamicLinks();
        // handleThemeInitialization();
        setupCKEditors();
        // updateLogo();
        setupDeletionConfirmation();
        setupImageUpload(); 
        setupAvailabilityDropdown();  
        handleLoading();
        initializeSliders();
        initiateLoadMore();
        toggleFields();
        initializeCarousels(); 
        contestFacebook();
        checkAndShowTutorial();
    }

    function checkAndShowTutorial() {
        const tutorialViewed = document.cookie
            .split('; ')
            .find((row) => row.startsWith('tutorialViewed='));
    
        if (!tutorialViewed) {
            const modalEl = document.getElementById('tutorialModal1Toggle');
            if (modalEl) {
                const tutorialModal1Toggle = new bootstrap.Modal(modalEl);
                tutorialModal1Toggle.show();
                document.cookie = "tutorialViewed=true; path=/; max-age=2592000";
            }
        }
    
        const modalElement = document.getElementById('uploadCVModalToggle');
        if (modalElement) {
            const modalCV = new bootstrap.Modal(modalElement);
            modalCV.show();
        }
    }

    function contestFacebook() {

        $('#yesButton').on('click', function () {
            $(this).addClass('active'); 
            $('#noButton').removeClass('active'); 
            $('#user').show(); 
            $('#newUser').hide(); 
        });

        $('#noButton').on('click', function () {
            $(this).addClass('active'); 
            $('#yesButton').removeClass('active');
            $('#user').hide(); 
            $('#newUser').show(); 
        });
    }

    // Fonction pour gérer l'affichage des champs en fonction de la sélection
    function toggleFields() {
        var selectedValue = $('#contract_typePerson').val(); 

        if (selectedValue === "0") { 
            $('#person-type').text("Je suis un");
            $('#contract_socialReason').closest('.mb-3').hide(); 
            $('#contract_siret').closest('.mb-3').hide(); 
            $('#contract_firstName').closest('.mb-3').show(); 
            $('#contract_lastName').closest('.mb-3').show(); 
        } else { 
            $('#person-type').text("Je suis une");
            $('#contract_socialReason').closest('.mb-3').show();
            $('#contract_siret').closest('.mb-3').show(); 
            $('#contract_firstName').closest('.mb-3').hide(); 
            $('#contract_lastName').closest('.mb-3').hide(); 
        }
    }

    function initializeCarousels() {
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            new Carousel(carousel);
        });
    }

    function initiateLoadMore() {
        const query = $('#hidden-query').val();
        if (query) { 
            if ($('#candidates-list').length) {
                loadMore('/result/candidates', '#candidates-list', query);
            }
            
            if ($('#joblistings-list').length) {
                loadMore('/result/joblistings', '#joblistings-list', query);
            }
            
            if ($('#prestations-list').length) {
                loadMore('/result/prestations', '#prestations-list', query);
            }

            if ($('#outils-ia-list').length ) {
                loadMore('/v2/dashboard/outils-ai', '#outils-ia-list', query)
            }
        }
    }
    
    function loadMore(url, containerSelector, query) {
        let from = 6;
        let loading = false;
        let hasMore = true;
        const container = $(containerSelector);
    
        $(window).on('scroll', function() {
            if (!loading && hasMore && ($(window).scrollTop() + $(window).height() >= $(document).height() - 100)) {
                loading = true;
                const loader = $('<div class="text-center">'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-primary" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>' +
                '<div class="spinner-grow spinner-grow-sm mx-2 text-secondary" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-success" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-danger" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-warning" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-info" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div>'+
                '<div class="spinner-grow spinner-grow-sm mx-2 text-dark" role="status">' +
                '<span class="visually-hidden">Loading...</span>'+
                '</div></div>');

                container.append(loader);
    
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        from: from,
                        size: 6,
                        q: query
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(data) {
                        if (data.content && data.content.trim()) {
                            container.append(data.content);
                            initializeSliders();
                            from += 6;
                            hasMore = data.hasMore;
                        } else {
                            hasMore = false;
                        }
                        loading = false;
                        loader.remove();
                    },
                    error: function() {
                        loading = false;
                        hasMore = false;
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
                                'bold', 'italic', 'link', '|',
                                'bulletedList', 'numberedList', '|',
                                'blockQuote', '|',
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
        
        const editorsFull = document.querySelectorAll('.full-ckeditor-textarea');
        if (editorsFull.length > 0) {
            editorsFull.forEach(editorElement => {
                if (editorElement) {
                    ClassicEditor.create(editorElement, {
                        toolbar: {
                            items: [
                                'heading', '|',
                                'bold', 'italic', 'link', '|',
                                'bulletedList', 'numberedList', '|',
                                'blockQuote', '|',
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
            // const newTheme = $('body').hasClass('bootstrap-light') ? 'bootstrap-light' : 'bootstrap-light';
            // updateThemePreference(newTheme);
            // updateLogo();
        });

        // Initialisation de CKEditor
        let emailContentEditor;
        const editorElement = document.querySelector('#notification_profile_contenu');

        if (editorElement) {
            ClassicEditor
                .create(editorElement)
                .then(editor => {
                    emailContentEditor = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        }
			
        $('#templateEmail').on('change', function() {
            console.log('change')
            var selectedOption = $(this).find('option:selected');
            var titre = selectedOption.data('titre');
            var contenu = selectedOption.data('contenu');

            $('#notification_profile_titre').val(titre);

            if (emailContentEditor) {
                emailContentEditor.setData(contenu);
            }
        });
    }
    
    function updateThemePreference(theme) {
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
        $('body').removeClass('bootstrap-dark bootstrap-light').addClass(theme);
        const mode = theme.replace('bootstrap-', '');
        $('html').attr('data-bs-theme', mode);
    }
    
    function handleThemeInitialization() {
        const currentTheme = document.cookie.split('; ').find(row => row.startsWith('theme='));
        if (currentTheme) {
            const themeName = currentTheme.split('=')[1];
            $('body').removeClass('bootstrap-dark bootstrap-light').addClass(themeName);
            // const mode = themeName.replace('bootstrap-', '');
            const mode = 'light';
            $('html').attr('data-bs-theme', mode);
            updateLogo();
        }
    }
    
    function updateLogo() {
        const currentTheme = $('body').hasClass('bootstrap-dark') ? 'dark' : 'light';
        // const currentTheme = $('html').attr('data-bs-theme') === 'dark' ? 'dark' : 'light';
        const logoSrc = currentTheme === 'dark' ? '/images/logo-olona-talents-white600x200.png' : '/images/logo-olona-talents-black600x200.png';
        const loginImage = currentTheme === 'dark' ? 'images/login-dark.webp' : 'images/login-light.webp';
        $('#logoOffCanvas').attr('src', logoSrc);
        $('#logo').attr('src', logoSrc);
        $('#login-image').attr('src', loginImage);
    
        const themeIcon = currentTheme === 'dark' ? 'bi-brightness-high' : 'bi-moon-stars-fill';
        $('#switch-theme i').removeClass();
        $('#switch-theme i').addClass(`bi ${themeIcon}`);
    }
    

    function setupImageUpload() {
        const logoInput = document.getElementById('edit_entreprise_file'); 
        const imageInput = document.getElementById('prestation_file'); 
        const profileImgDiv = document.querySelector('.profile-img');
        const companyImgDiv = document.querySelector('.company-img');
    
        // Sécurisation du chargement du logo entreprise
        if (logoInput && companyImgDiv) {
            logoInput.addEventListener('change', function(event) {
                const file = event.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (companyImgDiv) {
                            companyImgDiv.style.backgroundImage = `url(${e.target.result})`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
    
            companyImgDiv.addEventListener('click', function() {
                logoInput.click();
            });
        }
    
        // Sécurisation du chargement de l'image prestation
        if (imageInput && profileImgDiv) {
            imageInput.addEventListener('change', function(event) {
                const file = event.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (profileImgDiv) {
                            profileImgDiv.style.backgroundImage = `url(${e.target.result})`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
    
            profileImgDiv.addEventListener('click', function() {
                imageInput.click();
            });
        }
    
        // Masquer les erreurs de validation à la soumission
        const ids = ['#applyJob', '#createJob']; 
        ids.forEach(function(id) {
            const $form = $(id);
            if ($form.length) {
                $form.on('submit', function(e) {
                    $('.invalid-feedback').remove();
                    $('.is-invalid').removeClass('is-invalid');
    
                    const modalElement = $(this).closest('.modal').get(0); 
                    if (modalElement) {
                        const modal = Modal.getInstance(modalElement) || new Modal(modalElement);
                        modal.hide(); 
                    }
                });
            }
        });        
    
        // Affichage du toast d'erreur à l'étape finale
        const finishMenuItem = $('a[role="menuitem"][href="#finish"]');
        if (finishMenuItem.length) {
            finishMenuItem.on('click', function() {
                const errorToastEl = $('#errorToast')[0];
                if (errorToastEl) {
                    const successToast = new Toast(errorToastEl);
                    setTimeout(() => {
                        successToast.show(); 
                    }, 1000);
                }
            });
        }
    }
    
    
    function setupAvailabilityDropdown() {
        const availabilityDropdown = document.getElementById('prestation_availability_nom');
        const dateField = document.getElementById('prestation_availability_dateFin');
    
        if (availabilityDropdown && dateField) {
            function toggleDateInput() {
                if (!dateField) return;
                if (availabilityDropdown.value === 'from-date') {
                    dateField.style.display = 'block';
                } else {
                    dateField.style.display = 'none';
                }
            }
    
            availabilityDropdown.addEventListener('change', toggleDateInput);
            toggleDateInput(); 
        }
    }

    function setupDynamicLinks() {
        $('#candidates-list').on('click', '.candidate-link', function(event) {
            event.preventDefault();
            var candidateId = $(this).data('id');
            var candidateContent = $('span[data-candidate="' + candidateId + '"]').html();
            $('#candidate-card-container').html(candidateContent);
        });

        $('#joblistings-list').on('click', '.annonce-link', function(event) {
            event.preventDefault();
            var annonceId = $(this).data('id');
            var annonceContent = $('span[data-annonce="' + annonceId + '"]').html();
            $('#candidate-card-container').html(annonceContent);
        });

        $('#prestations-list').on('click', '.prestation-link', function(event) {
            event.preventDefault();
            var prestationId = $(this).data('id');
            var prestationContent = $('span[data-prestation="' + prestationId + '"]').html();
            $('#candidate-card-container').html(prestationContent);
        });

        $(document).on('click', 'button[data-bs-target="#connectingModal"]', function() {
            var href = $(this).data('bs-href'); 
            $.ajax({
                url: '/store/target/path', 
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ href: href }),
                success: function(response) {
                    console.log(response.message);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                }
            });
        });

        $('#contract_typePerson').on('change', function () {
            toggleFields();
        });
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
                    if (data.success) {
                        $('#successToast .toast-body').text(data.message);
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
                    if (data.success) {
                        // $('#successToast').find('.toast-body').text(data.message);
                        // var successToast = new Toast($('#successToast')[0]);
                        // successToast.show();
                        var boostProfileModal = Modal.getInstance($('#boostProfile')[0]) || new Modal($('#boostProfile')[0]);
                        boostProfileModal.hide();
                    } else {
                        // $('#errorToast').find('.toast-body').text('Erreur: ' + data.message);
                        // var errorToast = new Toast($('#errorToast')[0]);
                        // errorToast.show();
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
                    $('#errorToast').find('.toast-body').text('Une erreur est survenue lors de la l\'analyse. Votre CV trop lourd.');
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

  
    // On branche notre comportement à tous les éléments
    function initializeSliders() {
        Array.from(document.querySelectorAll("[data-slider]"))
            .map(el => new Slider(el));
    }
});



class Slider {
    constructor(el) {
        this.el = el;
        this.nextButton = el.querySelector('[data-slider-next]');
        this.prevButton = el.querySelector('[data-slider-prev]');
        this.wrapper = el.querySelector('[data-slider-wrapper]');
        this.interval = null;
        this.currentPage = 0;

        this.nextButton.addEventListener('click', () => {
            this.move(1);
            this.resetAutoplay();
        });

        this.prevButton.addEventListener('click', () => {
            this.move(-1);
            this.resetAutoplay();
        });

        this.updateUI();
        this.startAutoplay();
    }

    get itemsToScroll() {
        return parseInt(getComputedStyle(this.wrapper).getPropertyValue('--items'), 10) || 1;
    }

    get totalItems() {
        return this.wrapper.children.length;
    }

    get pages() {
        return Math.ceil(this.totalItems / this.itemsToScroll);
    }

    move(n) {
        this.currentPage += n;

        if (this.currentPage < 0) this.currentPage = 0;
        if (this.currentPage >= this.pages) this.currentPage = this.pages - 1;

        const targetIndex = this.currentPage * this.itemsToScroll;
        const targetItem = this.wrapper.children[targetIndex];

        if (targetItem) {
            this.wrapper.scrollTo({
                left: targetItem.offsetLeft,
                behavior: 'smooth'
            });
        }

        this.updateUI();
    }

    updateUI() {
        if (this.currentPage === 0) {
            this.prevButton.setAttribute('hidden', 'hidden');
        } else {
            this.prevButton.removeAttribute('hidden');
        }

        if (this.currentPage >= this.pages - 1) {
            this.nextButton.setAttribute('hidden', 'hidden');
        } else {
            this.nextButton.removeAttribute('hidden');
        }
    }

    startAutoplay() {
        this.interval = setInterval(() => {
            // Si on est à la dernière page, stop autoplay
            if (this.currentPage >= this.pages - 1) {
                clearInterval(this.interval);
                return;
            }

            this.currentPage++;

            const targetIndex = this.currentPage * this.itemsToScroll;
            const targetItem = this.wrapper.children[targetIndex];

            if (targetItem) {
                this.wrapper.scrollTo({
                    left: targetItem.offsetLeft,
                    behavior: 'smooth'
                });
            }

            this.updateUI();
        }, 5000);
    }

    resetAutoplay() {
        clearInterval(this.interval);
        this.startAutoplay();
    }
}

