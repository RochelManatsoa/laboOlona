# Olona Talents

## Installation
php bin/console app:init-project

## Vue d'ensemble du projet

Olona Talents est une application Symfony permettant de connecter les talents francophones avec les recruteurs et de proposer des services de coworking.

Principales caractéristiques :

- **Configuration et dépendances** : Symfony 6.4 et divers bundles (Doctrine ORM, EasyAdmin, JWT Authentication, Turbo, LiveComponent, ...).
- **Docker** : conteneurs pour PHP, Nginx, MySQL, Mailhog et phpMyAdmin.
- **Domaines métier** : entités pour les candidats, entreprises, offres d'emploi, réservations de coworking, crédits, abonnements et concours Facebook.
- **Fonctionnalités** : API d'offres d'emploi, espace d'administration, scripts en ligne de commande et serveur Node pour le paiement PayPal.
- **Scripts OpenAI** : plusieurs scripts Node interagissent avec l'API OpenAI pour traduire du contenu et analyser les CV via des assistants.
- **Coworking** : espace de travail disponible uniquement pour les résidents d'Antananarivo.
- **Abonnements entreprise** : les sociétés peuvent s'abonner pour consulter la CVthèque, avec paiement via PayPal ou mobile money.

