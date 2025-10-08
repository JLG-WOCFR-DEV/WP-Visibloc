<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/plugin-meta.php';

if ( ! function_exists( 'visibloc_jlg_get_visual_presets_definitions' ) ) {
    /**
     * Return the curated list of visual presets available for the UI.
     *
     * @return array<int, array<string, mixed>>
     */
    function visibloc_jlg_get_visual_presets_definitions() {
        static $presets = null;

        if ( null !== $presets ) {
            return $presets;
        }

        $default_version = visibloc_jlg_get_plugin_version();
        $base_dir        = 'assets/presets';

        $definitions = [
            'headless-fluent' => [
                'label'        => __( 'Headless Fluent', 'visi-bloc-jlg' ),
                'description'  => __( 'Composants fluides et accessibles inspirés de Headless UI avec un accent bleu électrique.', 'visi-bloc-jlg' ),
                'inspiration'  => 'Headless UI',
                'components'   => [
                    __( 'Dialogues et popovers discrets', 'visi-bloc-jlg' ),
                    __( 'Panneaux contextuels à transitions courtes', 'visi-bloc-jlg' ),
                    __( 'Boutons primaires très contrastés', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Animations ease-out rapides (150 ms)', 'visi-bloc-jlg' ),
                    __( 'Focus visible renforcé', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => '#ffffff',
                    'surfaceSubtle'  => '#f1f5f9',
                    'borderSubtle'   => 'rgba(15, 23, 42, 0.06)',
                    'borderStrong'   => 'rgba(15, 23, 42, 0.16)',
                    'accent'         => '#2563eb',
                    'accentStrong'   => '#1d4ed8',
                    'shadowSubtle'   => '0 10px 24px -22px rgba(15, 23, 42, 0.45)',
                    'shadowElevated' => '0 40px 80px -48px rgba(15, 23, 42, 0.35)',
                    'radiusBase'     => '8px',
                    'fontFamily'     => 'Inter',
                ],
                'class_name'   => 'visibloc-preset--headless-fluent',
                'file'         => 'headless-fluent.css',
            ],
            'shadcn-minimal' => [
                'label'        => __( 'Shadcn Minimal', 'visi-bloc-jlg' ),
                'description'  => __( 'Preset typographique minimaliste inspiré de shadcn/ui, idéal pour les réglages sobrement hiérarchisés.', 'visi-bloc-jlg' ),
                'inspiration'  => 'shadcn/ui',
                'components'   => [
                    __( 'Cartes avec bordure double', 'visi-bloc-jlg' ),
                    __( 'Boutons minimalistes à coins doux', 'visi-bloc-jlg' ),
                    __( 'Champs de formulaire tabulaires', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Ombrages doux et progressifs', 'visi-bloc-jlg' ),
                    __( 'Hover subtil sur les cartes', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => '#ffffff',
                    'surfaceSubtle'  => '#f9fafb',
                    'borderSubtle'   => 'rgba(17, 24, 39, 0.08)',
                    'borderStrong'   => 'rgba(17, 24, 39, 0.16)',
                    'accent'         => '#111827',
                    'accentStrong'   => '#0b1120',
                    'shadowSubtle'   => '0 12px 26px -24px rgba(17, 24, 39, 0.35)',
                    'shadowElevated' => '0 30px 70px -45px rgba(17, 24, 39, 0.4)',
                    'radiusBase'     => '10px',
                    'fontFamily'     => 'Inter',
                ],
                'class_name'   => 'visibloc-preset--shadcn-minimal',
                'file'         => 'shadcn-minimal.css',
            ],
            'radix-structured' => [
                'label'        => __( 'Radix Structured', 'visi-bloc-jlg' ),
                'description'  => __( 'Palette violette structurée pour mettre en avant sliders, toggles et cartes empilées.', 'visi-bloc-jlg' ),
                'inspiration'  => 'Radix UI',
                'components'   => [
                    __( 'Cartes à rayons progressifs', 'visi-bloc-jlg' ),
                    __( 'Groupes de boutons segmentés', 'visi-bloc-jlg' ),
                    __( 'Badges informatifs sans capitalisation', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Focus ring violet soutenu', 'visi-bloc-jlg' ),
                    __( 'Transitions sur les hover de cartes et sliders', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => '#f9f8ff',
                    'surfaceSubtle'  => '#f4f1ff',
                    'borderSubtle'   => 'rgba(76, 29, 149, 0.12)',
                    'borderStrong'   => 'rgba(76, 29, 149, 0.28)',
                    'accent'         => '#6d28d9',
                    'accentStrong'   => '#4c1d95',
                    'shadowSubtle'   => '0 18px 46px -38px rgba(58, 27, 126, 0.45)',
                    'shadowElevated' => '0 42px 120px -64px rgba(58, 27, 126, 0.5)',
                    'radiusBase'     => '10px',
                    'fontFamily'     => 'IBM Plex Sans',
                ],
                'class_name'   => 'visibloc-preset--radix-structured',
                'file'         => 'radix-structured.css',
            ],
            'bootstrap-express' => [
                'label'        => __( 'Bootstrap Express', 'visi-bloc-jlg' ),
                'description'  => __( 'Réinterprétation moderne des codes Bootstrap pour une prise en main rapide.', 'visi-bloc-jlg' ),
                'inspiration'  => 'Bootstrap',
                'components'   => [
                    __( 'Boutons pilules contrastés', 'visi-bloc-jlg' ),
                    __( 'Cartes avec bordure bleutée', 'visi-bloc-jlg' ),
                    __( 'Panneaux aux coins arrondis', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Focus ring accentué de 4px', 'visi-bloc-jlg' ),
                    __( 'Majuscules légères sur les boutons principaux', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => '#ffffff',
                    'surfaceSubtle'  => '#f8f9fa',
                    'borderSubtle'   => 'rgba(13, 110, 253, 0.12)',
                    'borderStrong'   => 'rgba(13, 110, 253, 0.28)',
                    'accent'         => '#0d6efd',
                    'accentStrong'   => '#0b5ed7',
                    'shadowSubtle'   => '0 10px 30px -22px rgba(13, 110, 253, 0.4)',
                    'shadowElevated' => '0 38px 90px -60px rgba(13, 110, 253, 0.35)',
                    'radiusBase'     => '12px',
                    'fontFamily'     => 'Helvetica Neue',
                ],
                'class_name'   => 'visibloc-preset--bootstrap-express',
                'file'         => 'bootstrap-express.css',
            ],
            'semantic-harmony' => [
                'label'        => __( 'Semantic Harmony', 'visi-bloc-jlg' ),
                'description'  => __( 'Palette expressive aux accents bleu lagon reprenant les codes Semantic UI.', 'visi-bloc-jlg' ),
                'inspiration'  => 'Semantic UI',
                'components'   => [
                    __( 'Badges et steps en capitales espacées', 'visi-bloc-jlg' ),
                    __( 'Cartes aux dégradés radiaux', 'visi-bloc-jlg' ),
                    __( 'Boutons massifs à ombres diffuses', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Transitions douces sur les cartes', 'visi-bloc-jlg' ),
                    __( 'Focus bleu cyan lumineux', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => 'hsl(0 0% 100%)',
                    'surfaceSubtle'  => 'hsl(210 40% 96%)',
                    'borderSubtle'   => 'hsla(205, 60%, 40%, 0.18)',
                    'borderStrong'   => 'hsla(205, 70%, 38%, 0.32)',
                    'accent'         => 'hsl(205 80% 45%)',
                    'accentStrong'   => 'hsl(205 85% 34%)',
                    'shadowSubtle'   => '0 18px 52px -44px hsla(205, 70%, 32%, 0.55)',
                    'shadowElevated' => '0 54px 140px -72px hsla(205, 70%, 32%, 0.5)',
                    'radiusBase'     => '10px',
                    'fontFamily'     => 'Lato',
                ],
                'class_name'   => 'visibloc-preset--semantic-harmony',
                'file'         => 'semantic-harmony.css',
            ],
            'anime-kinetic' => [
                'label'        => __( 'Anime Kinetic', 'visi-bloc-jlg' ),
                'description'  => __( 'Preset vibrant avec dégradés et micro-animations inspirés d’Anime.js.', 'visi-bloc-jlg' ),
                'inspiration'  => 'Anime.js',
                'components'   => [
                    __( 'Badges pulsés et lumineux', 'visi-bloc-jlg' ),
                    __( 'Panneaux à fond animé', 'visi-bloc-jlg' ),
                    __( 'Boutons CTA à gradient dynamique', 'visi-bloc-jlg' ),
                ],
                'interactions' => [
                    __( 'Animations pulsées et comètes', 'visi-bloc-jlg' ),
                    __( 'Gradients animés sur les boutons', 'visi-bloc-jlg' ),
                ],
                'tokens'       => [
                    'surface'        => '#0f172a',
                    'surfaceSubtle'  => 'rgba(15, 23, 42, 0.94)',
                    'borderSubtle'   => 'rgba(56, 189, 248, 0.32)',
                    'borderStrong'   => 'rgba(14, 165, 233, 0.48)',
                    'accent'         => '#f97316',
                    'accentStrong'   => '#22d3ee',
                    'shadowSubtle'   => '0 18px 60px -50px rgba(14, 165, 233, 0.6)',
                    'shadowElevated' => '0 68px 160px -90px rgba(79, 70, 229, 0.65)',
                    'radiusBase'     => '14px',
                    'fontFamily'     => 'Plus Jakarta Sans',
                ],
                'class_name'   => 'visibloc-preset--anime-kinetic',
                'file'         => 'anime-kinetic.css',
            ],
        ];

        $presets = [];

        foreach ( $definitions as $slug => $data ) {
            if ( ! is_string( $slug ) || '' === $slug ) {
                continue;
            }

            $file = isset( $data['file'] ) ? (string) $data['file'] : '';
            $file = ltrim( $file, '/' );

            if ( '' === $file ) {
                continue;
            }

            $relative_path = $base_dir . '/' . $file;
            $handle        = 'visibloc-jlg-preset-' . str_replace( '_', '-', sanitize_key( $slug ) );
            $stylesheet    = function_exists( 'visibloc_jlg_get_asset_url' )
                ? visibloc_jlg_get_asset_url( $relative_path )
                : '';
            $version       = function_exists( 'visibloc_jlg_get_asset_version' )
                ? visibloc_jlg_get_asset_version( $relative_path, $default_version )
                : $default_version;

            $presets[] = [
                'slug'            => $slug,
                'name'            => isset( $data['label'] ) ? (string) $data['label'] : $slug,
                'description'     => isset( $data['description'] ) ? (string) $data['description'] : '',
                'inspiration'     => isset( $data['inspiration'] ) ? (string) $data['inspiration'] : '',
                'features'        => [
                    'components'   => isset( $data['components'] ) && is_array( $data['components'] ) ? array_values( $data['components'] ) : [],
                    'interactions' => isset( $data['interactions'] ) && is_array( $data['interactions'] ) ? array_values( $data['interactions'] ) : [],
                ],
                'tokens'          => isset( $data['tokens'] ) && is_array( $data['tokens'] ) ? $data['tokens'] : [],
                'handle'          => $handle,
                'className'       => isset( $data['class_name'] ) ? (string) $data['class_name'] : '',
                'stylesheet'      => $stylesheet,
                'stylesheet_path' => $relative_path,
                'version'         => $version,
            ];
        }

        return $presets;
    }
}

if ( ! function_exists( 'visibloc_jlg_register_visual_preset_styles' ) ) {
    /**
     * Register the preset stylesheets so they can be enqueued on demand.
     *
     * @return void
     */
    function visibloc_jlg_register_visual_preset_styles() {
        if ( ! function_exists( 'wp_register_style' ) ) {
            return;
        }

        foreach ( visibloc_jlg_get_visual_presets_definitions() as $preset ) {
            $handle    = isset( $preset['handle'] ) ? (string) $preset['handle'] : '';
            $stylesheet = isset( $preset['stylesheet'] ) ? (string) $preset['stylesheet'] : '';
            $version    = isset( $preset['version'] ) ? (string) $preset['version'] : '';

            if ( '' === $handle || '' === $stylesheet ) {
                continue;
            }

            wp_register_style( $handle, $stylesheet, [], $version );
        }
    }
}

if ( ! function_exists( 'visibloc_jlg_get_editor_visual_presets' ) ) {
    /**
     * Sanitize preset definitions for editor consumption.
     *
     * @return array<int, array<string, mixed>>
     */
    function visibloc_jlg_get_editor_visual_presets() {
        $presets = [];

        foreach ( visibloc_jlg_get_visual_presets_definitions() as $preset ) {
            $presets[] = [
                'slug'        => (string) ( $preset['slug'] ?? '' ),
                'name'        => (string) ( $preset['name'] ?? '' ),
                'description' => (string) ( $preset['description'] ?? '' ),
                'inspiration' => (string) ( $preset['inspiration'] ?? '' ),
                'features'    => isset( $preset['features'] ) && is_array( $preset['features'] ) ? $preset['features'] : [ 'components' => [], 'interactions' => [] ],
                'tokens'      => isset( $preset['tokens'] ) && is_array( $preset['tokens'] ) ? $preset['tokens'] : [],
                'handle'      => (string) ( $preset['handle'] ?? '' ),
                'className'   => (string) ( $preset['className'] ?? '' ),
                'stylesheet'  => (string) ( $preset['stylesheet'] ?? '' ),
            ];
        }

        return $presets;
    }
}
