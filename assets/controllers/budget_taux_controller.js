// job_listing_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["devise", "taux"];
    
    connect() {
        this.deviseTargets.forEach(deviseSelect => {
            console.log(deviseSelect)
            deviseSelect.addEventListener('change', this.onDeviseChange.bind(this));
        });
    }

    onDeviseChange(event) {
        const devise = event.target.value;
        const tauxElement = document.querySelector('[data-id="budgetAnnonce_taux"]');
        const symboleElement = document.querySelector('[data-id="budgetAnnonce_symbole"]');
        fetch('/ajax/devise/select/' + devise)
        .then(response => response.json())
        .then(data => {
            tauxElement.value = data.taux;
            symboleElement.value = data.symbole;
        });
    }
}
