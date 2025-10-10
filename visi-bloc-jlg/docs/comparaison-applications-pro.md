# Comparaison avec les solutions professionnelles

Ce document positionne **Visi-Bloc – JLG** face à trois solutions premium du marché (Block Visibility Pro, If-So, LogicHop) et propose des évolutions pour rapprocher l'expérience des standards professionnels. Les constats sont issus d'audits fonctionnels et UX menés sur la version actuelle du plugin.

## Panorama rapide

| Axe | Visi-Bloc – JLG | Block Visibility Pro | If-So | LogicHop |
| --- | --- | --- | --- | --- |
| **Ciblage** | Rôles WP, statut de connexion, calendrier, règles avancées (type de contenu, taxonomie, cookies, paramètres d'URL, WooCommerce, segments exposés par intégrations). | Conditions WooCommerce / EDD, GeoIP MaxMind, statuts de commande, champs Gravity Forms. | Scénarios conditionnels en drag & drop, segments par appareil / source / URL. | Segments dynamiques basés sur CRM, automatisation marketing, scores de leads. |
| **Onboarding** | Intégration native à l'inspecteur Gutenberg, règles configurées bloc par bloc. | Assistant guidé et bibliothèques de « recettes » prêtes à l'emploi. | Conditions pré-packagées avec suggestions contextuelles. | Parcours orientés entonnoir marketing, recommandations intelligentes. |
| **Analytics** | Logs techniques limités, pas de reporting d'impact. | Statistiques d'impression et de conversion intégrées. | Connexion à Google Analytics / Tag Manager, reporting basique. | Tableaux de bord complets (conversions, A/B testing, entonnoirs). |
| **Automatisation** | Hooks PHP, commande WP-CLI, filtres pour segments personnalisés. | Intégrations WooCommerce / EDD / Gravity Forms, automatisations internes. | Webhooks entrants/sortants, API REST simplifiée. | API REST riche, webhooks, synchronisation CRM, déclencheurs temps réel. |
| **Gouvernance** | Gestion des permissions via capabilities WordPress, fallback global configurable. | Audit des règles, gestion de rôles marketing dédiés. | Gestion simplifiée du consentement, segmentation par site. | Gestion multisite avancée, audit trail, conformité RGPD intégrée. |
| **UI/UX** | Panneaux Gutenberg cohérents mais denses, règles avancées à configurer manuellement. | Interface propriétaire segmentée avec CTA explicites. | Builder conditionnel visuel avec prévisualisation instantanée. | Dashboards full-screen, visualisations story-tellées. |

## Points forts actuels

- **Intégration WordPress profonde** : pas d'interface externe, prise en main immédiate pour les éditeurs habitués à Gutenberg.
- **Flexibilité des règles** : combinaison AND/OR, support de conditions variées (planification, rôles, cookies, panier WooCommerce, segments marketing via hooks).
- **Prévisualisation contextualisée** : commutateur de rôle dans la barre d'administration, messages d'aperçu dans l'éditeur.
- **Extensibilité développeur** : filtres pour étendre les segments, helpers PHP, commande WP-CLI.

## Écarts observés par rapport aux solutions pro

1. **Absence de parcours guidés** – pas de wizard ni de playbooks pour créer rapidement des scénarios prêts à l'emploi.
2. **Vision d'ensemble limitée** – aucune vue consolidée des règles existantes ou de leur impact (heatmap, timeline, alertes).
3. **Ciblage marketing incomplet** – pas d'intégration native avec des CRM/marketing automation ni de géociblage out-of-the-box.
4. **Manque d'analytics** – aucun tableau de bord ne mesure l'efficacité des règles ou la couverture réelle des audiences.
5. **Gouvernance perfectible** – pas d'audit log, de workflow d'approbation ou de notifications proactives pour les règles critiques.
6. **UI dense** – l'inspecteur Gutenberg empile de nombreux panneaux sans hiérarchie visuelle ni indicateurs de sévérité.

### Focus UX / UI, ergonomie, fiabilité et design

| Axe | Visi-Bloc – JLG | Applications pro (Block Visibility Pro, If-So, LogicHop) | Opportunités d'amélioration |
| --- | --- | --- | --- |
| **UX / UI** | Interface native Gutenberg, panneaux nombreux sans segmentation claire, messages contextuels techniques. | Assistants guidés, composants propriétaires hiérarchisés, iconographie explicite, labels marketing. | Introduire une navigation en étapes, icônes cohérentes, vocabulaire métier et badges de statut pour réduire la charge cognitive. |
| **Ergonomie** | Paramétrage bloc par bloc, champs longs, absence de raccourcis, feedback limité lors de l'enregistrement. | Tableaux de bord centralisés, formulaires multi-colonnes, suggestions automatiques, auto-save et undo contextualisés. | Créer une vue centrale des règles, proposer des valeurs suggérées et sauvegarder en continu avec confirmation non intrusive. |
| **Fiabilité** | Tests unitaires ciblés, peu d'automatisation end-to-end, validations dispersées, monitoring manuel. | Suites de tests couvrant scénarios clés, alertes automatisées, télémétrie d'erreur, SLA documentés. | Mutualiser la validation via un moteur unique, renforcer la couverture Playwright/PHPUnit et ajouter de la télémétrie (Sentry, traces API). |
| **Design visuel** | Palette WordPress par défaut, densité élevée, contrastes variables entre panneaux, peu de repères visuels. | Systèmes de design propriétaires (tokens, pictogrammes), storytelling graphique, vues plein écran. | Définir une bibliothèque de composants avec tokens, thèmes clair/sombre et storytelling visuel (timelines, cartes d'audience). |

Ces écarts révèlent les leviers prioritaires pour aligner l'expérience Visi-Bloc sur les standards premium : fluidifier la découverte des fonctionnalités, fournir des repères visuels stables et fiabiliser la chaîne de livraison.

## Améliorations recommandées

### P0 – Mode Simple / Expert et accessibilité immédiate
- Repenser l'inspecteur avec un stepper clair, des badges de sévérité et des CTA contextualisés pour limiter la surcharge cognitive dès la première release.
  - Utiliser un `Stepper` maison (ou `NavigationItem`) avec étapes collapsibles et sauvegarde automatique à chaque changement.
  - Afficher des badges « Essentiel / Avancé » et un indicateur de complétude pour guider la progression.
- Introduire un **mode Simple** (règles essentielles, recommandations guidées, vocabulaire marketing) et un **mode Expert** (conditions avancées, logique booléenne, paramètres techniques) avec bascule persistée.
  - Option utilisateur `visibloc_editor_mode`, comparaison synthétique avant bascule, toggle accessible (`role="switch"`, libellé explicite et raccourci clavier).
  - Mode Simple : blocs intelligents pré-configurés (timer, bannière, upsell), tutoriels inline, recommandations de segmentation en 1 clic.
  - Mode Expert : onglets supplémentaires (logique imbriquée, hooks), historique des versions, import/export YAML.
- Prévoir un récapitulatif synthétique (cartes « Objectif / Audience / Timing / Fallback ») commun aux deux modes pour garantir l'alignement marketing/technique.
  - Cartes interactives imprimables, partageables via lien public (token) avec badge d'intégrité (vert/orange/rouge) calculé par le validateur central.
- Renforcer immédiatement l'accessibilité et la cohérence visuelle.
  - Focus ring unifié documenté (`focus.tokens.json`), tests `axe-core` dans la CI via Playwright (`await expect(page).toHaveNoViolations()`), badges d'arbre de blocs avec icônes SVG + texte caché.
  - Préférence « haute visibilité » (contraste renforcé, animations réduites, zones cliquables élargies) persistée en `localStorage` + `user_meta`.
  - Alertes in-éditeur quand un bloc est masqué par une règle, messages d'erreur contextualisés avec description ARIA et ordre de tabulation vérifié.

### P0 – Design system et repères visuels
- Capitaliser sur un **design system léger** aligné sur Gutenberg tout en clarifiant l'identité de Visi-Bloc.
  - Définir des tokens couleur/typo/espace (`design-tokens.json`) et générer automatiquement les styles (Sass/Emotion) pour garantir le contraste AA/AAA.
  - Créer une grille de composants dédiés (cartes d'audience, badges de statut, timelines) documentée dans Storybook avec exemples de composition.
- Améliorer la lisibilité des panneaux.
  - Harmoniser les espacements et titres de sections, introduire des `SectionHeader` avec sous-titre et aide inline.
  - Ajouter des visuels légers (illustrations vectorielles, pictogrammes) pour différencier les modes Simple/Expert et les types d'alertes.

### P0 – Fiabilité, performance et observabilité
- Couvrir les scénarios critiques par des tests unitaires (PHPUnit) et end-to-end (Playwright) pour sécuriser les évolutions rapides.
  - Fixtures Playwright pour bloc promo, segments combinés, géolocalisation ; tests `tests/Rules/ParserTest.php` sur le parsing booléen.
- Ajouter des contrôles de cohérence automatiques à l'enregistrement.
  - `RuleValidator` centralisé, corrections rapides en 1 clic et hooks d'extension pour l'écosystème.
- Suivre la santé des intégrations et services tiers.
  - Monitoring des API CRM, géolocalisation et file analytics avec widget « Santé des intégrations » rafraîchi toutes les 5 minutes.
- Fournir un package diagnostic exportable pour accélérer le support.
  - Bouton « Générer un package » rassemblant options, versions, logs anonymisés et derniers événements, avec hash de vérification.
- Exécuter des tests de charge réguliers sur les endpoints REST critiques.
  - Scripts k6/Gatling dans `tests/perf/`, seuil 95e percentile < 200 ms, alertes Slack en cas de dérive.

### P1 – Parcours guidés & playbooks
- Ajouter un assistant en 4 étapes (Objectif → Audience → Timing → Contenu) inspiré des solutions pro.
  - **Objectif** : templates (conversion, engagement, personnalisation) alimentant des KPI par défaut.
  - **Audience** : regroupement visuel des conditions avec chips éditables et prévisualisation « personnes concernées ».
  - **Timing** : timeline horizontale avec contraintes de calendrier, fenêtre de fréquence et fallback automatique.
  - **Contenu** : résumé du bloc ciblé, options de duplication, rappel des effets de la règle.
- Proposer une bibliothèque de recettes contextualisées (upsell WooCommerce, nurturing B2B, relance panier) accessible depuis l'inspecteur.
  - Recettes stockées en JSON (`resources/recipes/`) avec métadonnées (usage, prérequis, difficulté) et recherche full-text.
  - Présentation via `Modal` avec badges par persona et aperçu des KPI attendus.
- Pré-remplir les réglages clés avec suggestions intelligentes basées sur les scénarios existants.
  - Moteur de recommandations analysant fréquence et segments dominants, exposé via API REST `visibloc/v1/recommendations` utilisée par l'assistant et le mode Simple.

### P1 – Expérience éditeur avancée
- Introduire un canvas « Parcours & règles » représentant les scénarios sous forme de cartes connectées colorées selon leur usage.
  - Basé sur `@wordpress/components` + `react-flow`, regroupement par page ou objectif, vue timeline affichant la superposition des règles.
- Remplacer les listes de taxonomies par des `ComboboxControl` filtrants/paginés pour les gros catalogues.
  - Chargement à la demande via REST, mémorisation des sélections récentes par utilisateur.
- Étendre les badges de l'arbre de blocs pour refléter calendrier, segments et géociblage avec texte accessible.
- Ajouter des **micro-interactions** inspirées des solutions pro pour clarifier l'état de chaque règle.
  - Animations discrètes lors de l'activation/désactivation, toasts avec résumé d'impact (« Règle activée pour 18 % des visiteurs »), transitions cohérentes documentées dans le design system.
  - Options de personnalisation du layout (densité compacte/standard) stockées côté utilisateur pour adapter l'ergonomie aux cas intensifs.

### P2 – Ciblage enrichi
- Intégrer un module de géolocalisation (MaxMind, IP2Location) avec cache transitoire et respect du consentement. *(Version initiale livrée : ciblage par pays basé sur les codes ISO et cache transitoire.)*
  - `GeolocationService` dédié, fallback pays si consentement absent, réponses en `transient` 15 min invalidées par cron.
- Détecter appareils/systèmes (iOS, Android, desktop tactile) via une couche front partagée.
  - `DeviceContextProvider` commun éditeur/front basé sur `navigator.userAgentData` ou fallback UA, segments devices avec pictogrammes et aide accessibilité.
- Offrir des connecteurs HubSpot, Brevo, Mailchimp, ActiveCampaign pour activer des segments dynamiques.
  - Interface `MarketingConnectorInterface`, synchronisations différées via Action Scheduler, documentation de mapping de champs.

### P2 – Analytics et optimisation
- Capturer impressions, vues effectives et conversions dans `wp_visibloc_insights`.
  - Schéma : `rule_id`, `block_id`, `timestamp`, `event_type`, `audience_hash`, `context_metadata` (JSON), ingestion via REST + fallback `admin-ajax.php`, file d'attente optionnelle (WP Queue/Redis).
- Visualiser les données via un tableau de bord dédié.
  - Page `Visi-Bloc → Insights` (Victory/Recharts), mode sombre/clair, export CSV/JSON, filtres par période, règle, audience, device.
- Déployer un module d'A/B testing.
  - Custom post type `visibloc_test`, allocation aléatoire puis bandit (Thompson Sampling) possible, reporting intégré et notifications de signifiance.
- Assurer la **fiabilité opérationnelle** de ces nouveaux flux.
  - Ajouter des sondes de performance (latence REST, erreurs 4xx/5xx) et un tableau de bord SLO simple (temps de réponse, fraîcheur des données) accessible depuis l'écran Insights.
  - Mettre en place des webhooks de supervision (Slack, Teams) et un plan de reprise (retry queue, alerte si >5 % d'erreurs sur 10 min).

### P3 – Gouvernance & collaboration
- Enregistrer tous les changements de règles dans un audit log exportable (`wp_visibloc_audit`).
  - Détails utilisateur, action, diff JSON, IP, contexte ; interface filtrable avec export CSV.
- Mettre en place un workflow « Brouillon → Revue → Publié » avec commentaires @mention.
  - Statut `needs_review`, panneau latéral d'assignation, notifications WP admin + email + webhooks Slack/Teams.
- Ajouter un centre de notifications proactif (règles expirées, absence de fallback, conflits de conditions).
  - Une première itération est disponible dans l’interface d’aide (fallback global manquant, programmations expirées ou massivement servies en fallback).
  - Niveaux Info/Alerte/Critique avec CTA « Corriger » ouvrant la règle ; déclencheurs à l'enregistrement et via cron quotidien.

### Synthèse priorisée

| Priorité | Objectif principal | Livrables clés | Indicateurs d'impact |
| --- | --- | --- | --- |
| **P0** | Stabiliser l'expérience et éviter les régressions | Mode Simple/Expert complet, accessibilité AA, stepper hiérarchisé, tests Playwright/PHPUnit, monitoring & perf tests | Adoption du mode Simple, taux de tests verts, absence de régressions critiques |
| **P1** | Accélérer la création de scénarios et la visualisation | Assistant 4 étapes, recettes JSON, moteur de recommandations, canvas des parcours, combobox filtrantes | Temps moyen de création < 10 min, taux d'utilisation des recettes |
| **P2** | Étendre la personnalisation et mesurer l'impact | Géolocalisation, DeviceContext, connecteurs marketing, tableau de bord Insights, A/B testing | Couverture segments, conversions mesurées, latence API < 200 ms |
| **P3** | Structurer la gouvernance et la collaboration | Audit log, workflow de revue, centre de notifications intelligent | Délai de validation des règles, nombre d'alertes résolues |

## Roadmap suggérée

| Horizon | Objectifs | Livrables clés |
| --- | --- | --- |
| **0-3 mois** | Faciliter l'onboarding et structurer l'interface. | Assistant guidé, playbooks, refonte du stepper, focus management. |
| **3-6 mois** | Apporter visibilité et gouvernance. | Centre de notifications, audit log, canvas des règles, premiers KPI d'usage. |
| **6-12 mois** | Étendre les scénarios marketing & la mesure. | Géolocalisation, intégrations CRM, analytics/A/B testing, API REST & webhooks enrichis. |

## Indicateurs de succès

- **Adoption** : % de règles créées via l'assistant ou les playbooks ; temps moyen pour créer un scénario complet (< 10 min).
- **Performance** : uplift des conversions par rapport à la base, taux de couverture des segments clés.
- **Qualité** : réduction du nombre d'erreurs de configuration détectées, délai de résolution des alertes critiques.
- **Satisfaction** : score CSAT/NPS des équipes marketing et conformité après utilisation des nouvelles fonctionnalités.

## Prochaines étapes

1. **Cadrage produit** – ateliers avec les équipes marketing/édition pour prioriser les recettes et les KPI.
2. **Prototype UX** – maquettes Figma du wizard, du centre de notifications et du canvas des règles ; tests utilisateurs rapides.
3. **Architecture technique** – définir les modèles de données (tables insights/alerts), les endpoints REST et la stratégie de cache.
4. **MVP incrémental** – livrer progressivement : assistant, puis notifications, puis analytics ; instrumenter chaque release.

Ce plan permet de combler les écarts majeurs identifiés et d'offrir une expérience comparable aux solutions professionnelles tout en capitalisant sur l'intégration native de Visi-Bloc – JLG dans WordPress.
