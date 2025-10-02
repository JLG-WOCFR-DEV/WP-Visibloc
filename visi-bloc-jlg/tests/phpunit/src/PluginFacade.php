<?php

namespace Visibloc\Tests\Support;

use BadMethodCallException;

final class PluginFacade
{
    private function call(string $function, array $arguments = [])
    {
        if (! function_exists($function)) {
            throw new BadMethodCallException(
                sprintf('Expected function %s() to be available for testing.', $function)
            );
        }

        return call_user_func_array($function, $arguments);
    }

    public function addRoleSwitcherMenu(object $adminBar): void
    {
        $this->call('visibloc_jlg_add_role_switcher_menu', [$adminBar]);
    }

    public function clearCaches($postId = null): void
    {
        $this->call('visibloc_jlg_clear_caches', [$postId]);
    }

    public function collectGroupBlockMetadata(): array
    {
        $result = $this->call('visibloc_jlg_collect_group_block_metadata');

        return is_array($result) ? $result : [];
    }

    public function enqueueEditorAssets(): void
    {
        $this->call('visibloc_jlg_enqueue_editor_assets');
    }

    public function filterUserCapabilities(array $allcaps, array $caps, array $args, $user): array
    {
        $result = $this->call('visibloc_jlg_filter_user_capabilities', [$allcaps, $caps, $args, $user]);

        return is_array($result) ? $result : $allcaps;
    }

    public function generateDeviceVisibilityCss(bool $canPreview, ?int $mobileBreakpoint = null, ?int $tabletBreakpoint = null): string
    {
        return (string) $this->call(
            'visibloc_jlg_generate_device_visibility_css',
            [$canPreview, $mobileBreakpoint, $tabletBreakpoint]
        );
    }

    public function generateGroupBlockSummaryFromContent($postId, $content = null, $blockMatcher = null): array
    {
        $result = $this->call('visibloc_jlg_generate_group_block_summary_from_content', [$postId, $content, $blockMatcher]);

        return is_array($result) ? $result : [];
    }

    public function getDisplayFallbackForSelector(string $selector): ?string
    {
        $result = $this->call('visibloc_jlg_get_display_fallback_for_selector', [$selector]);

        return null === $result ? null : (string) $result;
    }

    public function getGroupBlockSummaryIndex(): array
    {
        $result = $this->call('visibloc_jlg_get_group_block_summary_index');

        return is_array($result) ? $result : [];
    }

    public function getPreviewCookieExpirationTime($referenceTime = null): int
    {
        return (int) $this->call('visibloc_jlg_get_preview_cookie_expiration_time', [$referenceTime]);
    }

    public function getPreviewRoleFromCookie(): string
    {
        return (string) $this->call('visibloc_jlg_get_preview_role_from_cookie');
    }

    public function getPreviewRuntimeContext(bool $resetCache = false): array
    {
        $result = $this->call('visibloc_jlg_get_preview_runtime_context', [$resetCache]);

        return is_array($result) ? $result : [];
    }

    public function getPreviewSwitchBaseUrl(): string
    {
        return (string) $this->call('visibloc_jlg_get_preview_switch_base_url');
    }

    public function getSanitizedQueryArg(string $key): string
    {
        return (string) $this->call('visibloc_jlg_get_sanitized_query_arg', [$key]);
    }

    public function getSupportedBlocks(): array
    {
        $result = $this->call('visibloc_jlg_get_supported_blocks');

        return is_array($result) ? $result : [];
    }

    public function groupPostsById($posts): array
    {
        $result = $this->call('visibloc_jlg_group_posts_by_id', [$posts]);

        return is_array($result) ? $result : [];
    }

    public function handleRoleSwitching(): void
    {
        $this->call('visibloc_jlg_handle_role_switching');
    }

    public function isAdminOrTechnicalRequest(): bool
    {
        return (bool) $this->call('visibloc_jlg_is_admin_or_technical_request');
    }

    public function missingEditorAssets(): bool
    {
        if (! function_exists('visibloc_jlg_missing_editor_assets')) {
            return false;
        }

        $result = $this->call('visibloc_jlg_missing_editor_assets');

        return (bool) $result;
    }

    public function normalizeBlockDeclarations(string $selector, $declaration): array
    {
        $result = $this->call('visibloc_jlg_normalize_block_declarations', [$selector, $declaration]);

        return is_array($result) ? $result : [];
    }

    public function parseScheduleDatetime($value): ?int
    {
        $result = $this->call('visibloc_jlg_parse_schedule_datetime', [$value]);

        if (null === $result) {
            return null;
        }

        return is_numeric($result) ? (int) $result : null;
    }

    public function purgePreviewCookie(): bool
    {
        return (bool) $this->call('visibloc_jlg_purge_preview_cookie');
    }

    public function rebuildGroupBlockSummaryIndex(?int &$scannedPosts = null): array
    {
        $result = $this->call('visibloc_jlg_rebuild_group_block_summary_index', [&$scannedPosts]);

        return is_array($result) ? $result : [];
    }

    public function renderBlockFilter(string $content, array $block): string
    {
        return (string) $this->call('visibloc_jlg_render_block_filter', [$content, $block]);
    }

    public function renderMissingEditorAssetsNotice(): void
    {
        $this->call('visibloc_jlg_render_missing_editor_assets_notice');
    }

    public function storeGroupBlockSummaryIndex(array $index): void
    {
        $this->call('visibloc_jlg_store_group_block_summary_index', [$index]);
    }

    public function storeRealUserId($userId): void
    {
        $this->call('visibloc_jlg_store_real_user_id', [$userId]);
    }
}
