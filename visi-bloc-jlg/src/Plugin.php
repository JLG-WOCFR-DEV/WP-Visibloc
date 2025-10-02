<?php

namespace VisiBloc;

class Plugin
{
    /**
     * @var string
     */
    private $plugin_dir;

    /**
     * @var bool
     */
    private $bootstrapped = false;

    /**
     * @param string $plugin_dir
     */
    public function __construct($plugin_dir)
    {
        $this->plugin_dir = rtrim($plugin_dir, '/\\');
    }

    public function bootstrap()
    {
        if ($this->bootstrapped) {
            return;
        }

        $this->bootstrapped = true;
        $this->load_dependencies();
    }

    private function load_dependencies()
    {
        $includes_dir = $this->plugin_dir . '/includes';

        $files = [
            'block-utils.php',
            'datetime-utils.php',
            'admin-settings.php',
            'assets.php',
            'visibility-logic.php',
            'i18n-inline.php',
            'role-switcher.php',
        ];

        foreach ($files as $file) {
            $path = $includes_dir . '/' . $file;

            if (file_exists($path)) {
                require_once $path;
            }
        }

        $cli_path = $includes_dir . '/cli.php';

        if (defined('WP_CLI') && WP_CLI && file_exists($cli_path)) {
            require_once $cli_path;
        }
    }
}
