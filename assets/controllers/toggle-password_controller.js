import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'icon'];

    toggle() {
        this.inputTargets.forEach(input => {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
        });

        this.iconTargets.forEach(icon => {
            const eyeIcon = icon.querySelector('i') 
            eyeIcon.classList.toggle('bi-eye')
            eyeIcon.classList.toggle('bi-eye-slash')
        })
    }
}
