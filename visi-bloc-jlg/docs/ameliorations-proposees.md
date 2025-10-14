# Idées d'améliorations à coder

## 1. Analytics et table des insights
- Finaliser l'installateur de la table `wp_visibloc_insights` via `dbDelta`.
- Centraliser l'écriture et la lecture des événements dans un dépôt (`Visibloc_Insights_Repository`).
- Exposer un endpoint REST (`visibloc-jlg/v1/insights`) pour capter les événements front.
- Ajouter une file de secours dans le script front pour les appels réseau.

## 2. Onboarding et adoption
- Étendre la checklist avec la progression par rôle et la synchronisation multi-admin.
- Ajouter un mode "guidé" qui installe des recettes prédéfinies dans l'éditeur.
- Déployer des notifications proactives lorsque des règles restent incomplètes.

## 3. Gouvernance et audit
- Implémenter un audit log des modifications de règles et des accès front.
- Proposer des exports CSV/JSON depuis l'admin Insights.
- Autoriser des webhooks sortants sur les événements critiques.

## 4. Expérience développeur
- Documenter les hooks existants et ajouter des exemples de recettes.
- Couvrir les services critiques avec des tests unitaires (repository, REST, onboarding).
- Intégrer une analyse statique (PHPStan) et un lint JS pour les scripts buildés.

## 5. Accessibilité et performance
- Vérifier le contraste et la navigation clavier des écrans admin.
- Charger les assets front conditionnellement aux blocs utilisés.
- Mettre en cache les segments d'audience et résultats de règles fréquemment demandés.

