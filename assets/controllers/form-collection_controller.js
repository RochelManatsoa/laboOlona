// assets/controllers/form-collection_controller.js
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"]

    static values = {
        index    : Number,
        prototype: String,
    }

    addCollectionElement(event)
    {
        const item = document.createElement('li');
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.appendChild(item);
        this.indexValue++;

        // Initialisez CKEditor pour les champs de texte nouvellement ajoutés
        const textArea = item.querySelector('.ckeditor-textarea'); // Remplacez '.ckeditor-textarea' par la classe appropriée de votre champ de texte
        if (textArea) {
            ClassicEditor.create(textArea);
        }
    }
}