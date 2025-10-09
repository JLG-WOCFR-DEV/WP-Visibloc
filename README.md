# Visi-Bloc - JLG

Visi-Bloc – JLG is a WordPress plugin that adds advanced visibility controls to Gutenberg blocks. It lets administrators show or hide blocks for particular audiences, schedule their display, or preview the site as different user roles.

## Fonctionnalités

### Contrôles de visibilité dans l’éditeur
- **Restriction par rôle ou statut de connexion** – ciblez les visiteurs connectés/déconnectés et les rôles WordPress autorisés à voir le bloc, avec des badges d’aperçu lorsqu’une règle masque le contenu.
- **Planification temporelle** – activez l’option « Programmer l’affichage » pour définir des dates de début et de fin respectant le fuseau de WordPress et expliquer en aperçu pourquoi le bloc est masqué en dehors de la fenêtre.
- **Masquage manuel** – retirez immédiatement un bloc du front-end tout en gardant un contour et une explication en mode prévisualisation pour les rôles autorisés.
- **Règles avancées** – combinez plusieurs conditions (type de publication, taxonomie, modèle, créneaux récurrents, statut de connexion, groupes de rôles, segments marketing exposés par vos intégrations, compteur de visites ou cookies, contenu du panier WooCommerce, paramètres d’URL) avec une logique AND/OR pour affiner l’affichage.
- **Compatibilité blocs personnalisés** – sélectionnez précisément quels types de blocs héritent des contrôles Visibloc via la page d’options.

### Contenu de substitution et affichage par appareil
- **Fallback global configurable** – choisissez de ne rien afficher, d’injecter un message HTML personnalisé ou de réutiliser un bloc Gutenberg publié lorsqu’un bloc est masqué.
- **Classes CSS prêtes à l’emploi** – ajoutez `vb-hide-on-mobile`, `vb-mobile-only`, `vb-tablet-only` ou `vb-desktop-only` à n’importe quel bloc et laissez le plugin générer les media queries adaptées.
- **Seuils responsive personnalisables** – ajustez les largeurs mobile/tablette via le panneau d’administration et profitez d’une feuille de style recalculée dynamiquement.

### Outils d’aperçu et d’administration
- **Commutateur de rôle** – autorisez certains rôles à se glisser dans la peau d’un autre depuis la barre d’admin, avec conservation du statut réel pour les appels techniques.
- **Sélecteur mobile accessible** – le panneau front-end gère le focus clavier, verrouille le scroll et rend le reste de la page inert/aria-hidden tant qu’il est ouvert.
- **Snapshots de configuration** – exportez/importez l’ensemble des réglages (blocs pris en charge, seuils responsive, fallback, rôles autorisés, mode debug) pour synchroniser plusieurs environnements.
- **Panneau d’aide unifié** – gérez les blocs pris en charge, les seuils responsive, le fallback, les permissions d’aperçu et le mode debug depuis une unique page dans l’administration.
- **Gestion du cache** – régénérez à la demande l’index des blocs groupés et videz les caches liés aux fallbacks, aux feuilles de style et aux aperçus lorsque la configuration change.

### Intégrations et outils développeur
- **Filtres extensibles** – ajustez la requête listant les blocs de fallback, la liste des rôles pouvant impersoner ou encore les blocs pris en charge via les hooks fournis.
- **Commande WP-CLI** – reconstruisez l’index des blocs groupés (`wp visibloc rebuild-index`) dans vos scripts de déploiement.
- **API utilitaires** – accédez à des helpers PHP (`visibloc_jlg_normalize_boolean`, `visibloc_jlg_get_sanitized_query_arg`, etc.) pour intégrer Visibloc dans vos développements.
- **Segments marketing dynamiques** – exposez vos segments CRM/marketing automation au sein de l’éditeur grâce au filtre `visibloc_jlg_user_segments`, et déléguez leur évaluation serveur via `visibloc_jlg_user_segment_matches`.
- **Compteur de visites personnalisable** – adaptez le cookie de suivi (`visibloc_jlg_visit_count`) et son cycle de vie avec `visibloc_jlg_visit_count_cookie_name`, `visibloc_jlg_visit_count_cookie_lifetime` et `visibloc_jlg_should_track_visit_count`.

## Comparaison avec des solutions professionnelles et pistes d’amélioration

Des extensions commerciales de personnalisation de contenu (p. ex. Block Visibility Pro, If-So, LogicHop) mettent souvent l’accent sur des segments marketing avancés, des connecteurs SaaS prêts à l’emploi et des tableaux de bord orientés performance. Le tableau ci-dessous synthétise les principales différences observées. Une analyse plus détaillée, incluant recommandations et roadmap priorisée, est disponible dans [`docs/comparaison-applications-pro.md`](visi-bloc-jlg/docs/comparaison-applications-pro.md).

### Synthèse comparative avec des applications professionnelles

| Axe | Visi-Bloc – JLG | Block Visibility Pro | If-So | LogicHop |
| --- | --- | --- | --- | --- |
| Ciblage de base | Rôles WP, statut de connexion, planification, règles avancées AND/OR | Ciblage par rôle + conditions WooCommerce, Easy Digital Downloads, GeoIP | Scénarios conditionnels visuels, règles basées sur l’appareil et l’URL | Segments dynamiques, intégration CRM/marketing automation |
| Expérience d’onboarding | Interface Gutenberg native, règles configurées bloc par bloc | Assistant de configuration et bibliothèques de recettes | « Conditions » pré-emballées avec suggestions de cas d’usage | Parcours guidés orientés entonnoir marketing |
| Analytics & optimisation | Logs techniques limités, pas de reporting d’impact | Statistiques d’affichage et de conversion intégrées | Mesures basiques avec connecteurs Google Analytics/Tag Manager | Tableaux de bord détaillés (conversions, tests A/B) |
| Automatisation & intégrations | Hooks WordPress, commande WP-CLI, API utilitaires PHP | Intégrations WooCommerce, EDD, Gravity Forms | Webhooks entrants/sortants, connecteurs SaaS | API REST complète, webhooks, synchronisation CRM |
| Gouvernance & conformité | Gestion des permissions et fallback global | Contrôles d’accès granulaires, audit de règles | Gestion de consentement simplifiée | Gestion multi-sites, rôles marketing dédiés, support RGPD |
| Qualité UI/UX | Panneaux Gutenberg cohérents mais denses, absence de hiérarchie visuelle unifiée | Interface propriétaire segmentant les règles et les recettes, call-to-action explicites | Builder conditionnel visuel avec aperçus instantanés | Tableaux de bord full-screen, storytelling et notation visuelle |

### Diagnostic UI/UX actuel

- **Point forts** – L’expérience se fond dans le panneau latéral de Gutenberg, ce qui limite la rupture de parcours pour un éditeur habitué à WordPress. Les règles avancées sont modulaires (type de contenu, taxonomie, récurrence, statut de connexion, groupes de rôles, cookies, paramètres d’URL, panier WooCommerce, etc.) et peuvent être combinées en logique AND/OR, offrant une grande puissance sans avoir à quitter l’éditeur.
- **Limites identifiées** – La création de scénarios repose sur une configuration bloc par bloc et sur une compréhension approfondie des réglages disponibles. Il n’existe ni guide interactif ni vue d’ensemble des règles en place, ce qui peut entraîner un manque de visibilité et des erreurs de paramétrage pour les équipes marketing ou produit débutantes.
- **Accessibilité et feedback** – Les messages d’explication en mode aperçu et le commutateur de rôle dans la barre d’admin donnent des retours utiles, mais il manque des visualisations plus explicites (timeline, alertes contextuelles, score de couverture) pour comprendre l’impact d’une règle sur le parcours utilisateur ou détecter les conflits.

### Axes d’amélioration recommandés

- **Parcours utilisateurs préconfigurés** – les solutions pro livrent fréquemment des playbooks prêts à l’emploi (ex. « afficher un bandeau de relance au visiteur récurrent », « cibler les clients VIP ») avec un assistant pas-à-pas. Visi-Bloc gagnerait à proposer une bibliothèque de recettes guidées, accompagnée de tutoriels intégrés dans l’éditeur pour réduire la marche d’apprentissage.
- **Ciblage géographique et par appareil enrichi** – proposer des conditions basées sur la localisation (IP/Géolocalisation MaxMind, consentement à la géolocalisation HTML5), le navigateur ou la détection de périphériques spécifiques (iOS/Android, desktop tactile) irait au-delà des media queries actuellement disponibles.
- **Segments marketing dynamiques** – offrir une intégration native avec les plateformes de marketing automation / CRM (HubSpot, Brevo, Mailchimp, ActiveCampaign) afin de déclencher l’affichage selon l’appartenance à une campagne, un score de lead ou l’étape du tunnel de conversion.
- **Tests, analytics et scoring** – ajouter l’A/B testing, le suivi de conversion et des rapports sur la visibilité réelle des blocs (impressions vs. vues effectives, taux de clic) aiderait les équipes marketing à mesurer l’efficacité des règles. Des indicateurs dans le tableau de bord (performances des règles, taux d’erreur) rapprocheraient l’outil des standards pro.
- **Conditions comportementales supplémentaires** – enrichir le builder avec des déclencheurs basés sur les cookies (valeur exacte, présence ou date de dernière mise à jour), le nombre de visites ou de pages vues, l’état d’abonnement à WooCommerce/EDD (panier récurrent, statut de membre, niveau d’adhésion), l’appartenance à un groupe BuddyPress/BuddyBoss ou des segments issus d’un DMP. Chaque condition devrait être paramétrable (comparaison, opérateurs, durée de conservation) et combinable avec les règles existantes via une interface uniforme.

> ✅ **Mise à jour** – Le builder propose désormais un déclencheur « Compteur de visites » alimenté par un cookie dédié (`visibloc_visit_count`) et un type de règle « Segment marketing » piloté par vos intégrations via les filtres `visibloc_jlg_user_segments` et `visibloc_jlg_user_segment_matches`.
- **Automatisation et écosystème** – exposer et piloter les règles de visibilité via l’API REST, des webhooks et une CLI plus complète permettrait de synchroniser les scénarios depuis des workflows externes (Make, Zapier, n8n). À l’inverse, des déclencheurs entrants (webhooks, file d’attente) autoriseraient des réactions en quasi temps réel.
- **Expérience d’administration avancée** – intégrer un audit log détaillant les modifications, la possibilité d’assigner des propriétaires de règles, des revues avant publication et un mode « sandbox » pour tester des règles sans impacter le front rapprocherait l’administration de standards enterprise.
- **Support multilingue et conformité** – proposer des déclinaisons automatiques des règles par langue (WPML/Polylang) et des mécanismes respectant le consentement (masquer des blocs tant qu’aucun consentement analytics n’est donné, par exemple) sécuriserait les déploiements dans des environnements réglementés.
- **Améliorations UI/UX ciblées** – Concevoir une page d’overview centralisant les règles actives par contenu, avec filtres, indicateurs de statut et navigation par segments permettrait d’aligner le produit sur les standards pro. Ajouter un mode « diagramme d’audience » ou une frise temporelle interactive aiderait à visualiser les chevauchements de règles. Dans l’éditeur, des badges colorés, des tooltips contextualisés et une palette de couleurs cohérente avec le design system WordPress renforceraient la lisibilité, tandis que des modales d’onboarding (checklist, vidéo courte) et un centre d’aide contextuel fluidifieraient les premiers usages.
- **Design system et responsive preview** – Introduire un mini design system (typographie, couleurs, icônes, composants réutilisables) assurerait une expérience homogène entre la page d’options, les panneaux et les modales. Un sélecteur de preview responsive directement accessible depuis le panneau Visibloc, couplé à des captures d’écran générées automatiquement pour chaque breakpoint, offrirait une perception immédiate du rendu final et des éventuels conflits de fallback.
- **Monitoring de performance** – mettre à disposition un tableau de bord corrélant règles actives, temps de rendu et incidents détectés (erreurs PHP, cache expiré) aiderait à prioriser les optimisations techniques.
- **Gouvernance multi-environnements** – formaliser des parcours de promotion (staging → production) avec export/import signé, journal d’audit et hooks de pré-déploiement garantirait une cohérence entre environnements.

### Améliorations techniques complémentaires

- **Refonte des assets front-end** – Migrer les sources JavaScript/TypeScript vers un bundler moderne (Vite ou esbuild) réduirait les temps de compilation et faciliterait le code-splitting côté éditeur. Cette évolution permettrait aussi d’introduire des tests unitaires ciblés sur les hooks React personnalisés et de détecter les régressions de performance dès le développement.
- **Bibliothèque de composants partagés** – Isoler les panneaux, badges et tooltips dans une bibliothèque de composants (Storybook + tests visuels via Playwright) garantirait la cohérence UI tout en accélérant la création de nouvelles règles ou assistants guidés. Chaque composant pourrait exposer ses props et bonnes pratiques d’accessibilité pour favoriser la contribution.
- **Couverture de tests élargie** – Compléter la suite E2E par des tests PHP (PHPUnit) ciblant les règles complexes (récurrences, fuseaux, combinaisons de conditions) sécuriserait les refactors. Des tests de migration d’options (création, mise à jour, suppression) aideraient également à fiabiliser les changements de schéma.
- **Observabilité et débogage** – Ajouter un mode debug enrichi (journalisation structurée via Monolog, onglet d’inspection des règles exécutées, export JSON) simplifierait la résolution des incidents sur les environnements clients. Couplé à des métriques (temps d’évaluation des règles, nombre d’appels à la base), cela fournirait une base pour le capacity planning.
- **Compatibilité multisite & CI/CD** – Documenter et automatiser la prise en charge de WordPress multisite (scripts d’activation réseau, propagation des paramètres) ainsi que l’intégration continue (workflow GitHub Actions couvrant lint, tests PHP/JS, build) faciliterait le déploiement dans des organisations distribuées.

### Focales UI/UX et design

- **Architecture d’information dans l’éditeur** – regrouper les réglages par « Objectif » (Ciblage, Calendrier, Substitution, Déclencheurs avancés) avec des sous-panneaux repliables et un état récapitulatif (badges colorés, texte concis) réduirait la charge cognitive. Les solutions pro affichent souvent des résumés contextuels, par exemple « Affiché pour : Clients connectés – Règle promo Black Friday » directement sous le titre du bloc.
- **Guides visuels et empty states** – ajouter des écrans d’accueil illustrés sur les panneaux vides (ex. « Commencez par définir votre audience ») et des checklists intégrées améliore la progression. Un système de « modèles » (carte illustrée + CTA) dans la barre latérale, inspiré des bibliothèques de recettes de Block Visibility Pro, aiderait à choisir un scénario pertinent.
- **Palette et cohérence graphique** – définir un design system léger (nuances principales, espacement 8pt, composants tokens) et l’appliquer aux badges, boutons secondaires et aides contextuelles apporterait une cohérence visuelle comparable aux produits premium. L’usage d’icônes linéaires uniformes (visibilité, calendrier, device) renforcerait l’intuitivité.
- **Feedback instantané** – proposer un volet d’aperçu actualisé en direct (miniature responsive ou résumé textuel) dès qu’une condition est ajoutée éviterait les allers-retours front/back. If-So et LogicHop s’appuient sur des vignettes ou la duplication du contenu pour matérialiser l’impact.
- **Accessibilité et micro-interactions** – intégrer des annonces ARIA lors des changements d’état, améliorer la navigation clavier par des ordres logiques et ajouter des micro-animations discrètes (progression, success check) rendrait l’expérience plus fluide et inclusive. Les versions professionnelles mettent l’accent sur les états focus/hover explicites et sur l’indication des erreurs au plus près du champ concerné.
- **Personnalisation du tableau de bord** – concevoir une page d’accueil administrative synthétique (cartes métriques, alertes de règles expirant bientôt, liste des dernières modifications) permettrait aux équipes de prioriser rapidement leurs actions. Cette vue pourrait intégrer un indicateur de santé des règles, à l’image des dashboards marketing modernes.

### Pistes UI/UX additionnelles inspirées des solutions pro

Les améliorations ci-dessous approfondissent les axes identifiés précédemment et fournissent des jalons concrets pour planifier les évolutions du plugin.

#### Canvas de parcours et heatmaps de règles
- **Vue canvas** – créer une page « Parcours & règles » accessible depuis l’administration qui affiche chaque scénario conditionnel sous forme de carte connectée. Les cartes reprendraient le titre du bloc, l’objectif défini et les principales conditions actives. Les connecteurs matérialiseraient les liens (règle héritée d’un modèle, duplication d’un bloc, séquence de campagnes) pour révéler les dépendances.
- **Heatmap basée sur l’usage** – superposer une coloration allant du vert (très diffusé) au gris (peu affiché) en s’appuyant sur les statistiques d’impression ou de clics collectées par la future brique analytics. Un infobulle détaillerait les métriques clés (dernière vue, audience principale, taux de conversion) pour aider à prioriser les optimisations.
- **Filtres et recherche** – offrir des filtres rapides (page, auteur, segment ciblé, statut) ainsi qu’une recherche plein texte afin de retrouver en quelques secondes un parcours spécifique ou une règle critique.

#### Assistant de scénarisation guidé
- **Parcours en 4 étapes** – proposer un wizard composé de quatre étapes : Objectif (conversion, rétention, upsell…), Audience (rôle, segment CRM, géolocalisation), Timing (programme, déclencheurs), Contenu (bloc ou fallback). Chaque étape fournirait des recommandations contextuelles et des suggestions prédéfinies issues d’un catalogue.
- **Suggestions intelligentes** – exploiter l’historique des règles existantes et les segments disponibles pour recommander des réglages (ex. « Les visiteurs revenants convertissent mieux avec une relance 24h après la première visite »). Les propositions seraient pré-remplies mais modifiables.
- **Prévisualisation live** – afficher dans un panneau latéral une mini-preview du rendu (texte, capture du bloc, timeline des déclencheurs) actualisée à chaque changement pour réduire l’incertitude.

#### Centre de notifications et alertes proactives
- **Notifications persistantes** – ajouter une cloche dans la barre d’outils du plugin, avec un badge numérique, qui regroupe les alertes critiques (règles expirées, conflits entre conditions, absence de fallback sur un bloc masqué) et les recommandations d’amélioration (tests A/B à lancer, segments peu exploités).
- **Priorisation** – trier les alertes par sévérité (critique, avertissement, information) et fournir des CTA directs (« Renouveler la date de fin », « Ajouter un fallback »). Les notifications devraient être dismissibles une fois traitées pour conserver une vue propre.
- **Historique** – conserver un journal consultable des alertes passées (90 jours glissants) pour suivre la résorption des problèmes et identifier les domaines récurrents de fragilité.

#### Mode collaboration et commentaires contextuels
- **Commentaires in-situ** – permettre à un utilisateur autorisé de cliquer sur une règle pour ouvrir un volet latéral de commentaires. Les messages seraient mentionnables (`@nom`), pourraient inclure des pièces jointes (capture, lien) et se verraient attribuer un statut (« à faire », « en cours », « résolu »).
- **Flux d’approbation** – introduire un workflow optionnel où les règles passent par les statuts « Brouillon », « En revue », « Publié ». Les reviewers reçoivent une notification et peuvent approuver ou demander des modifications directement depuis la conversation.
- **Permissions dédiées** – ajouter des capacités WordPress spécifiques (`visibloc_review_rules`, `visibloc_comment_rules`) pour différencier les rôles pouvant commenter, approuver ou uniquement consulter.

#### Guides in-app et centre d’aide immersif
- **Dock d’aide** – intégrer un composant flottant « Aide & ressources » affichant FAQ, vidéos micro-format, checklists d’onboarding et accès rapide à la documentation officielle. Le dock serait contextualisé : depuis l’éditeur, il afficherait des tutoriels sur les règles ; depuis la page d’overview, des guides sur l’analyse des performances.
- **Recherche unifiée** – proposer une palette de commande (`Cmd/Ctrl + K`) permettant de rechercher une règle, ouvrir un tutoriel, contacter le support ou créer un ticket sans quitter l’écran courant.
- **Checklists progressives** – pour les nouveaux sites, afficher une checklist (configurer les breakpoints, créer une première règle, tester en mode preview) avec suivi de progression et récompenses visuelles lorsque les étapes sont complétées.

#### Mode audit et conformité
- **Journal des modifications** – consigner chaque changement (création, modification, suppression) avec auteur, date, anciennes valeurs et nouvelles valeurs. Le journal serait exportable en CSV pour être intégré aux procédures internes des entreprises.
- **Filtrage par conformité** – offrir des tags « RGPD », « Consentement », « Localisation » attribuables aux règles. Un panneau dédié permettrait de filtrer les règles sensibles et de vérifier rapidement leur état (actif, en révision, expiré).
- **Rapport de contrôle** – générer automatiquement un rapport mensuel listant les règles critiques, les écarts détectés (ex. règle active sans consentement enregistré) et les actions recommandées. Le rapport pourrait être envoyé par e-mail aux responsables conformité.

### Feuille de route priorisée

| Priorité | Thématique | Objectifs clés | Livrables principaux |
| --- | --- | --- | --- |
| 🟥 Court terme | Parcours guidés & onboarding | Réduire le temps de prise en main pour les éditeurs | Assistant 4 étapes, tutoriels contextuels, playbooks prêts à l’emploi |
| 🟧 Court/moyen terme | Analytics & feedback | Mesurer l’impact réel des règles et détecter les conflits | Tableau de bord métrique, heatmap de couverture, notifications proactives |
| 🟨 Moyen terme | Ciblage enrichi | Étendre les scénarios marketing et comportementaux | Géolocalisation, conditions comportementales, segments CRM natifs |
| 🟩 Long terme | Gouvernance & conformité | Sécuriser les usages enterprise et multilingues | Audit log avancé, workflows d’approbation, support WPML/Polylang |

### Approche technique par axe

- **Onboarding assisté** – tirer parti des `wp.data` stores existants pour enregistrer l’état du wizard, persister les brouillons de scénarios via des options personnalisées, et utiliser `@wordpress/components` (Stepper, Guide) pour rester cohérent avec l’écosystème Gutenberg.
- **Géociblage** – encapsuler les services de géolocalisation dans une classe `Visibloc_Geolocation_Provider` avec pilotes interchangeables (MaxMind, IP2Location, API SaaS). Prévoir un cache transitoire (transients) pour limiter l’impact sur les performances.
- **Segments CRM** – exposer un module PHP dédié (`includes/integrations/class-visibloc-crm-sync.php`) capable de récupérer les segments via REST/GraphQL, stocker les correspondances côté serveur et fournir un `DataProvider` JavaScript pour l’éditeur.
- **Analytics** – utiliser `wp_track_event` ou un endpoint REST personnalisé pour capter les impressions. Une tâche cron agrègerait les données brutes dans une table dédiée (`wp_visibloc_insights`) afin d’alimenter le tableau de bord et les heatmaps.
- **Notifications & alertes** – implémenter une table `wp_visibloc_alerts` avec un statut, un niveau de sévérité et un horodatage. Les alertes seraient exposées via l’API REST et un `wp.data.select` dans l’éditeur fournirait un badge en temps réel.
- **Audit & gouvernance** – enregistrer les événements (création, update, suppression) via des hooks centralisés (`visibloc_rule_saved`, etc.) et un middleware qui signe les snapshots JSON. Des commandes WP-CLI (`wp visibloc export-rules --signed`) faciliteraient l’automatisation.
- **Accessibilité renforcée** – définir une checklist de critères WCAG 2.1 AA pour chaque composant interactif. Ajouter des tests Playwright axés sur le focus management et intégrer `axe-core` dans la CI pour détecter automatiquement les régressions.
- **Design system** – établir une bibliothèque Figma/Storybook alignée sur le 8pt grid et intégrer un thème Sass partagé (`assets/scss/_tokens.scss`) afin d’assurer la cohérence des futurs modules (heatmaps, notifications, dashboards).

### Métriques de succès recommandées

- **Adoption des recettes** – % de règles créées via le wizard ou les playbooks vs création manuelle.
- **Temps de configuration** – durée moyenne entre l’activation du plugin et la première règle publiée (objectif : < 10 min après onboarding guidé).
- **Qualité et fiabilité** – nombre de conflits détectés automatiquement vs conflits signalés par les utilisateurs.
- **Performance front** – variation du TTFB/LCP avant/après activation de scénarios avancés, pour garantir que l’ajout d’analytics ou de géociblage n’impacte pas l’expérience utilisateur.
- **Satisfaction des équipes** – score CSAT recueilli après résolution d’une alerte ou utilisation du centre d’aide immersif.

### Dépendances et prérequis

- **Budget API** – certains axes (géolocalisation, CRM) nécessitent des licences ou des quotas API ; prévoir un mécanisme de configuration sécurisée des clés (chiffrage via `wp_sodium` ou dépendance à `defuse/php-encryption`).
- **Compatibilité PHP/JS** – s’assurer que les nouvelles briques restent compatibles avec la matrice WordPress supportée (PHP 7.4+, WordPress 6.2+). Les packages front devront respecter les contraintes de bundling existantes (`@wordpress/scripts`).
- **Sécurité & RGPD** – documenter les traitements de données personnelles et proposer des hooks pour anonymiser/agréger les données (ex. stockage des impressions sans IP complète).

### Synthèse opérationnelle

| Périmètre | Actions identifiées | Statut |
| --- | --- | --- |
| **Fonctionnalités** | Assistant de scénarisation guidé, centre de notifications, heatmaps de visibilité, intégration géolocalisation, analytics & A/B testing, API REST/webhooks étendus, segmentation CRM native. | À cadrer (priorisation détaillée dans la feuille de route). |
| **Refactoring technique** | ✅ Centralisation de la constante `VISIBLOC_JLG_VERSION` et normalisation booléenne partagée livrées, prochaines étapes : mécanisme de cache respectant `apply_filters()`, fallback sur échec `plugins_url()`, suppression des dépendances npm obsolètes. | En cours (voir `REVIEW.md` et `docs/code-review.md`). |
| **Qualité & DX** | Compléter la batterie de tests (helpers PHP, scénarios Playwright), documenter les hooks publics, aligner les presets graphiques (`assets/presets/`) et fournir captures/tokens dans la doc d’onboarding. | En attente d’implémentation. |

> ℹ️ **Suivi** – Ces éléments synthétisent les besoins recensés dans les différentes revues (`REVIEW.md`, `docs/code-review.md`, `docs/presets-graphiques.md`). Maintenez ce tableau à jour à mesure que les chantiers avancent pour disposer d’une vue consolidée.


### Roadmap d'implémentation recommandée

Pour transformer ces orientations en plan d'action concret, il est utile de prioriser les chantiers selon l'effort requis et l'impact attendu.

1. **Phase 1 – Fondations produit & UX (0-3 mois)**
   - Déployer la bibliothèque de recettes guidées et le mode assistant pour sécuriser l'onboarding.
   - Ajouter les contrôles d'accessibilité (navigation clavier, annonces ARIA, focus management) et les micro-interactions critiques.
   - Structurer le design system (couleurs, typographie, composants transverses) pour homogénéiser l'interface.
2. **Phase 2 – Pilotage & gouvernance (3-6 mois)**
   - Mettre en place le centre de notifications, l'audit log et les permissions dédiées aux workflows d'approbation.
   - Introduire la vue canvas des parcours et les filtres rapides afin de donner une vision macroscopique des règles.
   - Livrer un premier tableau de bord synthétique avec métriques clés (règles actives, expirations imminentes).
3. **Phase 3 – Analytics & automatisation (6-12 mois)**
   - Développer la brique de mesure (impressions, conversions, heatmaps) et l'A/B testing natif.
   - Ouvrir l'écosystème via API REST enrichie, webhooks bidirectionnels et automatisations CLI.
   - Expérimenter les segments marketing dynamiques connectés aux solutions CRM et marketing automation.

Chaque phase devrait être ponctuée d'ateliers utilisateurs (éditeurs, marketeurs, administrateurs techniques) pour valider les prototypes et ajuster la feuille de route en fonction des retours terrain.

### Indicateurs de succès et instrumentation

Pour mesurer la valeur créée, suivez un ensemble d'indicateurs quantitatifs et qualitatifs :

- **Adoption** – taux d'utilisation des recettes guidées, nombre de règles créées via l'assistant, part des blocs configurés avec fallback.
- **Efficacité opérationnelle** – durée moyenne de configuration d'une règle, volume d'alertes résolues, temps de revue/approbation.
- **Performance marketing** – évolution des conversions associées aux scénarios personnalisés, uplift mesuré par les tests A/B, couverture des segments ciblés.
- **Qualité et conformité** – taux d'erreurs de configuration détectées, ratio de règles conformes (consentement/zone géo), satisfaction des équipes conformité.

Instrumentez ces métriques via un module de telemetry léger (collecte anonymisée respectant la confidentialité), des exports CSV programmés et l'intégration possible avec les outils BI existants. Complétez par des enquêtes NPS internes et des interviews trimestrielles pour capter le ressenti utilisateur.

### Documentation et accompagnement produit

Au-delà du plugin, l'écosystème documentaire doit soutenir la prise en main et la scalabilité :

- **Playbooks thématiques** – rédiger des guides « cas d'usage » (upsell e-commerce, nurturing B2B, relance de panier) alignés sur les recettes disponibles, avec captures d'écran et checklists.
- **Académie en ligne** – proposer une mini-formation autoportée (vidéos courtes, quiz, sandbox) permettant aux nouvelles recrues d'être opérationnelles en moins d'une heure.
- **Documentation API** – publier un portail développeur détaillant endpoints REST, webhooks, schémas JSON et exemples de scripts WP-CLI pour favoriser l'adoption technique.
- **Support communautaire** – animer un espace (forum, Slack ou Discord) où partager scripts, recettes, retours d'expérience, et recueillir les demandes de fonctionnalités pour nourrir la roadmap.

## Installation
1. Download or clone this repository into `wp-content/plugins/` of your WordPress installation.
2. Ensure the plugin folder is named `visi-bloc-jlg`.
3. Activate **Visi-Bloc - JLG** through the WordPress **Plugins** screen.
4. Configure preview roles and device breakpoints on the **Visi-Bloc - JLG** settings page.

## Usage
1. In the block editor, select a block and open the settings panel.
2. Choose visibility roles or limit the block to logged-in/logged-out users.
3. Optionally enable scheduling and set start/end dates.
4. Toggle "Hide block" to keep it off the public site while leaving a dashed outline in preview.
5. Use the toolbar role switcher to view the site as different roles.

## Build & Dependencies
The plugin depends on standard WordPress core components and includes compiled editor assets in the `build/` directory.

No additional build step is required for normal installation. To modify the source JavaScript, install dependencies and rebuild assets:

```bash
npm install
npm run build
```

## Manual testing

1. Sign in as a user who can view hidden blocks but is not allowed to impersonate other roles (for example a non-administrator after enabling their role on the plugin settings screen).
2. Manually add the cookie `visibloc_preview_role=administrator` (for example through the browser developer tools).
3. Confirm that `current_user_can( 'manage_options' )` still returns `false`, including when the check is performed through XML-RPC if your stack exposes it.
4. In the block editor, open “Règles de visibilité avancées”, use **Ajouter une règle de…** to insert each rule type, and verify that the new rule is appended in order and receives keyboard focus.

## Automated testing

End-to-end coverage is provided through the Gutenberg Playwright test harness. The suite boots a disposable WordPress environment, activates the plugin, and exercises the editor UI just like an editor would.

```bash
cd visi-bloc-jlg
npm install
npm run test:e2e
```

The tests rely on [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) and Playwright. The first `npm install` may take several minutes while browsers are downloaded.

## Performance considerations

The "Visi-Bloc - JLG" administration screen paginates the internal queries in batches of 100 post IDs and caches the compiled
results for an hour. This keeps memory usage low, but on very large sites the first load after the cache expires may still take a
little longer while the plugin analyses the content library.

## Filters

### `visibloc_jlg_available_fallback_blocks_query_args`

Reusable blocks exposed in the fallback selector are now loaded without a hard limit (the plugin passes `numberposts => -1`,
`posts_per_page => -1`, and `nopaging => true` to `get_posts()` by default). The query arguments can be filtered to
re-introduce pagination or otherwise scope the lookup for very large libraries:

```php
add_filter(
    'visibloc_jlg_available_fallback_blocks_query_args',
    static function ( array $args ) {
        // Keep the ascending alphabetical order but only fetch the first 50 blocks.
        $args['numberposts']    = 50;
        $args['posts_per_page'] = 50;
        $args['nopaging']       = false;

        return $args;
    }
);
```

When using pagination, make sure the fallback block stored in the global settings stays within the returned subset or adjust the
selector UI accordingly.

### `visibloc_jlg_common_cookies`

Use this filter to customize the list of suggested cookies exposed in the advanced visibility rule builder. Each entry must be an associative array with `value` (cookie name) and an optional `label`:

```php
add_filter(
    'visibloc_jlg_common_cookies',
    static function ( array $cookies ) {
        $cookies[] = [
            'value' => 'ab_test_variant',
            'label' => 'AB test variant',
        ];

        return $cookies;
    }
);
```

The plugin automatically ignores invalid entries and ensures that cookie names are non-empty strings.

### `visibloc_jlg_user_segments`

Expose marketing segments to the Gutenberg UI. Each segment must provide a `value` and can optionally declare a human readable `label`:

```php
add_filter(
    'visibloc_jlg_user_segments',
    static function ( array $segments ) {
        $segments[] = [
            'value' => 'crm_vip',
            'label' => 'Clients VIP',
        ];

        return $segments;
    }
);
```

When no label is provided, the value is reused for display. Duplicates and empty values are ignored automatically.

### `visibloc_jlg_user_segment_matches`

Determine whether the current visitor belongs to a custom marketing segment when evaluating advanced visibility rules:

```php
add_filter(
    'visibloc_jlg_user_segment_matches',
    static function ( bool $matches, array $context ) {
        if ( 'crm_vip' === ( $context['segment'] ?? '' ) ) {
            return current_user_can( 'manage_options' );
        }

        return $matches;
    },
    10,
    2
);
```

The `$context` array always includes the `segment` key and the normalized `user` context (`roles`, `is_logged_in`).

### `visibloc_jlg_should_track_visit_count`

Control when the visit counter cookie should be updated. The default behaviour ignores admin, REST, AJAX and CLI requests:

```php
add_filter(
    'visibloc_jlg_should_track_visit_count',
    static function ( bool $should_track, array $context ) {
        if ( ! empty( $context['is_admin'] ) ) {
            return false;
        }

        return $should_track;
    },
    10,
    2
);
```

Complementary filters `visibloc_jlg_visit_count_cookie_name` et `visibloc_jlg_visit_count_cookie_lifetime` permettent de personnaliser le cookie (`visibloc_visit_count` par défaut) et sa durée de vie.

## Actions

### `visibloc_jlg_supported_blocks_cache_invalidated`

Triggered whenever the supported blocks cache is cleared. Use this hook to rebuild any dependent caches or to synchronise
external services that rely on the list of supported blocks:

```php
add_action(
    'visibloc_jlg_supported_blocks_cache_invalidated',
    static function () {
        // Recompute derived data or flush related caches here.
    }
);
```

The action fires after both the runtime and persistent caches have been flushed, ensuring that subsequent calls to
`visibloc_jlg_get_supported_blocks()` recompute the list using the latest option value and filters.
