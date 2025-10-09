# Comparatif détaillé avec des applications professionnelles

Ce document synthétise la position de Visi-Bloc – JLG face à des solutions commerciales de personnalisation de contenu WordPress
ou orientées marketing automation (Block Visibility Pro, If-So, LogicHop) et identifie les améliorations clés pour combler l'écart.

## 1. Panorama fonctionnel

| Domaine | Visi-Bloc – JLG | Block Visibility Pro | If-So | LogicHop |
| --- | --- | --- | --- | --- |
| **Ciblage utilisateurs** | Rôles WP, statut de connexion, règles temporelles, conditions avancées (taxonomies, type de contenu, cookies, panier WooCommerce, paramètres d'URL, segments marketing exposés via filtres) | Rôles, statut de connexion, produits WooCommerce/EDD, intégrations Gravity Forms, GeoIP | Conditions prêtes à l'emploi (appareil, URL, référent, CRM via webhooks), scénarios visuels | Segments dynamiques basés sur CRM, comportement, localisation, données personnalisées via API |
| **Personnalisation par appareil** | Classes CSS (`vb-hide-on-mobile`, etc.), seuils responsive configurables | Règles spécifiques aux appareils et à l'orientation | Détection intégrée mobile/desktop/tablette + conditions navigateur | Détection approfondie (device, localisation) |
| **Planification** | Fenêtre de publication/dépublication avec aperçu explicatif | Planification et calendrier dynamique | Conditions de dates et plages horaires | Moteur temporel avancé, calendriers multiples |
| **Automation & intégrations** | Hooks PHP, filtre pour segments marketing, commande WP-CLI | Connecteurs WooCommerce, EDD, Gravity Forms, intégration API marketing | Webhooks entrants/sortants, connecteurs Zapier/Make | API REST complète, synchronisation CRM, webhooks bi-directionnels |
| **Analytics & optimisation** | Logs techniques basiques, pas de dashboards | Statistiques d'affichage, conversions par règle | Connecteurs Google Analytics/Tag Manager, suivi de clics | Tableaux de bord conversions, parcours, tests A/B |
| **Gouvernance** | Page d'options centrale, permissions par rôle pour l'aperçu | Audit log, rôles personnalisés, règles globales | Gestion simple des permissions | Gouvernance multi-site, rôles marketing dédiés, gestion du consentement |
| **Expérience utilisateur** | Interface native Gutenberg, règles bloc par bloc, aide contextuelle limitée | Interface propriétaire avec recettes, onboarding guidé | Builder visuel conditionnel, suggestions d'usage | Dashboard orienté entonnoir marketing, storytelling |

## 2. Forces de Visi-Bloc – JLG

- Intégration WordPress « first party » : composants Gutenberg natifs, respect des conventions WP, filtres et actions pour les développeurs.
- Flexibilité des règles avancées (combinaison AND/OR, récurrence, segments marketing via filtres personnalisables).
- Outils d'aperçu directement dans l'éditeur (commutateur de rôle, explications en mode prévisualisation) réduisant les allers-retours.
- Extensibilité backend (API PHP utilitaires, commande WP-CLI) pour les équipes techniques.

## 3. Écarts constatés face aux solutions pro

1. **Visibilité stratégique limitée** : absence de dashboards globaux, de KPIs et de reporting sur l'impact des règles.
2. **Onboarding et autonomie** : pas d'assistants guidés, documentation embarquée ou recettes prêtes à l'emploi pour cas d'usage courants.
3. **Richesse des signaux** : manque de ciblage géographique natif, de conditions comportementales (score CRM, historique d'achat, engagement email) et de connecteurs SaaS prêts à l'emploi.
4. **Gouvernance avancée** : pas d'audit log, de gestion des propriétaires de règles ou de workflows de validation/sandbox.
5. **Automatisation** : API REST, webhooks et intégrations no-code inexistants, limitant la connexion aux écosystèmes marketing.
6. **Monétisation structurée** : packaging et offres différenciées (SaaS vs auto-hébergé) non définis, rendant la comparaison commerciale difficile.

## 4. Feuille de route d'amélioration recommandée

### Court terme (0-3 mois)

- Lancer une bibliothèque de recettes modulaires dans Gutenberg (assistant pas-à-pas + modèles de règles).
- Ajouter un tableau de bord de synthèse (règles actives, blocs impactés, alertes de conflits, calendrier des expirations).
- Étendre les règles comportementales : compteur de visites détaillé, déclencheurs sur nombre de pages vues, cookies/UTM paramétrables.

### Moyen terme (3-6 mois)

- Proposer des connecteurs natifs : HubSpot, Brevo, Mailchimp, ActiveCampaign, WooCommerce Memberships, LearnDash.
- Intégrer un moteur d'A/B testing simple (deux variantes, répartition automatique, suivi clic/conversion).
- Implémenter un audit log et un mode brouillon/sandbox pour les règles avec validation avant publication.
- Ouvrir une API REST + webhooks (événements « règle appliquée », « règle expirée », « conflit détecté »).

### Long terme (6-12 mois)

- Ciblage géographique natif (GeoIP, consentement à la géolocalisation) et détection avancée des appareils/navigateurs.
- Scoring marketing (exploitation des segments CRM, scores de lead, suivi de conversions multi-étapes).
- Packaging produit : offre gratuite limitée, version Pro (analytics + intégrations), bundle Enterprise (SLA, gouvernance avancée, support multisite).
- Ecosystème partenaires : programme d'agences, templates partagés, marketplace de règles.

## 5. Indicateurs de succès

- **Adoption** : réduction du temps moyen de création d'une règle, nombre de recettes utilisées, taux d'activation des nouvelles fonctionnalités.
- **Performance** : amélioration du taux de conversion moyen des blocs personnalisés, volume d'impressions trackées.
- **Satisfaction** : NPS des rôles marketing, nombre de tickets support liés à la configuration divisé par deux.
- **Monétisation** : répartition des utilisateurs par palier d'abonnement, revenu MRR généré par les intégrations premium.

## 6. Ressources complémentaires

- [Block Visibility Pro](https://blockvisibilitywp.com/)
- [If-So Dynamic Content](https://www.if-so.com/)
- [LogicHop](https://logichop.com/)

Ces recommandations visent à rapprocher Visi-Bloc – JLG des standards des applications professionnelles tout en capitalisant sur son ancrage WordPress natif.
