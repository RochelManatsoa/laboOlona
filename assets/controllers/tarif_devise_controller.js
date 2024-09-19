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
        const symbole = document.querySelector('[data-id="tarif_symbole"]');
        // var spans = document.querySelectorAll('span.change-devise');
    
        // Votre code pour récupérer le taux de change de la devise
        fetch('/ajax/devise/select/' + devise)
        .then(response => response.json())
        .then(data => {
            console.log(data)    
            symbole.value = data.symbole;
        });
    }
}
