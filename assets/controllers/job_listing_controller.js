// job_listing_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["entreprise", "jobListing"];

    connect() {
        this.entrepriseTargets.forEach(entrepriseSelect => {
            console.log(entrepriseSelect)
            entrepriseSelect.addEventListener('change', this.onEntrepriseChange.bind(this));
        });
    }

    onEntrepriseChange(event) {
        const entrepriseSelect = event.target;
        const parentDiv = entrepriseSelect.parentElement; // La div du premier select
        const nextDiv = parentDiv.nextElementSibling; // La div du deuxième select
        const jobListingSelect = nextDiv.querySelector('select'); // Le deuxième select
        
        const entrepriseId = entrepriseSelect.value;

        fetch('/ajax/entreprise/select/' + entrepriseId)
            .then(response => response.json())
            .then(data => {
                if (jobListingSelect) {
                    jobListingSelect.innerHTML = '';

                    const annonces = Array.isArray(data) ? data : Object.values(data);
                    annonces.forEach(jobListing => {
                        const option = new Option(jobListing.titre, jobListing.id);
                        jobListingSelect.append(option);
                    });
                }
            });
    }
}
