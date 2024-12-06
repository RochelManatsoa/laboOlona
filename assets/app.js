/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import './styles/app.scss'; 
import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { Calendar } from 'fullcalendar';
require('tom-select/dist/css/tom-select.css');


document.addEventListener('turbo:load', function() {
    const calendarEl = document.getElementById('calendar')
    if (!calendarEl || calendarEl === '') {
      return; 
    }
    const eventData = calendarEl.getAttribute('data-events');
    if (!eventData || eventData === '') {
      return; 
    }
    // Parse the JSON data
    const events = JSON.parse(eventData);
    const calendar = new Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'fr',
      timeZone: 'Eastern/Africa',
      editable: true,
      eventResizableFromStart: true,
      events: events, 
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
      },
      buttonText: {
        today: 'Aujourd\'hui',
        month: 'Mois',
        week: 'Semaine',
        day: 'Jour',
        list: 'Liste',
      },
    })
    calendar.on('eventChange', function(e) {
      console.log('eventChange', e);
      let url = '/api/event/' + e.event.id + '/edit';
      let donnees = {
        "title": e.event.title,
        "description": e.event.extendedProps.description,
        "user": e.event.extendedProps.user,
        "start": e.event.start,
        "end": e.event.end,
        "allDay": e.event.allDay,
        "backgroundColor": e.event.backgroundColor,
        "borderColor": e.event.borderColor,
        "textColor": e.event.textColor,
      }
      fetch(url, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(donnees),
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          console.log('success', data);
        } else {
          console.log('error', data);
        }
      })
    })
    calendar.on('eventClick', function(e) {
      console.log('eventClick', e);
      
      const eventTitle = e.event.title || "Sans titre"; 
      const eventDescription = e.event.extendedProps.description || "Aucune description";
      const eventUser = e.event.extendedProps.user || "Non défini";
      const eventPlaces = e.event.extendedProps.places.length || 0;
    
      // Récupération des éléments modal
      const modalTitleElement = document.getElementById('modalTitle');
      const modalDescriptionElement = document.getElementById('modalDescription');
      const modalUserElement = document.getElementById('modalUser');
      const modalPlacesElement = document.getElementById('modalPlaces');
    
      // Mise à jour du contenu du modal
      if (modalTitleElement) {
        modalTitleElement.textContent = eventTitle;
      }
    
      if (modalDescriptionElement) {
        modalDescriptionElement.textContent = eventDescription;
      }
    
      if (modalUserElement) {
        modalUserElement.textContent = 'Utilisateur : ' + eventUser;
      }
    
      if (modalPlacesElement) {
        modalPlacesElement.textContent = 'Nombre de place : ' + eventPlaces;
      }
  
  
      const bsmodal = document.getElementById('exampleModal')
      var viewevent = new Modal(bsmodal);
      viewevent.show();
    })
    calendar.render()
  })



