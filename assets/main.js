/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';
import { BalloonEditor, AccessibilityHelp, Autosave, BlockToolbar, Bold, Essentials, Italic, Paragraph, SelectAll, Undo } from 'ckeditor5';
import translations from 'ckeditor5/translations/fr.js';
import 'ckeditor5/ckeditor5.css';

const editorConfig = {
	toolbar: {
		items: ['undo', 'redo', '|', 'selectAll', '|', 'bold', 'italic', '|', 'accessibilityHelp'],
		shouldNotGroupWhenFull: false
	},
	plugins: [AccessibilityHelp, Autosave, BlockToolbar, Bold, Essentials, Italic, Paragraph, SelectAll, Undo],
    blockToolbar: ['bold', 'italic'],
    language: 'fr',
	placeholder: 'Tapez ou collez votre contenu ici !',
	translations: [translations]
};

$(function() {
    document.addEventListener('turbo:load', handlePageLoad); // Attacher sur turbo:load pour le chargement initial
    document.addEventListener('turbo:frame-load', handleFrameLoad); // Attacher sur turbo:frame-load pour les chargements dans les frames

    function handleFrameLoad(event) {
        const context = event ? event.target : document;
        setupEditors(event); 
        setupImageUpload(); 
        setupAvailabilityDropdown(); 
        applyBootstrapStylesToBalloonEditors(context);
    }

    function handlePageLoad() {
        handleThemeChange();
        handleThemeInitialization();
        initializeBalloonEditor(); // Setup Balloon Editor on initial load
        updateLogo();
        setupDeletionConfirmation();
        setupImageUpload(); // Setup Image Upload on initial load
        setupAvailabilityDropdown(); // Setup Availability Dropdown on initial load
    }

    function setupEditors(event) {
        const context = event ? event.target : document;
        const editors = context.querySelectorAll('.balloon-editor');
        
        editors.forEach(editorElement => {
            const initialContent = editorElement.getAttribute('data-content') || ''; // Définir par défaut à une chaîne vide
            const hiddenInputSelector = editorElement.getAttribute('data-hidden-input-selector');
            const hiddenInput = document.querySelector(hiddenInputSelector);

            if (editorElement.getAttribute('data-editor-initialized') !== 'true') {
                BalloonEditor.create(editorElement, editorConfig)
                .then(editor => {
                    if (initialContent) {
                        editor.setData(htmlDecode(initialContent));
                    }
                    editor.model.document.on('change:data', () => {
                        if(hiddenInput){
                            hiddenInput.value = editor.getData();
                        }
                    });

                    editorElement.setAttribute('data-editor-initialized', 'true');
                })
                .catch(error => {
                    console.error('Error initializing Balloon Editor:', error);
                });
            }
        });
    }

    function htmlDecode(input){
        const doc = new DOMParser().parseFromString(input, "text/html");
        return doc.documentElement.innerHTML;
    }

    function initializeBalloonEditor() {
        const editorElement = document.querySelector('.balloon-editor');
        const hiddenInput = document.querySelector('input[name="prestation[description]"]');
        if (editorElement) {
            let initialContent = editorElement.getAttribute('data-content');
            initialContent = htmlDecode(initialContent);  // Décode les entités HTML
    
            BalloonEditor.create(editorElement, editorConfig)
                .then(editor => {
                    editor.setData(initialContent);
                    editor.model.document.on('change:data', () => {
                        if (hiddenInput) {
                            hiddenInput.value = editor.getData();
                        }
                    });
                })
                .catch(error => {
                    console.error('There was a problem initializing the Balloon Editor:', error);
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

    function applyBootstrapStylesToBalloonEditors(context) {
        const editors = context.querySelectorAll('.balloon-editor');
        editors.forEach(editor => {
            editor.classList.add('balloon-editor-bootstrap');
        });
    }
});