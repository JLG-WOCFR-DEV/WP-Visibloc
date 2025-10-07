# Revue de code Visibloc

## Points forts
- L'initialisation du plugin est bien encapsulée dans `VisiBloc\Plugin`, qui centralise l'enregistrement des hooks et garde les dépendances organisées par domaine fonctionnel (assets, visibilité, commutateur de rôle, etc.). 【F:visi-bloc-jlg/src/Plugin.php†L57-L113】
- Les réglages exposés dans l'API REST sont protégés par un schéma détaillé lors de l'appel à `register_setting`, ce qui garantit une normalisation côté serveur et côté éditeur. 【F:visi-bloc-jlg/src/Plugin.php†L74-L82】

## Améliorations proposées
1. **Définition de version dupliquée** – Le numéro de version est calculé deux fois : dans le fichier principal du plugin et dans `includes/assets.php`. Cette duplication risque de diverger si l'un des chemins évolue (ex. gestion d'erreur différente, ajout de mise en cache). Centraliser la logique (par exemple via une fonction utilitaire unique) réduirait le risque et simplifierait les tests. 【F:visi-bloc-jlg/visi-bloc-jlg.php†L45-L62】【F:visi-bloc-jlg/includes/assets.php†L124-L137】
2. **Retour de type incohérent pour le cookie d'aperçu** – `visibloc_jlg_get_preview_role_from_cookie()` renvoie `null` si le cookie est absent mais une chaîne vide si le contenu n'est pas une chaîne. Harmoniser le retour (par exemple toujours `null` dans les cas non valides) éviterait des comparaisons fragiles et faciliterait l'utilisation de types stricts. 【F:visi-bloc-jlg/includes/role-switcher.php†L10-L23】
3. **URL d'asset en mode dégradé** – Lorsque `plugins_url()` est indisponible, `visibloc_jlg_get_asset_url()` renvoie un chemin de fichier absolu. Cette valeur est difficilement exploitable par WordPress (qui attend une URL) et peut révéler la structure serveur. Mieux vaudrait retourner une chaîne vide ou lever une alerte explicite. 【F:visi-bloc-jlg/includes/assets.php†L85-L94】

## Notes sur le debogage visuel
- Le nouveau style supprimait l'outline de focus des blocs marqués (hidden/fallback), ce qui faisait disparaître l'indicateur de sélection clavier. L'ajout d'un outline personnalisé résout ce problème d'accessibilité tout en conservant l'esthétique du badge. 【F:visi-bloc-jlg/src/editor-styles.css†L121-L135】

## Pistes complémentaires
- Couvrir les helpers exposés (normalisation, lecture de cookie, etc.) par des tests unitaires supplémentaires pour détecter rapidement les régressions de type/valeur.
- Ajouter une documentation développeur décrivant les hooks publics et les points d'extension disponibles.
