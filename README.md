# Visi-Bloc - JLG

Visi-Bloc – JLG is a WordPress plugin that adds advanced visibility controls to Gutenberg blocks.

## Features
- **Role-based visibility** – restrict blocks to selected roles or logged in/out users.
- **Scheduling** – set start and end dates for blocks to appear.
- **Manual hide** – hide blocks from the front-end while still allowing preview for permitted roles.
- **Device visibility utilities** – apply classes such as `vb-hide-on-mobile`, `vb-mobile-only`, `vb-tablet-only`, or `vb-desktop-only` to control display based on screen width.
- **Role preview switcher** – administrators can preview the site as another role directly from the toolbar.

## Installation
1. Download or clone this repository into the `wp-content/plugins/` directory of your WordPress installation.
2. Ensure the plugin folder is named `visi-bloc-jlg`.
3. Activate **Visi-Bloc - JLG** through the **Plugins** screen in WordPress.
4. Configure preview roles and device breakpoints from the **Visi-Bloc - JLG** settings page.

## Usage
- In the block editor, select a block and open its settings panel to adjust visibility options:
  - Choose visibility roles or limit the block to logged-in/logged-out users.
  - Enable scheduling and specify start and end dates.
  - Toggle "Hide block" to keep it off the public site while showing a dashed outline in preview.
- Administrators can switch the preview role from the top toolbar to test visibility as different users.

## Build & Dependencies
The plugin depends on standard WordPress core components and ships with compiled editor assets in the `build/` directory.
No additional build step is required for normal installation. If you modify the source JavaScript, rebuild assets with Node.js tools such as `@wordpress/scripts` (`npm install` then `npm run build`).

