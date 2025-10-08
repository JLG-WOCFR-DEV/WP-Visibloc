This directory stores the compiled translation files for the Visi-Bloc - JLG plugin.

## Workflow de traduction

1. Extraire les chaînes :
   ```bash
   npm run translate:extract
   ```
2. Mettre à jour les fichiers `.po` depuis le `.pot` généré (`languages/visibloc-jlg.pot`).
3. Compiler les traductions :
   ```bash
   npm run translate:compile
   ```
4. Vérifier les accents et ponctuations via `msgfmt --check` avant de committer.

## TODO localisation

| Élément | Action | Statut |
| --- | --- | --- |
| Nouvelles fonctionnalités (assistant, notifications, heatmaps) | Ajouter les chaînes en anglais/français dans le `.pot` et préparer les locales prioritaires (fr_FR, en_US). | À faire |
| Glossaire | Formaliser un glossaire produit (rôles, règles, fallback) pour garantir la cohérence terminologique. | À faire |
| Automatisation CI | Ajouter un job GitHub Actions déclenchant l'extraction/compilation pour détecter les oublis de chaînes. | À étudier |

> 🔁 Pensez à synchroniser cette checklist avec `README.md > Synthèse opérationnelle` lorsque de nouvelles langues ou fonctionnalités sont planifiées.
