/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';

$(function() {
    document.addEventListener('turbo:load', handlePageLoad); // Attacher sur turbo:load pour le chargement initial
    document.addEventListener('turbo:frame-load', handleFrameLoad); // Attacher sur turbo:frame-load pour les chargements dans les frames

    function handleFrameLoad(event) {
        const context = event ? event.target : document;
        setupEditors(event); 
        setupImageUpload(); 
        setupAvailabilityDropdown(); 
    }

    function handlePageLoad() {
        handleThemeChange();
        handleThemeInitialization();
        setupCKEditors();
        updateLogo();
        setupDeletionConfirmation();
        setupImageUpload(); 
        setupAvailabilityDropdown();  
    }

    function setupEditors(event) {
        const context = event ? event.target : document;
        const editors = context.querySelectorAll('.ckeditor-textarea');
        
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
                console.log('ckeditor turbo-frame')
            }
        });
    }

    function setupCKEditors() {
        const editors = document.querySelectorAll('.ckeditor-textarea');
        
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
                console.log('ckeditor document-load')
            }
        });
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

        // Update switch theme icon
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