
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
          finish: 'Résultat (10 Crédits)',
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
          if (currentIndex === 4) {
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
                  'simulateur[status]': 'Mon statut',
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
                  'simulateur[status]',
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
          console.log(connected)
          if (!connected) {
            // Si connected est faux, affichez un popup
            $('#popup').trigger("click");
            return false; // Empêche la soumission du formulaire
          }
          var formData = new FormData(form[0]);
          var actionUrl = form.data('action');
          $.ajax({
              url: actionUrl,
              type: 'POST',
              data: formData,
              contentType: false,
              processData: false,
              headers: {
                  'Accept': 'text/vnd.turbo-stream.html'
              },
              success: function(data) {
                  console.log(data)
                  Turbo.renderStreamMessage(data);
              },
              error: function(jqXHR, textStatus, errorThrown) {
                  console.error('Erreur:', textStatus, errorThrown);
              }
          });
          // form.submit()
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
          'simulateur[nombreEnfant]': {
            number: true,
            max: 12
          },
          'simulateur[jourRepas]': {
            number: true,
            max: 22
          },
          'simulateur[jourDeplacement]': {
            number: true,
            max: 22
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
          'simulateur[nombreEnfant]': {
            number: 'Entrez un nombre valide',
            max: 'Maximum 12'
          },
          'simulateur[jourRepas]': {
            number: 'Entrez un nombre valide',
            max: 'Maximum 22 jours'
          },
          'simulateur[jourDeplacement]': {
            number: 'Entrez un nombre valide',
            max: 'Maximum 22 jours'
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

function initEntrepriseFormStep(form, title, bodyTag, transitionEffect, connected) {
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
        finish: 'Résultat (10 Crédits)',
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
        if (currentIndex === 4) {
          let data = $('#example-company').serializeArray().map(function(item) {
            item.label = $(`[name="${item.name}"]`).attr('data-unit');
            return item;
          })
          let resum = $('#resumCompany')
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
                'simulateur_entreprise[deviseSymbole]': 'Devise choisie',
                'simulateur_entreprise[taux]': 'Taux de change',
                'simulateur_entreprise[salaireNet]': 'Salaire net proposé',
                'simulateur_entreprise[nombreEnfant]': 'Nombre d\'enfant',
                'simulateur_entreprise[type]': 'Lieu de travail',
                'simulateur_entreprise[prixRepas]': 'Prix repas journalier',
                'simulateur_entreprise[jourRepas]': 'Nombre de jour (Repas)',
                'simulateur_entreprise[prixDeplacement]': 'Prix deplacement journalier',
                'simulateur_entreprise[jourDeplacement]': 'Nombre de jour (Déplacement)',
                'simulateur_entreprise[primeNet]': 'Commission nette (après deduction)',
                'simulateur_entreprise[status]': 'Statut du colaborateur',
                'simulateur_entreprise[avantage][primeConnexion]': 'Connexion Internet',
                'simulateur_entreprise[avantage][primeFonction]': 'Autres',
              }

              let field = [
                'simulateur_entreprise[deviseSymbole]',
                'simulateur_entreprise[taux]',
                'simulateur_entreprise[salaireNet]',
                'simulateur_entreprise[nombreEnfant]',
                'simulateur_entreprise[type]',
                'simulateur_entreprise[jourRepas]',
                'simulateur_entreprise[prixRepas]',
                'simulateur_entreprise[jourDeplacement]',
                'simulateur_entreprise[prixDeplacement]',
                'simulateur_entreprise[primeNet]',
                'simulateur_entreprise[status]',
                'simulateur_entreprise[avantage][primeConnexion]',
                'simulateur_entreprise[avantage][primeFonction]',
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
              if (element.name === 'simulateur_entreprise[taux]') {
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
        console.log(connected)
        if (!connected) {
          // Si connected est faux, affichez un popup
          $('#popupCompany').trigger('click');
          return false; // Empêche la soumission du formulaire
        }
        var formData = new FormData(form[0]);
        var actionUrl = form.data('action');
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'Accept': 'text/vnd.turbo-stream.html'
            },
            success: function(data) {
                console.log(data)
                Turbo.renderStreamMessage(data);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Erreur:', textStatus, errorThrown);
            }
        });
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
        'simulateur_entreprise[taux]': {
          required: true,
          number: true,
        },
        'simulateur_entreprise[salaireNet]': {
          required: true,
          number: true,
        },
        'simulateur_entreprise[nombreEnfant]': {
          number: true,
          max: 12
        },
        'simulateur_entreprise[jourRepas]': {
          number: true,
          max: 22
        },
        'simulateur_entreprise[jourDeplacement]': {
          number: true,
          max: 22
        },
        'simulateur_entreprise[avantage][primeFonction]': {
          number: true,
        },
        'simulateur_entreprise[avantage][primeConnexion]': {
          number: true,
        },
        'simulateur_entreprise[avantage][repas]': {
          number: true,
        },
        'simulateur_entreprise[avantage][deplacement]': {
          number: true,
        },
        'simulateur_entreprise[prixRepas]': {
          required: false,
          number: true,
        },
        'simulateur_entreprise[prixDeplacement]': {
          required: false,
          number: true,
        },
        'simulateur_entreprise[primeNet]': {
          required: false,
          number: true,
        },
      },
      messages: {
        'simulateur_entreprise[taux]': {
          required: 'Champs obligatoire',
          number: 'Le taux doit être un nombre décimal',
        },
        'simulateur_entreprise[salaireNet]': {
          required: 'Champs obligatoire',
          number: 'Le montant doit être un nombre décimal',
        },
        'simulateur_entreprise[nombreEnfant]': {
          number: 'Entrez un nombre valide',
          max: 'Maximum 12'
        },
        'simulateur_entreprise[jourRepas]': {
          number: 'Entrez un nombre valide',
          max: 'Maximum 22 jours'
        },
        'simulateur_entreprise[jourDeplacement]': {
          number: 'Entrez un nombre valide',
          max: 'Maximum 22 jours'
        },
        'simulateur_entreprise[avantage][primeFonction]': {
          number: 'Le montant doit être en chiffres',
        },
        'simulateur_entreprise[avantage][primeConnexion]': {
          number: 'Le montant doit être en chiffres',
        },
        'simulateur_entreprise[avantage][repas]': {
          number: 'Le montant doit être un nombre décimal',
        },
        'simulateur_entreprise[avantage][deplacement]': {
          number: 'Le montant doit être un nombre décimal',
        },
        'simulateur_entreprise[prixRepas]': {
          number: 'Le montant doit être un nombre décimal',
        },
        'simulateur_entreprise[prixDeplacement]': {
          number: 'Le montant doit être un nombre décimal',
        },
        'simulateur_entreprise[primeNet]': {
          number: 'Le montant doit être un nombre décimal',
        },
      },
    })
}

