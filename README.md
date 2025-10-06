# Visi-Bloc - JLG

Visi-Bloc – JLG is a WordPress plugin that adds advanced visibility controls to Gutenberg blocks. It lets administrators show or hide blocks for particular audiences, schedule their display, or preview the site as different user roles.

## Fonctionnalités

### Contrôles de visibilité dans l’éditeur
- **Restriction par rôle ou statut de connexion** – ciblez les visiteurs connectés/déconnectés et les rôles WordPress autorisés à voir le bloc, avec des badges d’aperçu lorsqu’une règle masque le contenu.
- **Planification temporelle** – activez l’option « Programmer l’affichage » pour définir des dates de début et de fin respectant le fuseau de WordPress et expliquer en aperçu pourquoi le bloc est masqué en dehors de la fenêtre.
- **Masquage manuel** – retirez immédiatement un bloc du front-end tout en gardant un contour et une explication en mode prévisualisation pour les rôles autorisés.
- **Règles avancées** – combinez plusieurs conditions (type de publication, taxonomie, modèle, créneaux récurrents, statut de connexion, groupes de rôles, contenu du panier WooCommerce, paramètres d’URL) avec une logique AND/OR pour affiner l’affichage.
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

Des extensions commerciales de personnalisation de contenu (p. ex. Block Visibility Pro, If-So, LogicHop) mettent souvent l’accent sur des segments marketing avancés, des connecteurs SaaS prêts à l’emploi et des tableaux de bord orientés performance. À la lumière de ces offres professionnelles, Visi-Bloc – JLG couvre déjà les besoins essentiels de ciblage éditorial, mais plusieurs axes lui permettraient de rivaliser avec ces outils :

- **Parcours utilisateurs préconfigurés** – les solutions pro livrent fréquemment des playbooks prêts à l’emploi (ex. « afficher un bandeau de relance au visiteur récurrent », « cibler les clients VIP ») avec un assistant pas-à-pas. Visi-Bloc gagnerait à proposer une bibliothèque de recettes guidées, accompagnée de tutoriels intégrés dans l’éditeur pour réduire la marche d’apprentissage.
- **Ciblage géographique et par appareil enrichi** – proposer des conditions basées sur la localisation (IP/Géolocalisation MaxMind, consentement à la géolocalisation HTML5), le navigateur ou la détection de périphériques spécifiques (iOS/Android, desktop tactile) irait au-delà des media queries actuellement disponibles.
- **Segments marketing dynamiques** – offrir une intégration native avec les plateformes de marketing automation / CRM (HubSpot, Brevo, Mailchimp, ActiveCampaign) afin de déclencher l’affichage selon l’appartenance à une campagne, un score de lead ou l’étape du tunnel de conversion.
- **Tests, analytics et scoring** – ajouter l’A/B testing, le suivi de conversion et des rapports sur la visibilité réelle des blocs (impressions vs. vues effectives, taux de clic) aiderait les équipes marketing à mesurer l’efficacité des règles. Des indicateurs dans le tableau de bord (performances des règles, taux d’erreur) rapprocheraient l’outil des standards pro.
- **Conditions comportementales supplémentaires** – enrichir le builder avec des déclencheurs basés sur les cookies (valeur exacte, présence ou date de dernière mise à jour), le nombre de visites ou de pages vues, l’état d’abonnement à WooCommerce/EDD (panier récurrent, statut de membre, niveau d’adhésion), l’appartenance à un groupe BuddyPress/BuddyBoss ou des segments issus d’un DMP. Chaque condition devrait être paramétrable (comparaison, opérateurs, durée de conservation) et combinable avec les règles existantes via une interface uniforme.
- **Automatisation et écosystème** – exposer et piloter les règles de visibilité via l’API REST, des webhooks et une CLI plus complète permettrait de synchroniser les scénarios depuis des workflows externes (Make, Zapier, n8n). À l’inverse, des déclencheurs entrants (webhooks, file d’attente) autoriseraient des réactions en quasi temps réel.
- **Expérience d’administration avancée** – intégrer un audit log détaillant les modifications, la possibilité d’assigner des propriétaires de règles, des revues avant publication et un mode « sandbox » pour tester des règles sans impacter le front rapprocherait l’administration de standards enterprise.
- **Support multilingue et conformité** – proposer des déclinaisons automatiques des règles par langue (WPML/Polylang) et des mécanismes respectant le consentement (masquer des blocs tant qu’aucun consentement analytics n’est donné, par exemple) sécuriserait les déploiements dans des environnements réglementés.


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
