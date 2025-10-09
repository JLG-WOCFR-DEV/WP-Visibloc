# Revue de code Visibloc

## Points forts
- L'initialisation du plugin est bien encapsulée dans `VisiBloc\Plugin`, qui centralise l'enregistrement des hooks et garde les dépendances organisées par domaine fonctionnel (assets, visibilité, commutateur de rôle, etc.). 【F:visi-bloc-jlg/src/Plugin.php†L57-L113】
- Les réglages exposés dans l'API REST sont protégés par un schéma détaillé lors de l'appel à `register_setting`, ce qui garantit une normalisation côté serveur et côté éditeur. 【F:visi-bloc-jlg/src/Plugin.php†L74-L82】

## Améliorations proposées
1. **Définition de version dupliquée** – Le numéro de version est calculé deux fois : dans le fichier principal du plugin et dans `includes/assets.php`. Cette duplication risque de diverger si l'un des chemins évolue (ex. gestion d'erreur différente, ajout de mise en cache). Centraliser la logique (par exemple via une fonction utilitaire unique) réduirait le risque et simplifierait les tests. 【F:visi-bloc-jlg/visi-bloc-jlg.php†L45-L62】【F:visi-bloc-jlg/includes/assets.php†L124-L137】
   > ✅ **Mise à jour** – La constante `VISIBLOC_JLG_VERSION` est désormais exposée depuis `includes/plugin-meta.php` et partagée dans l'ensemble du code.
2. **Retour de type incohérent pour le cookie d'aperçu** – `visibloc_jlg_get_preview_role_from_cookie()` renvoie `null` si le cookie est absent mais une chaîne vide si le contenu n'est pas une chaîne. Harmoniser le retour (par exemple toujours `null` dans les cas non valides) éviterait des comparaisons fragiles et faciliterait l'utilisation de types stricts. 【F:visi-bloc-jlg/includes/role-switcher.php†L10-L23】
3. **URL d'asset en mode dégradé** – Lorsque `plugins_url()` est indisponible, `visibloc_jlg_get_asset_url()` renvoie une chaîne vide. Il serait pertinent d'exposer un mécanisme de secours (filtre, hook d'erreur, fallback configurable) pour ne pas perdre silencieusement les assets critiques. 【F:visi-bloc-jlg/includes/assets.php†L49-L69】
4. **Constante métier déclarée à plusieurs endroits** – `VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS` est définie dans `Plugin::define_default_supported_blocks()` et redéclarée dans `includes/block-utils.php`. Lors d'une évolution, il faudra penser à modifier deux emplacements. Centraliser la définition (par exemple uniquement dans `plugin-meta.php` ou un fichier de constantes) limite les risques d'incohérence et simplifie les tests unitaires. 【F:visi-bloc-jlg/src/Plugin.php†L129-L141】【F:visi-bloc-jlg/includes/block-utils.php†L7-L9】
5. **Réimplémentation de helpers WordPress** – `visibloc_jlg_path_join()` duplique la logique de `path_join()`/`trailingslashit()` fournis par WP Core. Remplacer ce helper réduit la surface de maintenance et garantit un comportement cohérent avec le cœur, notamment sur les environnements Windows. 【F:visi-bloc-jlg/includes/assets.php†L14-L34】
6. **Dépendances NPM inutilisées** – Les devDependencies `hasown` et `p-try` n'apparaissent ni dans le code source ni dans les scripts npm. Elles semblent héritées d'anciens essais et peuvent être retirées pour accélérer les installations et alléger le lockfile. 【F:visi-bloc-jlg/package.json†L13-L16】

## Notes sur le debogage visuel
- Le nouveau style supprimait l'outline de focus des blocs marqués (hidden/fallback), ce qui faisait disparaître l'indicateur de sélection clavier. L'ajout d'un outline personnalisé résout ce problème d'accessibilité tout en conservant l'esthétique du badge. 【F:visi-bloc-jlg/src/editor-styles.css†L121-L135】

## Pistes complémentaires
- Couvrir les helpers exposés (normalisation, lecture de cookie, etc.) par des tests unitaires supplémentaires pour détecter rapidement les régressions de type/valeur.
  > ✅ **Mise à jour** – Un test d'intégration `BooleanNormalizationTest` sécurise la logique partagée de normalisation booléenne.
- Ajouter une documentation développeur décrivant les hooks publics et les points d'extension disponibles.

## Synthèse des suivis

| Domaine | Prochaines étapes | Blocage éventuel |
| --- | --- | --- |
| Refactoring | ✅ Définition de version et normalisation booléenne désormais centralisées ; hook d'invalidation `visibloc_jlg_supported_blocks_cache_invalidated` + test d'intégration ajoutés. Prochaines étapes : documenter la stratégie côté intégrateurs et finaliser la gestion d'URL d'asset. | Décision attendue sur la mise en cache (transient vs. runtime) pour éviter les régressions côté intégrations. |
| Qualité | Tests PHP sur la normalisation booléenne ajoutés ; reste à couvrir `get_preview_role_from_cookie` et un scénario Playwright « bloc masqué + utilisateur sans droit ». | Infrastructure de test disponible ; nécessite planification dans le sprint QA. |
| Fonctionnalités | Prioriser l'assistant guidé, le centre de notifications et l'exposition API avancée en s'alignant sur la feuille de route produit décrite dans `README.md`. | Validation produit requise pour découpage et jalons. |

> 🔁 **Mise à jour** – Ce tableau récapitule les actions ouvertes issues de cette revue. Synchroniser régulièrement avec la roadmap (cf. README) évite la dispersion des suivis.

## Analyse ergonomie & UX (comparaison avec les solutions pro)

- **Parcours de réglages dense dans l’inspecteur** – Tous les contrôles majeurs (appareils, calendrier, rôles, règles avancées, fallback) sont empilés dans un unique `TabPanel` (`inspectorSteps`) sans hiérarchisation visuelle ni appels à l’action ciblés. Lorsqu’un onglet est incomplet, seul un résumé textuel est rendu via `renderHelpText`, ce qui ne met pas en évidence la sévérité du point bloquant, à l’inverse des wizards segmentés qu’affichent Block Visibility Pro ou LogicHop. 【F:visi-bloc-jlg/src/index.js†L2412-L2874】<br/>💡 *Piste* : introduire un mode « scénarios » avec presets activables depuis l’inspecteur (boutons primaires + infobulles) et un marquage de sévérité (badges colorés, icônes) pour les étapes incomplètes afin de guider l’utilisateur vers la prochaine action.
- **Builder de règles avancées peu scalable** – Le rendu des taxonomies instancie une `CheckboxControl` pour chaque terme disponible. Sur un catalogue riche (ex. centaines de catégories WooCommerce), cette liste exhaustive devient difficile à explorer au clavier et lourde en performances, là où les solutions professionnelles basculent sur des combobox filtrantes ou des pickers paginés. 【F:visi-bloc-jlg/src/index.js†L1591-L1694】<br/>💡 *Piste* : remplacer les cases à cocher par un composant de recherche asynchrone (`ComboboxControl` + requêtes REST) avec tags récapitulatifs, compteur de sélection et sauvegarde de groupes réutilisables.
- **Guided recipes cantonnés à l’admin** – La bibliothèque de recettes (`visibloc_guided_recipes__dialog`) n’est accessible que depuis l’interface d’administration, sans passerelle depuis le panneau Gutenberg. Les solutions premium mettent leurs playbooks en avant au moment de la configuration du bloc. 【F:visi-bloc-jlg/includes/admin-settings.php†L1346-L1693】<br/>💡 *Piste* : ajouter un bouton « Appliquer une recette » dans l’inspecteur qui ouvre la modale existante et injecte les attributs suggérés dans le bloc sélectionné.

## Accessibilité

- **Anneau de focus absent sur les onglets du stepper** – Le style retire l’outline par défaut et applique `box-shadow: var(--visibloc-focus-ring)` sans définir cette variable dans la feuille de style de l’éditeur. Résultat : aucun indicateur visuel ne subsiste lors de la navigation clavier, ce qui contrevient aux bonnes pratiques WCAG. 【F:visi-bloc-jlg/src/editor-styles.css†L1-L35】【F:visi-bloc-jlg/src/editor-styles.css†L93-L96】<br/>💡 *Piste* : soit définir `--visibloc-focus-ring` dans `:root`, soit conserver l’outline natif et n’ajouter qu’un halo complémentaire.

## Performance

- **Sélecteur de fallback potentiellement coûteux** – La fonction `visibloc_jlg_get_available_fallback_blocks()` charge toutes les entrées `wp_block` sans pagination (`numberposts => -1`). Sur les bibliothèques volumineuses, on se retrouve avec un `get_posts()` qui instancie des objets complets inutiles pour un simple label. 【F:visi-bloc-jlg/includes/fallback.php†L466-L639】<br/>💡 *Piste* : privilégier `fields => 'ids'` + `get_posts` paginé côté REST, avec chargement asynchrone dans le `SelectControl` et indicateur de progression.
- **Mise à jour du List View par scrutation DOM** – Chaque mutation éditeur déclenche une itération sur tous les blocs (`getAllClientIds`) et des requêtes `document.querySelector` pour appliquer des classes/badges. Sur une page riche en blocs, ces accès DOM répétés depuis `subscribe(handleEditorSubscription)` peuvent devenir un bottleneck perceptible. 【F:visi-bloc-jlg/src/index.js†L3057-L3270】【F:visi-bloc-jlg/src/index.js†L3331-L3364】<br/>💡 *Piste* : tirer parti des sélecteurs mémoïsés (`select( 'core/block-editor' ).getBlocks()` + diff) ou d’un store personnalisé pour propager l’état sans toucher directement au DOM, avec un `ResizeObserver` limité à l’ouverture effective de la vue.

## Fiabilité & feedback éditeur

- **Badges « Bloc masqué » non synchronisés avec les règles dynamiques** – L’état poussé vers la vue liste se limite à `attrs.isHidden` et `hasFallback`. Les résumés textuels listent pourtant les conditions (device, calendrier, règles avancées), mais aucune classe n’est appliquée lorsque seul un ciblage dynamique masque le bloc. Un éditeur peut donc croire qu’un bloc est visible alors qu’il est filtré par une règle de calendrier ou de segment. 【F:visi-bloc-jlg/src/index.js†L2311-L2361】【F:visi-bloc-jlg/src/index.js†L3234-L3262】<br/>💡 *Piste* : calculer un indicateur `isConditionallyHidden` (temps, rôle, règles avancées) et afficher un badge spécifique/jaune, voire une info-bulle expliquant la règle active.

