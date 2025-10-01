# Visi-Bloc - JLG

Visi-Bloc – JLG is a WordPress plugin that adds advanced visibility controls to Gutenberg blocks. It lets administrators show or hide blocks for particular audiences, schedule their display, or preview the site as different user roles.

## Features
- **Role-based visibility** – restrict blocks to selected roles or to logged-in/out visitors.
- **Scheduling** – set start and end dates for blocks to appear.
- **Manual hide** – hide blocks from the front end while still previewable to permitted roles.
- **Device visibility utilities** – apply classes like `vb-hide-on-mobile`, `vb-mobile-only`, `vb-tablet-only`, or `vb-desktop-only` to control display by screen width. The generated CSS now includes a `display` fallback to support browsers that lack `display: revert`.
- **Role preview switcher** – administrators (or roles explicitly granted via the `visibloc_jlg_allowed_impersonator_roles` filter) can preview the site as another role from the toolbar.
- **Accessible mobile role switcher** – the front-end dialog now traps keyboard focus, keeps the toggle expanded until dismissed, and marks the rest of the page with `inert`/`aria-hidden` while open to avoid accidental interactions.

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
