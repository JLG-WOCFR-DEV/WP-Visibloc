<?php

namespace Visibloc\Tests\Support;

final class TestServices
{
    private static ?self $instance = null;

    private PluginFacade $plugin;

    private function __construct()
    {
        $this->plugin = new PluginFacade();
    }

    public static function bootstrap(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function plugin(): PluginFacade
    {
        return self::bootstrap()->plugin;
    }
}
