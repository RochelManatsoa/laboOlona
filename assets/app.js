/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/app.scss'; 
import 'bootstrap';
import './bootstrap.js';
import { Tooltip, Toast, Popover, Modal } from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
require('tom-select/dist/css/tom-select.css');

document.addEventListener('DOMContentLoaded', function() {
    var formElement = document.getElementById('boostProfileForm');
    if (formElement) {  
        formElement.addEventListener('submit', function(e) {
            e.preventDefault(); 

            var formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest', 
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    var successToast = new Toast(document.getElementById('successToast'));
                    successToast.show();
                    var modalEl = document.getElementById('boostProfile');
                    var boostProfileModal = Modal.getInstance(modalEl) || new Modal(modalEl);
                    boostProfileModal.hide();
                } else {
                    var errorToast = document.getElementById('errorToast');
                    errorToast.querySelector('.toast-body').textContent = 'Erreur: ' + data.message;
                    var toast = new Toast(errorToast);
                    toast.show();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                var errorToast = document.getElementById('errorToast');
                errorToast.querySelector('.toast-body').textContent = 'Une erreur est survenue lors de la tentative de boost de votre profil.';
                var toast = new Toast(errorToast);
                toast.show();
            });
        });
    }

    var buttons = document.querySelectorAll('.add-to-favorites');
    if (buttons.length > 0) {
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); 
                var url = button.getAttribute('data-href'); 

                fetch(url, {
                    method: 'POST',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        var successToast = new Toast(document.getElementById('successToast'));
                        errorToast.querySelector('.toast-body').textContent = data.message;
                        successToast.show();
                    } else {
                        var errorToast = document.getElementById('errorToast');
                        errorToast.querySelector('.toast-body').textContent = 'Erreur: ' + data.message;
                        var toast = new Toast(errorToast);
                        toast.show();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    var errorToast = document.getElementById('errorToast');
                    errorToast.querySelector('.toast-body').textContent = 'Une erreur est survenue lors de l\'ajout du candidat dans vos favoris.';
                    var toast = new Toast(errorToast);
                    toast.show();
                });
            });
        });
    } 
});

