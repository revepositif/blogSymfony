import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import '@hotwired/turbo';

// Désactiver la validation par défaut de Turbo pour les formulaires
document.addEventListener("turbo:before-fetch-request", (event) => {
    if (event.detail.fetchOptions.method === "POST") {
        event.detail.fetchOptions.headers["Accept"] = "text/html, application/xhtml+xml";
    }
});
document.addEventListener("turbo:submit-start", (event) => {
    const form = event.target;
    
    // Désactiver les boutons du formulaire pendant la soumission
    form.querySelectorAll("button, input[type='submit']").forEach(button => {
        button.disabled = true;
    });
});

document.addEventListener("turbo:submit-end", (event) => {
    const form = event.target;
    
    // Réactiver les boutons après la soumission
    form.querySelectorAll("button, input[type='submit']").forEach(button => {
        button.disabled = false;
    });
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
