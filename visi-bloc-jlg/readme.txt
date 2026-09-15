=== Visi-Bloc - JLG ===
Contributors: jeromelegousse
Tags: gutenberg, visibility, blocks, scheduling, roles
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Contrôles de visibilité avancés pour les blocs Gutenberg (rôles, planning, appareils, fallback).

== Description ==

Visi-Bloc – JLG ajoute des options avancées pour afficher ou masquer des blocs sur le site public : rôles, planification, règles avancées, aperçu et contenu de substitution.

== Changelog ==

= 1.1.1 =
* Déclare Requires at least 5.8, Requires PHP 7.4 et Tested up to 7.1.
* Charge le CSS d’éditeur dans l’iframe Gutenberg (WordPress 7.1).
* Synchronise les classes d’accessibilité (haute visibilité, badges compacts) vers le document canvas.
* Aligne l’admin sur la charte wp-admin (accent thème WP, notices natives).
* Échappe les `%` Gutenberg des recettes guidées pour PHP 8.2 (`sprintf`).
* Enregistre la page CRM après le menu parent (`admin.php?page=visi-bloc-jlg-crm`).
* Injecte et clone le CSS visibloc dans l’iframe éditeur 7.1.
