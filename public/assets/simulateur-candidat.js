if (typeof $ !== 'undefined') {
    $(function () {
        initFormStep($("#example-basic"), "h3", "section", "fade", $('#example-basic').data('can-apply'));

        // Sélectionner le champ select
        var selectType = $('#simulateur_type');

        // Sélectionner les divs à afficher ou cacher
        var divEmploye = $('#employe');
        var divFreelance = $('#freelance');

        // Ajouter un gestionnaire d'événement pour le changement de valeur dans le champ select
        selectType.on('change', function() {
            console.log($(this).val())
            // Vérifier la valeur sélectionnée
            if ($(this).val() === 'OLONA') {
                // Afficher la div pour Employé et cacher la div pour Freelance
                console.log("hide frelance")
                divEmploye.show();
                divFreelance.hide();
            } else if ($(this).val() === 'TELETRAVAIL') {
                // Afficher la div pour Freelance et cacher la div pour Employé
                console.log("hide employe")
                divEmploye.hide();
                divFreelance.show();
            }
        });


        // Écoute des changements sur les boutons radio et mise à jour de l'explication
        $('input[name="simulateur[status]"]').change(function() {
            updateExplanation(this.value);
        });

        // Mise à jour initiale pour la sélection par défaut
        updateExplanation($('input[name="simulateur[status]"]:checked').val());
    })
} else {
    console.error('jQuery not loaded');
}

function updateExplanation(value) {
    var explanation = '';
    if(value === 'PORTAGE') {
        explanation = '<i class="mx-2 h5 bi bi-info-circle text-primary"></i> En choisissant le Portage salarial, vous bénéficiez de la couverture sociale d\'un salarié avec les charges salariales et patronales correspondantes, tout en conservant une certaine flexibilité dans la gestion de vos missions.';
    } else if(value === 'FREELANCE') {
        explanation = '<i class="mx-2 h5 bi bi-info-circle text-primary"></i> Opter pour le statut Freelance implique une charge de 5% sur vos revenus, offrant une plus grande autonomie dans la gestion de vos activités sans les charges salariales et patronales typiques d\'un salarié.';
    }
    $('#status-explanation').html(explanation);
}

// Fonction pour vérifier si l'un des champs est vide
function checkFields() {
    var isEmpty = false;
    $('input[id^="simulateur_employe_user_"]').each(function() {
        if ($(this).val() === '') {
            isEmpty = true;
            return false; // Sortir de la boucle si un champ est vide
        }
    });
    return isEmpty;
}