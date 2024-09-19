import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"]

    static values = {
        index    : Number,
        prototype: String,
    }

    connect() {
        this.attachChangeEventToAll();
    }

    addCollectionElement(event) {
        const item = document.createElement('li');
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.appendChild(item);
        this.indexValue++;

        // Attacher l'événement 'change' aux nouveaux éléments d'entreprise
        this.attachChangeEventToElement(item);

        // Initialisation de CKEditor ou d'autres scripts si nécessaire
    }

    attachChangeEventToAll() {
        this.collectionContainerTarget.querySelectorAll('[id$="_entreprise"]').forEach(element => {
            this.attachChangeEventToElement(element);
        });
    }

    attachChangeEventToElement(element) {
        const entrepriseSelect = element.querySelector('[id$="_entreprise"]');
        if (entrepriseSelect) {
            entrepriseSelect.addEventListener('change', (event) => {
                // Logique à exécuter lors de la modification de la sélection
                // Par exemple, mise à jour du champ jobListing
            });
        }
    }
}
