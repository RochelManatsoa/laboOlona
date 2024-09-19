import $ from 'jquery';

$(document).ready(function() {
    $('#viewModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        
        $.get('/details/annonce/' + id, function(data) {
            $('#details-titre').text(data.titre);
            $('#details-description').text(data.description);
            $('#details-date-creation').text(data.dateCreation);
            $('#details-date-expiration').text(data.dateExpiration);
            $('#details-status').text(data.status);
            $('#details-salaire').text(data.salaire);
            $('#details-lieu').text(data.lieu);
            $('#details-type-contrat').text(data.typeContrat);
            $('#details-entreprise').text(data.entreprise);
        });
    });
});