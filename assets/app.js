/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
// import './styles/app.scss';
// import 'bootstrap';
// import './bootstrap.js';
// import 'bootstrap-icons/font/bootstrap-icons.css';
// import $ from 'jquery';

import $ from 'jquery';
import { Tooltip, Toast, Popover } from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import './styles/app.scss'; // Assurez-vous que ce chemin est correct selon votre structure de projet

// Import Swup
import Swup from 'swup';
import SwupFadeTheme from '@swup/fade-theme';
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Initializes the Stimulus application
const app = startStimulusApp();

// Initialize Swup
const swup = new Swup({
    plugins: [new SwupFadeTheme()]
});
import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';

// Attendez que le DOM soit entièrement chargé
document.addEventListener("DOMContentLoaded", function() {
    // Sélectionnez tous les champs avec l'attribut data-role="tagify"
    const tagifyElems = document.querySelectorAll('input[data-role=tagify]');

    // Appliquez Tagify à chaque élément sélectionné
    tagifyElems.forEach(function(elem) {
        new Tagify(elem);
    });
});
// Function to activate the tab from URL
function activateTabFromUrl() {
    // Obtenir les paramètres de l'URL
    var urlParams = new URLSearchParams(window.location.search);
    var selectedTab = urlParams.get('tab');

    if (selectedTab) {
        // Désactiver l'onglet actif par défaut
        console.log(selectedTab)
        $('.tab-pane').removeClass('show active');
        $('.nav-link').removeClass('active');

        // Trouver le lien du tab correspondant
        var tabLink = $('a.nav-link[href="#' + selectedTab + '"]');

        if (tabLink.length) {
            // Activer le tab correspondant
            var tab = new bootstrap.Tab(tabLink[0]);
            tab.show();
        }
    }
}

$(function() {
    // Function to update logo based on theme
    function updateLogo() {
        const currentTheme = $('body').hasClass('bootstrap-dark') ? 'dark' : 'light';
        const logoSrc = currentTheme === 'dark' ? '/images/logo-olona-talents-white600x200.png' : '/images/logo-olona-talents-black600x200.png';
        $('#logo').attr('src', logoSrc);

        // Update switch theme icon
        const themeIcon = currentTheme === 'dark' ? 'bi-brightness-high' : 'bi-moon-stars-fill';
        $('#switch-theme i').removeClass();
        $('#switch-theme i').addClass(`bi ${themeIcon}`);
    }

    $('#switch-theme').on('click', function() {
        $('body').toggleClass('bootstrap-dark');
        $('body').toggleClass('bootstrap-light');

        // Enregistrer la préférence de l'utilisateur dans le localStorage
        const currentTheme = $('body').hasClass('bootstrap-dark') ? 'bootstrap-dark' : 'bootstrap-light';
        localStorage.setItem('theme', currentTheme);

        // Update logo
        updateLogo();
    });

    // Charger le thème enregistré par l'utilisateur
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        $('body').removeClass('bootstrap-dark bootstrap-light').addClass(savedTheme);
    }

    // Update logo and icon on initial load
    updateLogo();

    // Activer l'onglet correct au chargement initial
    activateTabFromUrl();

    // Écouter les transitions de Swup
    document.addEventListener('swup:contentReplaced', function() {
        activateTabFromUrl();
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
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

    $('#account_identity .custom-control-input').on('change', function() {
        $(this).closest('form').submit();
    });

    $('.image-checkbox img').on('click', function() {
        // Ajouter l'effet de clignotement
        $(this).addClass('blinking');
        
        // Retirer l'effet de clignotement après 1.2 secondes (2 cycles d'animation)
        setTimeout(() => {
            $(this).removeClass('blinking');
        }, 900);
    });
    
    let offset = 20; // Comme les 20 premiers sont déjà affichés
let isLoading = false;

$(window).on('scroll', function() {
    const threshold = 1;
    const position = $(window).scrollTop() + $(window).height();
    const height = $(document).height();

    // Vérifier si le nombre total de candidats chargés est inférieur à 50
    if (position >= height - threshold && offset < 50 && !isLoading) {
        isLoading = true; // Activer le verrou

        $.ajax({
            url: `/ajax/candidat?offset=${offset}`,
            type: 'GET',
            success: function(response) {
                if (response) {
                    const $produitItemDiv = $('#candidates .expert-item');
                    if ($produitItemDiv.length) {
                        $produitItemDiv.append(response.html);
                        offset += 10; // Incrémente pour le prochain lot
                    }
                    isLoading = false; // Libérer le verrou
                }
            },
            error: function(error) {
                console.error('Une erreur est survenue:', error);
                isLoading = false; // Libérer le verrou en cas d'erreur
            }
        });
    }
});


    $('#previewButton').on('click', function(e) {
        e.preventDefault();
        const typeText = $('select[name="annonce[typeContrat]"] option:selected').text();
        const sectorText = $('select[name="annonce[secteur]"] option:selected').text();
        const descriptionContent = globalEditorInstance.getData();
        // Récupérer les données du formulaire
        const formData = {
            titre: $('input[name="annonce[titre]"]').val(),
            description: descriptionContent,
            salaire: $('input[name="annonce[salaire]"]').val(),
            budget: $('select[name="annonce[budgetAnnonce][typeBudget]"] option:selected').text(),
            devise: $('input[name="annonce[budgetAnnonce][devise]"]').val(),
            montant: $('input[name="annonce[budgetAnnonce][montant]"]').val(),
            nombrePoste: $('input[name="annonce[nombrePoste]"]').val(),
            dateExpiration: $('input[name="annonce[dateExpiration]"]').val(),
        };
        // Créer un tableau pour stocker les valeurs
        var values = [];

        // Sélectionner tous les éléments avec la classe 'item' et itérer sur chacun
        $('.ts-control .item').each(function() {
            // Récupérer le texte de l'élément, qui est la valeur souhaitée
            var value = $(this).text().trim().replace('×', '');

            // Ajouter la valeur au tableau
            values.push(value);
        });

        const content = `
        <div class="container">
            <div class="row">
                <!-- Colonne pour la description -->
                <div class="col-md-6">
                <p><span class="text-strong">Titre :</span> <br>${formData.titre}</p>
                <p><span class="text-strong">Type :</span> <br>${typeText}</p>
                <p><span class="text-strong">Secteur d'activité :</span> <br>${sectorText}</p>
                <p><span class="text-strong">Budget : ${formData.budget} </span> <br>${formData.montant} ${formData.devise}</p>
                <!-- Et ainsi de suite pour les autres champs... -->
                </div>
                <!-- Colonne pour la liste des éléments dans values -->
                <div class="col-md-6">
                <p><span class="text-strong">Nombre de personne à chercher :</span> <br>${formData.nombrePoste}</p>
                <p><span class="text-strong">Date du début :</span> <br>${formData.dateExpiration} </p>
                <p><span class="text-strong">Comptétences requises :</span></p>
                <ul>
                    ${values.map(value => `<li>${value}</li>`).join('')}
                </ul>
                </div>
            </div>
            <div class="row">
                <p><span class="text-strong">Description du poste:</span> <br>${formData.description}</p>
            </div>
        </div>

        `;
        $('#previewModal .modal-body').html(content);
    
    });
});