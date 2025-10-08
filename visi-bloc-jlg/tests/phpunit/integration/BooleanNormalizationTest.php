<?php

use PHPUnit\Framework\TestCase;
use VisiBloc\Plugin;

require_once dirname( __DIR__, 3 ) . '/includes/utils.php';
require_once dirname( __DIR__, 3 ) . '/src/Plugin.php';

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( $file );
    }
}

/**
 * @covers ::visibloc_jlg_normalize_boolean_value
 * @covers ::visibloc_jlg_normalize_boolean
 * @covers \VisiBloc\Plugin::normalize_boolean
 */
class BooleanNormalizationTest extends TestCase {
    /**
     * @dataProvider truthyValuesProvider
     */
    public function test_helper_returns_true_for_truthy_values( $value ) {
        $this->assertTrue( visibloc_jlg_normalize_boolean_value( $value ) );
    }

    /**
     * @dataProvider falsyValuesProvider
     */
    public function test_helper_returns_false_for_falsy_values( $value ) {
        $this->assertFalse( visibloc_jlg_normalize_boolean_value( $value ) );
    }

    public function test_plugin_method_leverages_shared_helper() {
        $plugin = new Plugin( dirname( __DIR__, 3 ) . '/visi-bloc-jlg.php' );

        $this->assertSame(
            visibloc_jlg_normalize_boolean_value( '1' ),
            $plugin->normalize_boolean( '1' )
        );

        $this->assertSame(
            visibloc_jlg_normalize_boolean_value( 'nope' ),
            $plugin->normalize_boolean( 'nope' )
        );
    }

    public static function truthyValuesProvider() {
        return [
            'string true'      => [ 'true' ],
            'string yes'       => [ 'yes' ],
            'integer one'      => [ 1 ],
            'float positive'   => [ 0.5 ],
            'boolean true'     => [ true ],
            'uppercase string' => [ 'ON' ],
        ];
    }

    public static function falsyValuesProvider() {
        return [
            'empty string' => [ '' ],
            'zero string'  => [ '0' ],
            'zero int'     => [ 0 ],
            'null'         => [ null ],
            'array'        => [ [] ],
            'object'       => [ (object) [] ],
            'random text'  => [ 'banana' ],
        ];
    }
}
