// assets/controllers/form-collection_controller.js
import { Controller } from '@hotwired/stimulus';
import 'jquery-ui/ui/widgets/datepicker';

export default class extends Controller {
    static targets = ["collectionContainer", "endDate", "currentlyCheckbox"]

    static values = {
        addLabel: String,
        deleteLabel: String
    }

    connect() {
        this.index = this.element.querySelectorAll('fieldset').length;
        console.log("Initial index:", this.index);

        const btn = document.createElement('button');
        btn.setAttribute('class', 'btn btn-secondary my-2 rounded-pill px-5');
        btn.innerText = this.addLabelValue || 'Ajouter un élément';
        btn.setAttribute('type', 'button');
        btn.addEventListener('click', this.addElement);

        Array.from(this.element.querySelectorAll('fieldset')).forEach(child => {
            if (!child.dataset.processed) {  // évite la duplication du bouton supprimer
                this.cleanExistingDeleteButtons(child); // clean up existing delete buttons
                this.addDeleteButton(child);
                this.addToggleEventListener(child);
                child.dataset.processed = true;  // marque cet élément comme traité
            }
        });

        this.element.append(btn);  // ajouter le bouton après le traitement des enfants

        // Initialiser la datepicker pour les éléments existants
        this.initializeDatepickers();
    }

    cleanExistingDeleteButtons(element) {
        const existingDeleteButtons = element.querySelectorAll('button.btn-danger');
        existingDeleteButtons.forEach(btn => btn.remove()); // Remove all existing delete buttons
    }

    initializeDatepickers() {
        // Utiliser jQuery pour initialiser les datepickers
        console.log("Initialization Datepicker Called");
        if (typeof $.fn.datepicker === 'function') {
            console.log("Initializing datepickers for .datepicker elements");
            $('.datepicker').datepicker({
                dateFormat: 'dd-mm-yy'
            });
        } else {
            console.log("Datepicker function not found");
        }
    }

    addToggleEventListener(element) {
        const currentlyCheckbox = element.querySelector('[data-form-collection-target="currentlyCheckbox"]');
        const endDateField = element.querySelector('[data-form-collection-target="endDate"]');
        if (currentlyCheckbox && endDateField) {
            currentlyCheckbox.addEventListener('change', () => this.toggleEndDateField(currentlyCheckbox, endDateField));
            this.toggleEndDateField(currentlyCheckbox, endDateField);  // Appel pour définir l'état initial
        }
    }

    toggleEndDateField(currentlyCheckbox, endDateField) {
        const endDateFieldParent = endDateField.parentNode;

        if (currentlyCheckbox.checked) {
            endDateFieldParent.style.display = 'none';
        } else {
            endDateFieldParent.style.display = 'block';
        }
    }

    /**
     * 
     * @param {MouseEvent} e
     */
    addElement = (e) => {
        e.preventDefault();
        const element = document.createRange().createContextualFragment(
            this.element.dataset['prototype'].replaceAll('__name__', this.index)
        ).firstElementChild;
        this.cleanExistingDeleteButtons(element); // clean up existing delete buttons
        this.addDeleteButton(element);
        this.addToggleEventListener(element);
        this.index++;
        const textArea = element.querySelector('.ckeditor-textarea');
        if (textArea) {
            ClassicEditor.create(textArea, {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'link', '|',
                        'bulletedList', 'numberedList', '|',
                        'blockQuote', '|',
                        'undo', 'redo'
                    ]
                }
            });
        }
        e.currentTarget.insertAdjacentElement('beforebegin', element);

        // Initialiser la datepicker pour le nouvel élément
        this.initializeDatepickers();
    }

    /**
     * 
     * @param {HTMLElement} item
     */
    addDeleteButton = (item) => {
        const existingBtn = item.querySelector('.btn-danger');
        if (!existingBtn) { // vérifie s'il n'y a pas déjà un bouton de suppression
            const btn = document.createElement('button');
            btn.setAttribute('class', 'btn btn-danger my-2 rounded-pill px-5 float-end');
            btn.innerText = this.deleteLabelValue || 'Supprimer';
            btn.setAttribute('type', 'button');
            item.append(btn);
            btn.addEventListener('click', e => {
                e.preventDefault();
                item.remove();
            });
        }
    }
}
    // addCollectionElement(event)
    // {
    //     event.preventDefault();
    //     const item = document.createElement('li');
    //     item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
    //     this.collectionContainerTarget.appendChild(item);
    //     this.indexValue++;

    //     // Initialisez CKEditor pour les champs de texte nouvellement ajoutés
    //     const textArea = item.querySelector('.ckeditor-textarea'); // Remplacez '.ckeditor-textarea' par la classe appropriée de votre champ de texte
    //     if (textArea) {
    //         ClassicEditor.create(textArea, {
    //             toolbar: {
    //                 items: [
    //                     'heading', '|',
    //                     'bold', 'italic', 'link', '|',
    //                     'bulletedList', 'numberedList', '|',
    //                     'blockQuote', '|',
    //                     'undo', 'redo'
    //                 ]
    //             }
    //         });
    //     }
    // }

    // handleFormSubmission(event) {
    //     event.preventDefault();
    //     const form = event.target;
    //     console.log(form)

    //     fetch(form.action, {
    //         method: 'POST',
    //         body: new FormData(form),
    //         headers: {
    //             'X-Requested-With': 'XMLHttpRequest',
    //         },
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.redirect_url) {
    //             window.location.href = data.redirect_url;
    //         } else if (data.errors) {
    //             // Gérer les erreurs si nécessaire
    //             console.error(data.errors);
    //         }
    //     })
    //     .catch(error => console.error('Erreur:', error));
    // }

    // connect() {
    //     this.element.querySelectorAll('form').forEach(form => {
    //         form.addEventListener('submit', this.handleFormSubmission.bind(this));
    //     });
    // }
// }