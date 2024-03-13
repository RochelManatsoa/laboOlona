
function initFormStep(form, title, bodyTag, transitionEffect, connected) {
    var form = form.show()
    form
      .steps({
        headerTag: 'h3',
        bodyTag: 'section',
        transitionEffect: 'fade',
        titleTemplate: '<span class="number"></span> #title#',
        labels: {
          current: 'current step:',
          pagination: 'Pagination',
          finish: 'Résultat',
          next: 'Suivant',
          previous: 'Précédent',
          loading: 'Chargement ...',
        },
        onStepChanging: function (event, currentIndex, newIndex) {
          // second step
          // if (currentIndex == 1 && newIndex == 2) {
          //     return checkSecondStep();
          // }
          // Allways allow previous action even if the current form is not valid!
          if (currentIndex > newIndex) {
            return true
          }
  
          // Needed in some cases if the user went back (clean up)
          if (currentIndex < newIndex) {
            // To remove error styles
            form.find('.body:eq(' + newIndex + ') label.error').remove()
            form.find('.body:eq(' + newIndex + ') .error').removeClass('error')
          }
          form.validate().settings.ignore = ':disabled,:hidden'
          return form.valid()
        },
        onStepChanged: function (event, currentIndex, priorIndex) {
          // Used to skip the "Warning" step if the user is old enough.
          if (currentIndex === 3) {
            let data = $('#example-basic').serializeArray().map(function(item) {
              item.label = $(`[name="${item.name}"]`).attr('data-unit');
              return item;
            })
            let resum = $('#resum')
            let html = ''
            // Définition de la fonction getDevise
              function getDevise(id, callback) {
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `/ajax/get/simulateur/${id}`);
                xhr.responseType = 'json';
                
                xhr.onload = function() {
                  if (xhr.status === 200) {
                    const data = xhr.response;
                    // Accéder aux données de la devise
                    const nom = data.devise.nom;
                    const symbole = data.devise.symbole;
                    const taux = data.devise.taux;
                    
                    // Appeler le callback avec les données de la devise
                    callback(nom, symbole, taux);
                  } else {
                    console.error('Une erreur est survenue lors de la requête.');
                  }
                };
                
                xhr.send();
              }

              data.forEach((element) => {
                let name = element.name;
                let value = getElementValue(element);

                let label = {
                  'simulateur[deviseSymbole]': 'Devise choisie',
                  'simulateur[taux]': 'Taux de change',
                  'simulateur[salaireNet]': 'Salaire net souhaité',
                  'simulateur[nombreEnfant]': 'Nombre d\'enfant',
                  'simulateur[type]': 'Ma situation',
                  'simulateur[prixRepas]': 'Prix repas journalier',
                  'simulateur[jourRepas]': 'Nombre de jour (Repas)',
                  'simulateur[prixDeplacement]': 'Prix deplacement journalier',
                  'simulateur[jourDeplacement]': 'Nombre de jour (Déplacement)',
                  'simulateur[avantage][primeConnexion]': 'Connexion Internet',
                  'simulateur[avantage][primeFonction]': 'Autres',
                }

                let field = [
                  'simulateur[deviseSymbole]',
                  'simulateur[taux]',
                  'simulateur[salaireNet]',
                  'simulateur[nombreEnfant]',
                  'simulateur[type]',
                  'simulateur[jourRepas]',
                  'simulateur[prixRepas]',
                  'simulateur[jourDeplacement]',
                  'simulateur[prixDeplacement]',
                  'simulateur[avantage][primeConnexion]',
                  'simulateur[avantage][primeFonction]',
                ]
                
                if (0 <= $.inArray(name, field)) {
                  // Si la valeur est une promesse, attendez qu'elle soit résolue avant de traiter
                    html = html.concat(
                      '<div class="container"><div class="row border"><div class="col-md-6 right text-white bg-success">' +
                        '<strong class="">' +
                        label[name] +
                        ' </strong></div><div class="col-md-6 text-white bg-info"">' +
                        ' <span class=""> ' +
                        value +
                        '</span></div>' +
                        '</div></div></div>',
                    );
                }
                resum.html(
                  html.concat(
                    "<div></div>",
                  ),
                )
              });

              function getElementValue(element) {
                if (element.name === 'simulateur[taux]') {
                    // Retourner une promesse pour les valeurs de devise
                    return element.value + ' Ar';
                }
                return element.value !== "" ? element.value : '-';
              }

          }
        },
        onFinishing: function (event, currentIndex) {
          form.validate().settings.ignore = ':disabled'
          return form.valid()
        },
        onFinished: function (event, currentIndex) {
          if (!connected) {
            // Si connected est faux, affichez un popup
            $('#popup').click();
            return false; // Empêche la soumission du formulaire
          }
          form.submit()
        },
      })
      .validate({
        errorPlacement: function(error, element) {
            // Créer un élément div pour le message d'erreur
            var errorDiv = document.createElement('div');
            errorDiv.classList.add('invalid-feedback', 'd-block');
        
            // Ajouter le message d'erreur à cet élément div
            errorDiv.appendChild(error[0]);
        
            // Insérer l'élément div avec le message d'erreur après l'élément parent du champ de formulaire
            element.closest('.input-group').append(errorDiv);
        },
        rules: {
          'simulateur[taux]': {
            required: true,
            number: true,
          },
          'simulateur[salaireNet]': {
            required: true,
            number: true,
          },
          'simulateur[avantage][primeFonction]': {
            number: true,
          },
          'simulateur[avantage][primeConnexion]': {
            number: true,
          },
          'simulateur[avantage][repas]': {
            number: true,
          },
          'simulateur[avantage][deplacement]': {
            number: true,
          },
          'simulateur[prixRepas]': {
            required: false,
            number: true,
          },
          'simulateur[prixDeplacement]': {
            required: false,
            number: true,
          },
        },
        messages: {
          'simulateur[taux]': {
            required: 'Champs obligatoire',
            number: 'Le taux doit être un nombre décimal',
          },
          'simulateur[salaireNet]': {
            required: 'Champs obligatoire',
            number: 'Le montant doit être un nombre décimal',
          },
          'simulateur[avantage][primeFonction]': {
            number: 'Le montant doit être en chiffres',
          },
          'simulateur[avantage][primeConnexion]': {
            number: 'Le montant doit être en chiffres',
          },
          'simulateur[avantage][repas]': {
            number: 'Le montant doit être un nombre décimal',
          },
          'simulateur[avantage][deplacement]': {
            number: 'Le montant doit être un nombre décimal',
          },
          'simulateur[prixRepas]': {
            number: 'Le montant doit être un nombre décimal',
          },
          'simulateur[prixDeplacement]': {
            number: 'Le montant doit être un nombre décimal',
          },
        },
      })
  }

