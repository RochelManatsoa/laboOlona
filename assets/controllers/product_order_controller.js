// job_listing_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["product", "quantity"];
    
    connect() {
        console.log(this.productTargets)
        this.productTargets.forEach(productSelect => {
            productSelect.addEventListener('change', this.onProductChange.bind(this));
        });
    }

    onProductChange(event) {
        const product = event.target.value;
        const id = event.target.id;
        const productPrice = document.getElementById(id.replace("product", "price"));
        const totalAmount = document.getElementById("order_totalAmount");
        // Votre code pour récupérer le taux de change de la devise
        fetch('/ajax/product/select/' + product)
        .then(response => response.json())
        .then(data => {
            productPrice.value = data.price;
        });
    }
}
