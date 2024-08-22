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
    }

    function setupCKEditors() {
        if (typeof ClassicEditor !== 'undefined') {
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
                        });
                    }
                });
            }
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
        $('#logo').attr('src', logoSrc);

        const themeIcon = currentTheme === 'dark' ? 'bi-brightness-high' : 'bi-moon-stars-fill';
        $('#switch-theme i').removeClass();
        $('#switch-theme i').addClass(`bi ${themeIcon}`);
    }

    function setupImageUpload() {
        const imageInput = document.getElementById('prestation_file'); 
        const profileImgDiv = document.querySelector('.profile-img');
    
        if (imageInput && profileImgDiv) {
            imageInput.addEventListener('change', function(event) {
                if (event.target.files && event.target.files[0]) {
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
        $('#contactDetails').on('click', function() {
            var errorToast = $('#errorToast');
            var toast = new Toast(errorToast[0]); 
            setTimeout(function() {
                toast.show();
            }, 1500);
        });
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
                    if (data.status === 'success') {
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
    
        $('.add-to-favorites').on('click', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            $.ajax({
                url: url,
                type: 'POST',
                success: function(data) {
                    if (data.status === 'success') {
                        $('#successToast').find('.toast-body').text(data.message);
                        var successToast = new Toast($('#successToast')[0]);
                        successToast.show();
                    } else {
                        $('#errorToast').find('.toast-body').text(data.message);
                        var errorToast = new Toast($('#errorToast')[0]);
                        errorToast.show();
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
            toggleDateInput(); // Initial state
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

    $('#experience').on('shown.bs.modal', function () {
        // Fonction pour gérer la logique de chaque groupe de champs
        function handleFieldGroup(baseId) {
            for (let i = 0; i < 10; i++) {  // Ajustez le nombre selon vos besoins
                const $currentlyCheckbox = $(`#${baseId}_${i}_enPoste`);
                const $endDateFieldContainer = $(`#${baseId}_${i}_dateFin`).closest('div');
    
                if (!$currentlyCheckbox.length) {
                    break; // Sortir si le checkbox n'existe pas
                }
    
                // Afficher ou masquer le conteneur en fonction de l'état du checkbox
                $endDateFieldContainer.parent().toggle(!$currentlyCheckbox.is(':checked'));
    
                // Gérer les changements d'état du checkbox
                $currentlyCheckbox.off('change').change(function() {
                    $endDateFieldContainer.parent().toggle(!$(this).is(':checked'));
                });
            }
        }
    
        // Appeler la fonction pour chaque groupe de champs
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