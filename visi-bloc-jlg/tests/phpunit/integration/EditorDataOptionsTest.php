<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../includes/assets.php';

final class EditorDataOptionsTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        visibloc_test_reset_block_editor_data();
    }

    protected function tearDown(): void {
        visibloc_test_reset_block_editor_data();

        parent::tearDown();
    }

    public function test_post_types_include_fse_types(): void {
        visibloc_test_set_post_types(
            [
                'post'          => (object) [
                    'public'             => true,
                    'publicly_queryable' => true,
                    'show_in_rest'       => true,
                    'labels'             => (object) [ 'singular_name' => 'Article' ],
                    'label'              => 'Articles',
                ],
                'wp_template'   => (object) [
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_in_rest'       => true,
                    'labels'             => (object) [ 'singular_name' => 'Modèle du site' ],
                    'label'              => 'Modèles du site',
                ],
                'wp_navigation' => (object) [
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_in_rest'       => true,
                    'labels'             => (object) [ 'singular_name' => 'Navigation' ],
                    'label'              => 'Navigations',
                ],
                'private_type'  => (object) [
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_in_rest'       => false,
                    'labels'             => (object) [ 'singular_name' => 'Privé' ],
                ],
            ]
        );

        $post_types = visibloc_jlg_get_editor_post_types();
        $values     = array_map(
            static fn( $item ) => $item['value'] ?? '',
            $post_types
        );

        $this->assertContains( 'post', $values );
        $this->assertContains( 'wp_template', $values );
        $this->assertContains( 'wp_navigation', $values );
        $this->assertNotContains( 'private_type', $values );
    }

    /**
     * @param array<int, array<string, mixed>> $options Options to flatten.
     * @return string[]
     */
    private function flattenTemplateValues( array $options ): array {
        $values = [];

        foreach ( $options as $option ) {
            if ( isset( $option['options'] ) && is_array( $option['options'] ) ) {
                $values = array_merge( $values, $this->flattenTemplateValues( $option['options'] ) );
                continue;
            }

            if ( isset( $option['value'] ) ) {
                $values[] = (string) $option['value'];
            }
        }

        return $values;
    }

    public function test_templates_include_block_templates_and_parts(): void {
        visibloc_test_set_theme_page_templates(
            [
                'Landing Page' => 'page-landing.php',
            ]
        );

        visibloc_test_set_block_templates(
            [
                [
                    'id'    => 'test-theme//wp_template//front-page',
                    'title' => 'Accueil',
                    'type'  => 'wp_template',
                    'slug'  => 'front-page',
                    'theme' => 'test-theme',
                ],
            ]
        );

        visibloc_test_set_block_template_parts(
            [
                [
                    'id'    => 'test-theme//wp_template_part//header',
                    'title' => 'En-tête',
                    'type'  => 'wp_template_part',
                    'slug'  => 'header',
                    'theme' => 'test-theme',
                ],
            ]
        );

        $templates = visibloc_jlg_get_editor_templates();

        $this->assertNotEmpty( $templates );
        $this->assertSame( '', $templates[0]['value'] );

        $all_values = $this->flattenTemplateValues( $templates );

        $this->assertContains( 'test-theme//wp_template//front-page', $all_values );
        $this->assertContains( 'test-theme//wp_template_part//header', $all_values );
        $this->assertContains( 'page-landing.php', $all_values );
    }
}
