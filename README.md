# Visi-Bloc - JLG

Visi-Bloc – JLG is a WordPress plugin that adds advanced visibility controls to Gutenberg blocks. It lets administrators show or hide blocks for particular audiences, schedule their display, or preview the site as different user roles.

## Fonctionnalités

### Contrôles de visibilité dans l’éditeur
- **Restriction par rôle ou statut de connexion** – ciblez les visiteurs connectés/déconnectés et les rôles WordPress autorisés à voir le bloc, avec des badges d’aperçu lorsqu’une règle masque le contenu.
- **Planification temporelle** – activez l’option « Programmer l’affichage » pour définir des dates de début et de fin respectant le fuseau de WordPress et expliquer en aperçu pourquoi le bloc est masqué en dehors de la fenêtre.
- **Masquage manuel** – retirez immédiatement un bloc du front-end tout en gardant un contour et une explication en mode prévisualisation pour les rôles autorisés.
- **Règles avancées** – combinez plusieurs conditions (type de publication, taxonomie, modèle, créneaux récurrents, statut de connexion, groupes de rôles, cookies, contenu du panier WooCommerce, paramètres d’URL) avec une logique AND/OR pour affiner l’affichage.
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

## Comparaison avec des solutions professionnelles et pistes d’amélioration

Des extensions commerciales de personnalisation de contenu (p. ex. Block Visibility Pro, If-So, LogicHop) mettent souvent l’accent sur des segments marketing avancés, des connecteurs SaaS prêts à l’emploi et des tableaux de bord orientés performance. Le tableau ci-dessous synthétise les principales différences observées.

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
- **Automatisation et écosystème** – exposer et piloter les règles de visibilité via l’API REST, des webhooks et une CLI plus complète permettrait de synchroniser les scénarios depuis des workflows externes (Make, Zapier, n8n). À l’inverse, des déclencheurs entrants (webhooks, file d’attente) autoriseraient des réactions en quasi temps réel.
- **Expérience d’administration avancée** – intégrer un audit log détaillant les modifications, la possibilité d’assigner des propriétaires de règles, des revues avant publication et un mode « sandbox » pour tester des règles sans impacter le front rapprocherait l’administration de standards enterprise.
- **Support multilingue et conformité** – proposer des déclinaisons automatiques des règles par langue (WPML/Polylang) et des mécanismes respectant le consentement (masquer des blocs tant qu’aucun consentement analytics n’est donné, par exemple) sécuriserait les déploiements dans des environnements réglementés.
- **Améliorations UI/UX ciblées** – Concevoir une page d’overview centralisant les règles actives par contenu, avec filtres, indicateurs de statut et navigation par segments permettrait d’aligner le produit sur les standards pro. Ajouter un mode « diagramme d’audience » ou une frise temporelle interactive aiderait à visualiser les chevauchements de règles. Dans l’éditeur, des badges colorés, des tooltips contextualisés et une palette de couleurs cohérente avec le design system WordPress renforceraient la lisibilité, tandis que des modales d’onboarding (checklist, vidéo courte) et un centre d’aide contextuel fluidifieraient les premiers usages.
- **Design system et responsive preview** – Introduire un mini design system (typographie, couleurs, icônes, composants réutilisables) assurerait une expérience homogène entre la page d’options, les panneaux et les modales. Un sélecteur de preview responsive directement accessible depuis le panneau Visibloc, couplé à des captures d’écran générées automatiquement pour chaque breakpoint, offrirait une perception immédiate du rendu final et des éventuels conflits de fallback.

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
