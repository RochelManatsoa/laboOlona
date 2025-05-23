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
        const tauxElement = document.querySelector('[data-id="simulateur_taux"]');
        const symbole = document.querySelector('[data-id="simulateur_deviseSymbole"]');
        var spans = document.querySelectorAll('span.change-devise');
        console.log(spans)
        // Votre code pour récupérer le taux de change de la devise
        fetch('/ajax/devise/select/' + devise)
        .then(response => response.json())
        .then(data => {
            console.log(data)    
            tauxElement.value = data.taux;
            symbole.value = data.symbole;
            spans.forEach(function(span) {
                span.textContent = data.symbole; // Vous pouvez également utiliser span.innerHTML = 'Nouveau contenu'; si vous avez du contenu HTML à insérer
            });
        });
    }
}
