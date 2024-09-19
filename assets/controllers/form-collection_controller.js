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
        console.log(this.addLabelValue, this.addLabelValue)
        btn.innerText = this.addLabelValue || 'Ajouter un élément';
        btn.setAttribute('type', 'button');
        btn.addEventListener('click', this.addElement);

        Array.from(this.element.querySelectorAll('fieldset')).forEach(child => {
            if (!child.dataset.processed) {  
                this.cleanExistingDeleteButtons(child); 
                this.addDeleteButton(child);
                this.addToggleEventListener(child);
                child.dataset.processed = true;  
            }
        });

        this.element.append(btn);  

        this.initializeDatepickers();
    }

    cleanExistingDeleteButtons(element) {
        const existingDeleteButtons = element.querySelectorAll('button.btn-danger');
        existingDeleteButtons.forEach(btn => btn.remove()); // Remove all existing delete buttons
    }

    initializeDatepickers() {
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
        if (!existingBtn) { 
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