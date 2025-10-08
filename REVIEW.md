# Revue de code Visibloc

## Points forts
- L'initialisation du plugin est bien encapsulée dans `VisiBloc\Plugin`, qui centralise l'enregistrement des hooks et garde les dépendances organisées par domaine fonctionnel (assets, visibilité, commutateur de rôle, etc.). 【F:visi-bloc-jlg/src/Plugin.php†L57-L113】
- Les réglages exposés dans l'API REST sont protégés par un schéma détaillé lors de l'appel à `register_setting`, ce qui garantit une normalisation côté serveur et côté éditeur. 【F:visi-bloc-jlg/src/Plugin.php†L74-L82】

## Améliorations proposées
1. **Définition de version dupliquée** – Le numéro de version est calculé deux fois : dans le fichier principal du plugin et dans `includes/assets.php`. Cette duplication risque de diverger si l'un des chemins évolue (ex. gestion d'erreur différente, ajout de mise en cache). Centraliser la logique (par exemple via une fonction utilitaire unique) réduirait le risque et simplifierait les tests. 【F:visi-bloc-jlg/visi-bloc-jlg.php†L45-L62】【F:visi-bloc-jlg/includes/assets.php†L124-L137】
2. **Retour de type incohérent pour le cookie d'aperçu** – `visibloc_jlg_get_preview_role_from_cookie()` renvoie `null` si le cookie est absent mais une chaîne vide si le contenu n'est pas une chaîne. Harmoniser le retour (par exemple toujours `null` dans les cas non valides) éviterait des comparaisons fragiles et faciliterait l'utilisation de types stricts. 【F:visi-bloc-jlg/includes/role-switcher.php†L10-L23】
3. **URL d'asset en mode dégradé** – Lorsque `plugins_url()` est indisponible, `visibloc_jlg_get_asset_url()` renvoie une chaîne vide. Il serait pertinent d'exposer un mécanisme de secours (filtre, hook d'erreur, fallback configurable) pour ne pas perdre silencieusement les assets critiques. 【F:visi-bloc-jlg/includes/assets.php†L49-L69】
4. **Constante métier déclarée à plusieurs endroits** – `VISIBLOC_JLG_DEFAULT_SUPPORTED_BLOCKS` est définie dans `Plugin::define_default_supported_blocks()` et redéclarée dans `includes/block-utils.php`. Lors d'une évolution, il faudra penser à modifier deux emplacements. Centraliser la définition (par exemple uniquement dans `plugin-meta.php` ou un fichier de constantes) limite les risques d'incohérence et simplifie les tests unitaires. 【F:visi-bloc-jlg/src/Plugin.php†L129-L141】【F:visi-bloc-jlg/includes/block-utils.php†L7-L9】
5. **Réimplémentation de helpers WordPress** – `visibloc_jlg_path_join()` duplique la logique de `path_join()`/`trailingslashit()` fournis par WP Core. Remplacer ce helper réduit la surface de maintenance et garantit un comportement cohérent avec le cœur, notamment sur les environnements Windows. 【F:visi-bloc-jlg/includes/assets.php†L14-L34】
6. **Dépendances NPM inutilisées** – Les devDependencies `hasown` et `p-try` n'apparaissent ni dans le code source ni dans les scripts npm. Elles semblent héritées d'anciens essais et peuvent être retirées pour accélérer les installations et alléger le lockfile. 【F:visi-bloc-jlg/package.json†L13-L16】

## Notes sur le debogage visuel
- Le nouveau style supprimait l'outline de focus des blocs marqués (hidden/fallback), ce qui faisait disparaître l'indicateur de sélection clavier. L'ajout d'un outline personnalisé résout ce problème d'accessibilité tout en conservant l'esthétique du badge. 【F:visi-bloc-jlg/src/editor-styles.css†L121-L135】

## Pistes complémentaires
- Couvrir les helpers exposés (normalisation, lecture de cookie, etc.) par des tests unitaires supplémentaires pour détecter rapidement les régressions de type/valeur.
- Ajouter une documentation développeur décrivant les hooks publics et les points d'extension disponibles.
