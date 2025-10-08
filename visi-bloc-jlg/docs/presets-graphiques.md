# Presets graphiques proposés pour Visibloc

Ce document présente plusieurs presets graphiques prêts à l'emploi pour accélérer la conception d'interfaces autour du plugin Visi-Bloc – JLG. Chaque preset dispose désormais d’un fichier CSS chargeable à la demande dans `assets/presets/`. Les styles sont exposés via des attributs `data-visibloc-preset` et des classes utilitaires (`visibloc-preset--slug`) utilisables sur l’interface Gutenberg et dans l’administration.

Chaque preset reprend les codes visuels et les interactions de bibliothèques populaires comme Headless UI, shadcn/ui, Radix UI, Bootstrap, Semantic UI et Anime.js, tout en indiquant comment décliner ces inspirations dans l'écosystème WordPress/Gutenberg.

## 1. Preset « Headless Fluent » (inspiré de Headless UI)
- **Philosophie** : composants sans styles imposés, basés sur l'accessibilité, personnalisables via Tailwind ou tokens internes.
- **Tokens suggérés** : palette neutre (`slate-50` à `slate-900`), accent principal `#2563EB`, radius `8px`, ombres légères (`shadow-md`).
- **Composants clés** : Dialog, Popover, Tabs et Combobox s'appuyant sur les hooks d'état WordPress (`@wordpress/data`).
- **Interactions** : transitions discrètes (`transition ease-out duration-150`), focus states visibles (`outline-2 outline-offset-2`).
- **Intégration Gutenberg** : utiliser `@wordpress/components` pour les primitives (Button, TextControl) en surcouchant les classes Tailwind injectées via `postcss-preset-env`.

## 2. Preset « Shadcn Minimal » (inspiré de shadcn/ui)
- **Philosophie** : design system minimaliste basé sur Radix primitives + Tailwind, avec une hiérarchie typographique affirmée.
- **Tokens suggérés** : typographie `"Inter"`, scale `--font-size-xs` à `--font-size-4xl`, couleurs `--primary: #111827`, `--primary-foreground: #F9FAFB`, `--muted: #E5E7EB`.
- **Composants clés** : Command Palette (`Cmd+K`), Sheet latéral pour les paramètres avancés, Badge de statut pour les règles.
- **Interactions** : micro-animations sur hover (`scale-102`, `bg-muted/60`), skeleton loaders pour les panneaux lourds.
- **Intégration Gutenberg** : générer les classes utilitaires dans `assets/scss/_tokens.scss` et fournir un preset de couleurs dans `theme.json` pour harmoniser l'éditeur.

## 3. Preset « Radix Structured » (inspiré de Radix UI)
- **Philosophie** : composants modulaires, orientés accessibilité, avec un focus sur les états contrôlés/non contrôlés.
- **Tokens suggérés** : palette `Radix Gray` + `Radix Violet`, radius progressifs (`4px`, `6px`, `8px`), spacing basé sur l'échelle 4 (`4px`, `8px`, `12px`, `16px`).
- **Composants clés** : Slider pour les fenêtres temporelles, Collapsible pour les règles avancées, Toast de feedback système.
- **Interactions** : animations `@radix-ui/react-toast` adaptées en CSS via `@keyframes slideIn`, `fadeOut`.
- **Intégration Gutenberg** : encapsuler les primitives dans des composants React (`packages/components`) et distribuer une feuille CSS générée via CSS Modules pour éviter les collisions.

## 4. Preset « Bootstrap Express »
- **Philosophie** : adoption rapide, repères visuels classiques, forte lisibilité sur bureau et mobile.
- **Tokens suggérés** : palette `Primary #0D6EFD`, `Success #198754`, `Warning #FFC107`, `Danger #DC3545`; typographie `"Helvetica Neue", Arial, sans-serif`; radius `0.375rem`.
- **Composants clés** : Navbar secondaire pour les onglets d'options, Accordions pour les groupes de règles, Alerts pour les conflits détectés.
- **Interactions** : transitions CSS standard (`transition: all .2s ease-in-out`), `box-shadow` accentué sur les modals.
- **Intégration Gutenberg** : importer uniquement les modules SCSS nécessaires (`buttons`, `forms`, `utilities`) via `sass-loader` afin de limiter le poids, et mapper les variables Bootstrap avec celles du Customizer.

## 5. Preset « Semantic Harmony » (inspiré de Semantic UI)
- **Philosophie** : interface expressive, labels descriptifs, thèmes déclinables via variables CSS.
- **Tokens suggérés** : `--brand-hue: 205`, `--brand-saturation: 80%`, `--brand-lightness: 45%`; typographie `"Lato"`; `border-radius: 0.28571429rem`.
- **Composants clés** : Steps pour l'onboarding, Cards empilées pour visualiser les scénarios, Dropdown multi-sélection pour les segments.
- **Interactions** : `box-shadow` dynamique (`0 2px 8px rgba(34,36,38,0.12)`), animations `slide down` sur les menus contextuels.
- **Intégration Gutenberg** : définir des mixins Sass pour générer les variations (positive, negative, info) et exposer un JSON de thème chargeable depuis l'administration.

## 6. Preset « Anime Kinetic » (inspiré d'Anime.js)
- **Philosophie** : expérience dynamique axée sur les transitions et animations scénarisées.
- **Tokens suggérés** : couleurs saturées (`#F97316`, `#10B981`, `#3B82F6`), gradients linéaires, usage de `clamp()` pour la typographie responsive.
- **Composants clés** : Timeline visuelle des règles programmées, indicateurs pulsés pour les alertes, loaders illustrés.
- **Interactions** : animations orchestrées via Anime.js (`targets: '.vb-rule-card'`, `translateY`, `opacity`, `stagger`), déclenchement conditionnel lors de l'apparition (`IntersectionObserver`).
- **Intégration Gutenberg** : charger Anime.js uniquement sur les écrans d'administration concernés, fournir des hooks React (`useAnimeTimeline`) et documenter les préférences de réduction de mouvement (`prefers-reduced-motion`).

## Recommandations transverses
- **Accessibilité** : chaque preset doit conserver un contraste AA/AAA, gérer les focus states, respecter les rôles ARIA et offrir un mode réduit de mouvement.
- **Thématisation** : exposer les tokens via des variables CSS (`:root { --vb-color-primary: ... }`) et permettre leur surcharge depuis le Customizer ou `theme.json`.
- **Performance** : livrer les CSS via `assets/build/presets/{preset}.css` chargés à la demande et regrouper les scripts optionnels (Anime.js) sous forme de chunks dynamiques.
- **Documentation** : ajouter une section par preset dans le guide d'onboarding, avec captures d'écran, exemples de blocs et snippets de configuration.

## Checklist d'implémentation

| Preset | Fichier CSS dédié | Tokens documentés | Captures/Guides | Statut |
| --- | --- | --- | --- | --- |
| Headless Fluent | `assets/presets/headless-fluent.css` à générer depuis `scss/presets/_headless-fluent.scss`. | Palette + échelles `spacing`, `radius`. | À produire (screens Gutenberg + admin). | ⏳ À faire |
| Shadcn Minimal | `assets/presets/shadcn-minimal.css` via build Tailwind restreint. | Typographie `Inter`, mapping `--primary`, `--muted`. | Storyboard du Command Palette + sheet latéral. | ⏳ À faire |
| Radix Structured | `assets/presets/radix-structured.css` avec animations `toast`. | Tokens `radius`, `elevation`, `motion`. | GIF démontrant slider + collapsible. | ⏳ À faire |
| Bootstrap Express | `assets/presets/bootstrap-express.css` sélectionnant modules nécessaires. | Table de correspondance Bootstrap ↔ Visibloc. | Captures de la navbar secondaire + alertes. | 🔄 En cours (sélection modules). |
| Semantic Harmony | `assets/presets/semantic-harmony.css` alimenté par variables CSS. | Documentation `--brand-*` + variations d'état. | Modèles Steps/Cards commentés. | ⏳ À faire |
| Anime Kinetic | `assets/presets/anime-kinetic.css` + chunk JS `anime-kinetic.js`. | Tokens `gradient`, `motion`, `timing`. | Vidéo courte sur timeline + loaders. | ⏳ À faire |

> 🗂️ **Organisation** – Créer un dossier `docs/presets/` pour héberger les captures et guides au fur et à mesure, puis référencer chaque ressource dans la documentation produit principale.

