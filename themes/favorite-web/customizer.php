<?php
/**
 * Favorite Web Theme Customizer View
 *
 * Full-featured WordPress-style Theme Customizer interface for Favorite Web.
 * Provides controls for site identity, header, homepage sections and ordering,
 * hero, trust stats, about, services, products, packages, memberships,
 * latest articles, CTA, footer, light & dark design tokens, typography, layout,
 * and custom CSS.
 *
 * Loaded inside Universal Core Admin via CustomizeController.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$effectiveConfig = fw_get_config();
$defaults = fw_default_config();

// Ensure active theme and mods are loaded
$themeId = $themeId ?? 'favorite-web';
$themeName = $themeName ?? 'Favorite Web Official';
$csrfToken = csrf_token();

// Load section order and configurations
$layoutSections = $sections ?? [];
if (empty($layoutSections)) {
    try {
        $layoutService = new \FavoriteCMS\Themes\ThemeLayoutService(\FavoriteCMS\Core\Application::getInstance());
        $layoutSections = $layoutService->getSections($themeId);
    } catch (\Throwable) {
        $layoutSections = [];
    }
}

// Extract current values with 1:1 default fallback
$cfg = $effectiveConfig;
$mods = $mods ?? [];

// Helper for input values
$val = static function (string $key, mixed $fallback = '') use ($mods, $cfg, $defaults): string {
    if (isset($mods[$key]) && $mods[$key] !== '') {
        return (string)$mods[$key];
    }
    if (isset($cfg[$key]) && $cfg[$key] !== '') {
        return (string)$cfg[$key];
    }
    return (string)($defaults[$key] ?? $fallback);
};

$boolVal = static function (string $key, bool $fallback = true) use ($mods, $cfg, $defaults): bool {
    if (isset($mods[$key])) {
        return !empty($mods[$key]);
    }
    if (isset($cfg[$key])) {
        return !empty($cfg[$key]);
    }
    return (bool)($defaults[$key] ?? $fallback);
};

// Services items
$servicesItems = $effectiveConfig['services_items'] ?? $defaults['services_items'] ?? [];
$servicesJson = json_encode($servicesItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Stats slots
$stat1Val = $val('stats_1_value', $val('stat_1_number', '10K+'));
$stat1Lbl = $val('stats_1_label', $val('stat_1_label', 'Active Users'));
$stat1Ind = $val('stats_1_indicator', $val('stat_1_desc', 'Worldwide community'));

$stat2Val = $val('stats_2_value', $val('stat_2_number', '99.9%'));
$stat2Lbl = $val('stats_2_label', $val('stat_2_label', 'Uptime'));
$stat2Ind = $val('stats_2_indicator', $val('stat_2_desc', 'Enterprise SLA'));

$stat3Val = $val('stats_3_value', $val('stat_3_number', '250+'));
$stat3Lbl = $val('stats_3_label', $val('stat_3_label', 'Digital Assets'));
$stat3Ind = $val('stats_3_indicator', $val('stat_3_desc', 'Curated quality'));

$stat4Val = $val('stats_4_value', $val('stat_4_number', '24/7'));
$stat4Lbl = $val('stats_4_label', $val('stat_4_label', 'Dedicated Support'));
$stat4Ind = $val('stats_4_indicator', $val('stat_4_desc', 'Expert assistance'));
?>

<div class="fw-customizer-root" id="fw-customizer-root">
    <style>
        /* Scoped Customizer Layout */
        .wp-content:has(.fw-customizer-root) {
            padding: 0 !important;
            max-width: 100% !important;
        }
        .fw-customizer-root {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 46px);
            background: var(--admin-bg, #f8fafc);
            color: var(--admin-text, #1e293b);
            font-family: inherit;
            overflow: hidden;
            margin: -24px -28px;
        }
        @media (max-width: 782px) {
            .fw-customizer-root {
                margin: -16px;
                height: calc(100vh - 46px);
            }
        }

        /* Top Header Bar */
        .fw-customizer-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 18px;
            background: var(--admin-surface, #ffffff);
            border-bottom: 1px solid var(--admin-border, #e2e8f0);
            z-index: 20;
            gap: 12px;
            flex-shrink: 0;
        }
        .fw-topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .fw-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            background: var(--admin-surface-subtle, #f1f5f9);
            color: var(--admin-text, #1e293b);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid var(--admin-border, #cbd5e1);
            transition: all 0.15s ease;
        }
        .fw-back-link:hover {
            background: var(--admin-border, #e2e8f0);
            color: var(--admin-text-heading, #0f172a);
        }
        .fw-theme-pill {
            font-size: 12px;
            font-weight: 600;
            color: var(--admin-primary, #3b82f6);
            background: rgba(59, 130, 246, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        .fw-save-status {
            font-size: 12px;
            color: var(--admin-text-muted, #64748b);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fw-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
        }
        .fw-status-dot.dirty {
            background: #f59e0b;
        }

        /* Topbar Center: Device Controls */
        .fw-device-switcher {
            display: flex;
            align-items: center;
            background: var(--admin-surface-subtle, #f1f5f9);
            border: 1px solid var(--admin-border, #cbd5e1);
            border-radius: 6px;
            padding: 2px;
            gap: 2px;
        }
        .fw-device-btn {
            background: transparent;
            border: none;
            color: var(--admin-text-muted, #64748b);
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .fw-device-btn:hover {
            color: var(--admin-text, #1e293b);
        }
        .fw-device-btn.active {
            background: var(--admin-surface, #ffffff);
            color: var(--admin-primary, #3b82f6);
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        }
        .fw-preview-action-btn {
            background: transparent;
            border: 1px solid var(--admin-border, #cbd5e1);
            color: var(--admin-text, #1e293b);
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            transition: all 0.15s;
        }
        .fw-preview-action-btn:hover {
            background: var(--admin-surface-subtle, #f1f5f9);
        }

        /* Topbar Right: Actions */
        .fw-topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fw-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .fw-btn--primary {
            background: var(--admin-primary, #3b82f6);
            color: #ffffff;
        }
        .fw-btn--primary:hover {
            background: var(--admin-primary-hover, #2563eb);
        }
        .fw-btn--primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .fw-btn--danger-outline {
            background: transparent;
            border-color: var(--admin-danger-border, #fca5a5);
            color: var(--admin-danger, #ef4444);
        }
        .fw-btn--danger-outline:hover {
            background: var(--admin-danger-bg, rgba(239, 68, 68, 0.1));
        }

        /* Workspace Main Body */
        .fw-customizer-workspace {
            display: flex;
            flex: 1;
            height: calc(100% - 56px);
            overflow: hidden;
            position: relative;
        }

        /* Left Controls Sidebar */
        .fw-customizer-sidebar {
            width: 400px;
            min-width: 360px;
            max-width: 480px;
            background: var(--admin-surface, #ffffff);
            border-right: 1px solid var(--admin-border, #e2e8f0);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            flex-shrink: 0;
            z-index: 10;
        }
        .fw-sidebar-content {
            padding: 14px;
            flex: 1;
        }

        /* Accordion Panels */
        .fw-accordion {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .fw-panel {
            border: 1px solid var(--admin-border, #e2e8f0);
            border-radius: 8px;
            background: var(--admin-surface, #ffffff);
            overflow: hidden;
            transition: border-color 0.15s ease;
        }
        .fw-panel.is-open {
            border-color: var(--admin-primary, #3b82f6);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .fw-panel-header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            background: var(--admin-surface, #ffffff);
            border: none;
            color: var(--admin-text-heading, #0f172a);
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            user-select: none;
            transition: background 0.15s ease;
        }
        .fw-panel-header:hover {
            background: var(--admin-surface-subtle, #f8fafc);
        }
        .fw-panel-header .fw-panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .fw-panel-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        .fw-panel-arrow {
            font-size: 14px;
            color: var(--admin-text-muted, #94a3b8);
            transition: transform 0.2s ease;
        }
        .fw-panel.is-open .fw-panel-arrow {
            transform: rotate(90deg);
        }
        .fw-panel-body {
            display: none;
            padding: 14px;
            border-top: 1px solid var(--admin-border, #f1f5f9);
            background: var(--admin-surface, #ffffff);
            font-size: 13px;
        }
        .fw-panel.is-open .fw-panel-body {
            display: block;
        }

        /* Form Controls */
        .fw-field {
            margin-bottom: 14px;
        }
        .fw-field:last-child {
            margin-bottom: 0;
        }
        .fw-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--admin-text-heading, #0f172a);
            font-size: 12.5px;
        }
        .fw-help {
            display: block;
            font-size: 11px;
            color: var(--admin-text-muted, #64748b);
            margin-top: 4px;
            line-height: 1.4;
        }
        .fw-input, .fw-select, .fw-textarea {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid var(--admin-input-border, #cbd5e1);
            border-radius: 6px;
            background: var(--admin-input-bg, #ffffff);
            color: var(--admin-input-text, #1e293b);
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .fw-input:focus, .fw-select:focus, .fw-textarea:focus {
            outline: none;
            border-color: var(--admin-border-focus, #3b82f6);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        .fw-textarea {
            resize: vertical;
            min-height: 70px;
        }
        .fw-textarea--code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            min-height: 140px;
            tab-size: 2;
        }
        .fw-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            user-select: none;
            color: var(--admin-text, #1e293b);
        }
        .fw-checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--admin-primary, #3b82f6);
            cursor: pointer;
        }

        /* Media Upload / Picker Field */
        .fw-media-picker-group {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 4px;
        }
        .fw-media-preview-box {
            width: 52px;
            height: 52px;
            border: 1px dashed var(--admin-border, #cbd5e1);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--admin-surface-subtle, #f8fafc);
            overflow: hidden;
            flex-shrink: 0;
        }
        .fw-media-preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .fw-media-preview-box.is-empty span {
            font-size: 10px;
            color: var(--admin-text-muted, #94a3b8);
        }
        .fw-media-inputs {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .fw-media-btns {
            display: flex;
            gap: 6px;
        }
        .fw-btn-sm {
            padding: 4px 8px;
            font-size: 11.5px;
            border-radius: 4px;
            border: 1px solid var(--admin-border, #cbd5e1);
            background: var(--admin-surface, #ffffff);
            color: var(--admin-text, #1e293b);
            cursor: pointer;
            font-weight: 500;
        }
        .fw-btn-sm:hover {
            background: var(--admin-surface-subtle, #f1f5f9);
        }

        /* Color Picker Field */
        .fw-color-picker-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fw-color-swatch {
            width: 36px;
            height: 36px;
            border: 1px solid var(--admin-border, #cbd5e1);
            border-radius: 6px;
            padding: 2px;
            cursor: pointer;
            background: transparent;
        }

        /* Section Reorder List */
        .fw-section-sort-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .fw-section-sort-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            background: var(--admin-surface-subtle, #f8fafc);
            border: 1px solid var(--admin-border, #e2e8f0);
            border-radius: 6px;
            cursor: grab;
            user-select: none;
            transition: all 0.15s ease;
        }
        .fw-section-sort-item.is-dragging {
            opacity: 0.5;
            border-color: var(--admin-primary, #3b82f6);
            background: rgba(59, 130, 246, 0.05);
        }
        .fw-section-sort-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .fw-drag-handle {
            color: var(--admin-text-muted, #94a3b8);
            font-size: 15px;
            cursor: grab;
            line-height: 1;
        }
        .fw-section-sort-title {
            font-weight: 600;
            font-size: 12.5px;
            color: var(--admin-text-heading, #0f172a);
        }
        .fw-section-sort-actions {
            display: flex;
            gap: 3px;
        }
        .fw-sort-arrow-btn {
            background: var(--admin-surface, #ffffff);
            border: 1px solid var(--admin-border, #cbd5e1);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 11px;
            cursor: pointer;
            color: var(--admin-text, #1e293b);
        }
        .fw-sort-arrow-btn:hover:not(:disabled) {
            background: var(--admin-surface-subtle, #f1f5f9);
        }
        .fw-sort-arrow-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        /* Services Cards Manager */
        .fw-services-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .fw-service-card {
            border: 1px solid var(--admin-border, #e2e8f0);
            border-radius: 6px;
            padding: 10px;
            background: var(--admin-surface-subtle, #f8fafc);
        }
        .fw-service-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .fw-service-card-title {
            font-weight: 600;
            font-size: 12.5px;
        }

        /* Right Preview Area */
        .fw-customizer-preview-pane {
            flex: 1;
            background: #475569;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: auto;
            position: relative;
            padding: 16px;
            transition: background 0.2s;
        }
        .fw-iframe-wrapper {
            position: relative;
            background: #ffffff;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), height 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-radius 0.25s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .fw-iframe-wrapper.mode-desktop {
            width: 100%;
            height: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        .fw-iframe-wrapper.mode-tablet {
            width: 768px;
            height: 1024px;
            max-height: 96%;
            border-radius: 12px;
        }
        .fw-iframe-wrapper.mode-mobile {
            width: 375px;
            height: 667px;
            max-height: 96%;
            border-radius: 24px;
            border: 8px solid #1e293b;
        }
        .fw-preview-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: #ffffff;
            display: block;
        }
        .fw-iframe-loader {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 500;
            color: #1e293b;
            z-index: 5;
        }
        .fw-iframe-loader.is-loading {
            display: flex;
        }

        /* Media Modal */
        .fw-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.15s ease, visibility 0.15s ease;
        }
        .fw-modal-backdrop.is-open {
            display: flex !important;
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }
        .fw-modal-backdrop[hidden] {
            display: none !important;
            pointer-events: none !important;
            visibility: hidden !important;
        }
        .fw-modal {
            background: var(--admin-surface, #ffffff);
            border-radius: 10px;
            width: 780px;
            max-width: 95vw;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 1px solid var(--admin-border, #cbd5e1);
        }
        .fw-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--admin-border, #e2e8f0);
        }
        .fw-modal-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }
        .fw-modal-close {
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--admin-text-muted, #94a3b8);
        }
        .fw-modal-tabs {
            display: flex;
            padding: 0 18px;
            border-bottom: 1px solid var(--admin-border, #e2e8f0);
            background: var(--admin-surface-subtle, #f8fafc);
        }
        .fw-modal-tab-btn {
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-text-muted, #64748b);
            cursor: pointer;
        }
        .fw-modal-tab-btn.active {
            color: var(--admin-primary, #3b82f6);
            border-bottom-color: var(--admin-primary, #3b82f6);
        }
        .fw-modal-body {
            padding: 18px;
            overflow-y: auto;
            flex: 1;
        }
        .fw-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 12px;
        }
        .fw-media-grid-item {
            aspect-ratio: 1;
            border: 2px solid var(--admin-border, #e2e8f0);
            border-radius: 6px;
            padding: 4px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--admin-surface-subtle, #f8fafc);
            overflow: hidden;
            position: relative;
            transition: all 0.15s ease;
        }
        .fw-media-grid-item:hover {
            border-color: var(--admin-primary, #3b82f6);
            transform: scale(1.02);
        }
        .fw-media-grid-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .fw-media-grid-item .fw-item-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.65);
            color: #fff;
            font-size: 10px;
            padding: 2px 4px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            text-align: center;
        }
        .fw-upload-dropzone {
            border: 2px dashed var(--admin-border, #cbd5e1);
            border-radius: 8px;
            padding: 36px 20px;
            text-align: center;
            background: var(--admin-surface-subtle, #f8fafc);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fw-upload-dropzone:hover, .fw-upload-dropzone.drag-over {
            border-color: var(--admin-primary, #3b82f6);
            background: rgba(59, 130, 246, 0.04);
        }
    </style>

    <!-- Top Navigation Bar -->
    <header class="fw-customizer-topbar">
        <div class="fw-topbar-left">
            <a href="/admin/appearance" class="fw-back-link" title="Return to Appearance Themes">&larr; Appearance</a>
            <span class="fw-theme-pill"><?php echo htmlspecialchars($themeName, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="fw-save-status">
                <span class="fw-status-dot" id="fw-status-dot"></span>
                <span id="fw-status-text">All changes saved</span>
            </div>
        </div>

        <div class="fw-device-switcher">
            <button type="button" class="fw-device-btn active" data-device="desktop" title="Desktop Preview">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Desktop
            </button>
            <button type="button" class="fw-device-btn" data-device="tablet" title="Tablet Preview (768px)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Tablet
            </button>
            <button type="button" class="fw-device-btn" data-device="mobile" title="Mobile Preview (375px)">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Mobile
            </button>
            <button type="button" class="fw-preview-action-btn" id="fw-reload-preview-btn" title="Reload Preview Frame">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            </button>
            <a href="/?fw_preview=1" target="_blank" class="fw-preview-action-btn" title="Open preview in new tab">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
        </div>

        <div class="fw-topbar-right">
            <form id="fw-reset-form" method="POST" action="/admin/customize/reset" style="display:inline;">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" id="fw-reset-btn" class="fw-btn fw-btn--danger-outline">&#8635; Reset Defaults</button>
            </form>
            <button type="button" id="fw-save-btn" class="fw-btn fw-btn--primary">Save Changes</button>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="fw-customizer-workspace">
        <!-- Controls Sidebar -->
        <aside class="fw-customizer-sidebar" role="region" aria-label="Theme Customization Panels">
            <div class="fw-sidebar-content">
                <form id="fw-customizer-form" method="POST" action="/admin/customize/save">
                    <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="mods[services_json]" id="fw-mod-services-json" value="<?php echo htmlspecialchars($servicesJson, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="fw-accordion" id="fw-accordion">
                        <!-- Panel 1: Site Identity -->
                        <div class="fw-panel is-open">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🏷️</span> Site Identity</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-site-title">Site Title</label>
                                    <input type="text" name="mods[site_title]" id="fw-site-title" class="fw-input" value="<?php echo htmlspecialchars($val('site_title'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(\FavoriteCMS\Models\Setting::get('general', 'site_name', 'Favorite CMS'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="fw-help">Display title across the header and meta tags.</span>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-site-tagline">Site Tagline</label>
                                    <input type="text" name="mods[site_tagline]" id="fw-site-tagline" class="fw-input" value="<?php echo htmlspecialchars($val('site_tagline'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(\FavoriteCMS\Models\Setting::get('general', 'site_description', ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Site Logo Image</label>
                                    <div class="fw-media-picker-group">
                                        <div class="fw-media-preview-box" id="preview-box-site-logo">
                                            <?php $logoUrl = $val('site_logo_url'); ?>
                                            <?php if ($logoUrl !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo preview">
                                            <?php else: ?>
                                                <span>No logo</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-media-inputs">
                                            <input type="text" name="mods[site_logo_url]" id="fw-site-logo-url" class="fw-input" value="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="/uploads/logo.png">
                                            <div class="fw-media-btns">
                                                <button type="button" class="fw-btn-sm fw-open-media-modal" data-target="fw-site-logo-url" data-preview="preview-box-site-logo">Choose / Upload</button>
                                                <button type="button" class="fw-btn-sm fw-clear-media" data-target="fw-site-logo-url" data-preview="preview-box-site-logo">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="fw-help">Leave empty to display the official brand icon &amp; site title.</span>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-logo-width">Logo Max Width (px)</label>
                                    <input type="number" name="mods[logo_width]" id="fw-logo-width" class="fw-input" value="<?php echo htmlspecialchars($val('logo_width', '38'), ENT_QUOTES, 'UTF-8'); ?>" min="20" max="400" step="2">
                                    <span class="fw-help">Sets the maximum rendered width in pixels (default 38px).</span>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Browser Favicon</label>
                                    <div class="fw-media-picker-group">
                                        <div class="fw-media-preview-box" id="preview-box-site-favicon">
                                            <?php $favUrl = $val('site_favicon_url'); ?>
                                            <?php if ($favUrl !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($favUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Favicon preview">
                                            <?php else: ?>
                                                <span>No icon</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-media-inputs">
                                            <input type="text" name="mods[site_favicon_url]" id="fw-site-favicon-url" class="fw-input" value="<?php echo htmlspecialchars($favUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="/uploads/favicon.png">
                                            <div class="fw-media-btns">
                                                <button type="button" class="fw-btn-sm fw-open-media-modal" data-target="fw-site-favicon-url" data-preview="preview-box-site-favicon">Choose / Upload</button>
                                                <button type="button" class="fw-btn-sm fw-clear-media" data-target="fw-site-favicon-url" data-preview="preview-box-site-favicon">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="fw-help">Favicon displayed in the browser tab (PNG, ICO, or SVG).</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 2: Header Navigation -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🧭</span> Header Navigation</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <input type="hidden" name="mods[header_sticky]" value="0">
                                    <label class="fw-checkbox-label">
                                        <input type="checkbox" name="mods[header_sticky]" value="1" <?php echo $boolVal('header_sticky', false) ? 'checked' : ''; ?>>
                                        Enable Sticky Header
                                    </label>
                                    <span class="fw-help">Keeps the header bar fixed at the top of the viewport when scrolling.</span>
                                </div>
                                <div class="fw-field">
                                    <input type="hidden" name="mods[header_show_search]" value="0">
                                    <label class="fw-checkbox-label">
                                        <input type="checkbox" name="mods[header_show_search]" value="1" <?php echo $boolVal('header_show_search', true) ? 'checked' : ''; ?>>
                                        Show Search Box in Header
                                    </label>
                                </div>
                                <div class="fw-field">
                                    <input type="hidden" name="mods[header_show_store]" value="0">
                                    <label class="fw-checkbox-label">
                                        <input type="checkbox" name="mods[header_show_store]" value="1" <?php echo $boolVal('header_show_store', true) ? 'checked' : ''; ?>>
                                        Show Store Action Button
                                    </label>
                                </div>
                                <div class="fw-field">
                                    <input type="hidden" name="mods[header_show_theme_toggle]" value="0">
                                    <label class="fw-checkbox-label">
                                        <input type="checkbox" name="mods[header_show_theme_toggle]" value="1" <?php echo $boolVal('header_show_theme_toggle', true) ? 'checked' : ''; ?>>
                                        Show Dark / Light Mode Switcher
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 3: Homepage Sections & Reorder -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">📑</span> Homepage Sections &amp; Order</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <p class="fw-help" style="margin-bottom: 12px;">Drag items or click arrows to reorder homepage sections. Uncheck to hide from the homepage.</p>
                                <div class="fw-section-sort-list" id="fw-section-sort-list">
                                    <?php foreach ($layoutSections as $sIdx => $sec): ?>
                                        <?php
                                        $secId = (string)($sec['id'] ?? '');
                                        $secName = (string)($sec['name'] ?? $secId);
                                        $secEnabled = !empty($sec['enabled']);
                                        ?>
                                        <div class="fw-section-sort-item" draggable="true" data-section-id="<?php echo htmlspecialchars($secId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="section_order[]" value="<?php echo htmlspecialchars($secId, ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="fw-section-sort-left">
                                                <span class="fw-drag-handle" title="Drag to reorder">&#x2807;&#x2807;</span>
                                                <label class="fw-checkbox-label" style="font-size: 12.5px;">
                                                    <input type="checkbox" name="sections[<?php echo htmlspecialchars($secId, ENT_QUOTES, 'UTF-8'); ?>][enabled]" value="1" <?php echo $secEnabled ? 'checked' : ''; ?>>
                                                    <span class="fw-section-sort-title"><?php echo htmlspecialchars($secName, ENT_QUOTES, 'UTF-8'); ?></span>
                                                </label>
                                            </div>
                                            <div class="fw-section-sort-actions">
                                                <button type="button" class="fw-sort-arrow-btn fw-sort-up" title="Move section up">&uarr;</button>
                                                <button type="button" class="fw-sort-arrow-btn fw-sort-down" title="Move section down">&darr;</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 4: Hero Section -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🚀</span> Hero Section</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-hero-eyebrow">Eyebrow Badge</label>
                                    <input type="text" name="mods[hero_eyebrow]" id="fw-hero-eyebrow" class="fw-input" value="<?php echo htmlspecialchars($val('hero_eyebrow'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-hero-title">Main Headline</label>
                                    <textarea name="mods[hero_title]" id="fw-hero-title" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('hero_title'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-hero-lead">Description / Lead</label>
                                    <textarea name="mods[hero_lead]" id="fw-hero-lead" class="fw-textarea" rows="3"><?php echo htmlspecialchars($val('hero_lead'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Primary Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[hero_primary_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('hero_primary_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[hero_primary_url]" class="fw-input" placeholder="URL (/store)" value="<?php echo htmlspecialchars($val('hero_primary_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Secondary Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[hero_secondary_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('hero_secondary_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[hero_secondary_url]" class="fw-input" placeholder="URL (#services)" value="<?php echo htmlspecialchars($val('hero_secondary_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-hero-media-type">Hero Media Type</label>
                                    <?php $hMediaType = $val('hero_media_type', 'image'); ?>
                                    <select name="mods[hero_media_type]" id="fw-hero-media-type" class="fw-select">
                                        <option value="image" <?php echo $hMediaType === 'image' ? 'selected' : ''; ?>>Image (Custom or Default SVG)</option>
                                        <option value="video" <?php echo $hMediaType === 'video' ? 'selected' : ''; ?>>Video (MP4, YouTube, or Vimeo)</option>
                                        <option value="none" <?php echo $hMediaType === 'none' ? 'selected' : ''; ?>>None (Text &amp; Buttons Only)</option>
                                    </select>
                                </div>
                                <div class="fw-field" id="fw-hero-image-fields">
                                    <label class="fw-label">Hero Image</label>
                                    <div class="fw-media-picker-group">
                                        <div class="fw-media-preview-box" id="preview-box-hero-image">
                                            <?php $hImgUrl = $val('hero_image_url'); ?>
                                            <?php if ($hImgUrl !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($hImgUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Hero image">
                                            <?php else: ?>
                                                <span>Default SVG</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-media-inputs">
                                            <input type="text" name="mods[hero_image_url]" id="fw-hero-image-url" class="fw-input" value="<?php echo htmlspecialchars($hImgUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="/uploads/hero.webp">
                                            <div class="fw-media-btns">
                                                <button type="button" class="fw-btn-sm fw-open-media-modal" data-target="fw-hero-image-url" data-preview="preview-box-hero-image">Choose Image</button>
                                                <button type="button" class="fw-btn-sm fw-clear-media" data-target="fw-hero-image-url" data-preview="preview-box-hero-image">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-top: 6px; display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                                        <input type="text" name="mods[hero_image_alt]" class="fw-input" placeholder="Alt text" value="<?php echo htmlspecialchars($val('hero_image_alt'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[hero_image_link]" class="fw-input" placeholder="Optional click link" value="<?php echo htmlspecialchars($val('hero_image_link'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field" id="fw-hero-video-fields">
                                    <label class="fw-label" for="fw-hero-video-url">Hero Video URL</label>
                                    <input type="text" name="mods[hero_video_url]" id="fw-hero-video-url" class="fw-input" value="<?php echo htmlspecialchars($val('hero_video_url'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://www.youtube.com/watch?v=... or MP4 file">
                                    <span class="fw-help">Supports MP4 direct link, YouTube video URL, or Vimeo video URL.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 5: Trust & Stats -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">📊</span> Trust &amp; Stats Section</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <p class="fw-help" style="margin-bottom: 12px;">Configure up to 4 trust and performance metric counters.</p>
                                <div class="fw-field">
                                    <label class="fw-label">Metric 1</label>
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 6px; margin-bottom: 4px;">
                                        <input type="text" name="mods[stats_1_value]" class="fw-input" placeholder="10K+" value="<?php echo htmlspecialchars($stat1Val, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[stats_1_label]" class="fw-input" placeholder="Active Users" value="<?php echo htmlspecialchars($stat1Lbl, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <input type="text" name="mods[stats_1_indicator]" class="fw-input" placeholder="Worldwide community" value="<?php echo htmlspecialchars($stat1Ind, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Metric 2</label>
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 6px; margin-bottom: 4px;">
                                        <input type="text" name="mods[stats_2_value]" class="fw-input" placeholder="99.9%" value="<?php echo htmlspecialchars($stat2Val, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[stats_2_label]" class="fw-input" placeholder="Uptime" value="<?php echo htmlspecialchars($stat2Lbl, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <input type="text" name="mods[stats_2_indicator]" class="fw-input" placeholder="Enterprise SLA" value="<?php echo htmlspecialchars($stat2Ind, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Metric 3</label>
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 6px; margin-bottom: 4px;">
                                        <input type="text" name="mods[stats_3_value]" class="fw-input" placeholder="250+" value="<?php echo htmlspecialchars($stat3Val, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[stats_3_label]" class="fw-input" placeholder="Digital Assets" value="<?php echo htmlspecialchars($stat3Lbl, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <input type="text" name="mods[stats_3_indicator]" class="fw-input" placeholder="Curated quality" value="<?php echo htmlspecialchars($stat3Ind, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Metric 4</label>
                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 6px; margin-bottom: 4px;">
                                        <input type="text" name="mods[stats_4_value]" class="fw-input" placeholder="24/7" value="<?php echo htmlspecialchars($stat4Val, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[stats_4_label]" class="fw-input" placeholder="Dedicated Support" value="<?php echo htmlspecialchars($stat4Lbl, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <input type="text" name="mods[stats_4_indicator]" class="fw-input" placeholder="Expert assistance" value="<?php echo htmlspecialchars($stat4Ind, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Panel 6: About Section -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🏢</span> About Section</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-about-badge">Badge</label>
                                    <input type="text" name="mods[about_badge]" id="fw-about-badge" class="fw-input" value="<?php echo htmlspecialchars($val('about_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-about-title">Section Title</label>
                                    <input type="text" name="mods[about_title]" id="fw-about-title" class="fw-input" value="<?php echo htmlspecialchars($val('about_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-about-description">Narrative Paragraph</label>
                                    <textarea name="mods[about_description]" id="fw-about-description" class="fw-textarea" rows="3"><?php echo htmlspecialchars($val('about_description'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">CTA Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[about_cta_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('about_cta_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[about_cta_url]" class="fw-input" placeholder="URL (/page/about-us)" value="<?php echo htmlspecialchars($val('about_cta_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">About Media Image</label>
                                    <div class="fw-media-picker-group">
                                        <div class="fw-media-preview-box" id="preview-box-about-image">
                                            <?php $aboutImgUrl = $val('about_image_url'); ?>
                                            <?php if ($aboutImgUrl !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($aboutImgUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="About preview">
                                            <?php else: ?>
                                                <span>Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-media-inputs">
                                            <input type="text" name="mods[about_image_url]" id="fw-about-image-url" class="fw-input" value="<?php echo htmlspecialchars($aboutImgUrl, ENT_QUOTES, 'UTF-8'); ?>" placeholder="/uploads/about.webp">
                                            <div class="fw-media-btns">
                                                <button type="button" class="fw-btn-sm fw-open-media-modal" data-target="fw-about-image-url" data-preview="preview-box-about-image">Choose</button>
                                                <button type="button" class="fw-btn-sm fw-clear-media" data-target="fw-about-image-url" data-preview="preview-box-about-image">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 7: Services Section -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🛠️</span> Professional Services</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-services-badge">Badge</label>
                                    <input type="text" name="mods[services_badge]" id="fw-services-badge" class="fw-input" value="<?php echo htmlspecialchars($val('services_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-services-title">Title</label>
                                    <input type="text" name="mods[services_title]" id="fw-services-title" class="fw-input" value="<?php echo htmlspecialchars($val('services_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-services-desc">Description</label>
                                    <textarea name="mods[services_desc]" id="fw-services-desc" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('services_desc'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <label class="fw-label" style="margin: 0;">Service Cards</label>
                                        <button type="button" id="fw-add-service-btn" class="fw-btn-sm" style="color: var(--admin-primary, #3b82f6);">+ Add Service</button>
                                    </div>
                                    <div class="fw-services-container" id="fw-services-container">
                                        <!-- Rendered dynamically by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 8: Digital Products -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">📦</span> Digital Products</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-products-badge">Eyebrow Badge</label>
                                    <input type="text" name="mods[products_badge]" id="fw-products-badge" class="fw-input" value="<?php echo htmlspecialchars($val('products_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-products-title">Section Title</label>
                                    <input type="text" name="mods[products_title]" id="fw-products-title" class="fw-input" value="<?php echo htmlspecialchars($val('products_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Action Link</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[products_action_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('products_action_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[products_action_url]" class="fw-input" placeholder="URL" value="<?php echo htmlspecialchars($val('products_action_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="fw-field">
                                        <label class="fw-label" for="fw-products-limit">Item Limit</label>
                                        <input type="number" name="mods[products_limit]" id="fw-products-limit" class="fw-input" min="1" max="12" value="<?php echo htmlspecialchars($val('products_limit', '6'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="fw-field">
                                        <label class="fw-label" for="fw-products-columns">Columns</label>
                                        <?php $pCols = (int)$val('products_columns', '3'); ?>
                                        <select name="mods[products_columns]" id="fw-products-columns" class="fw-select">
                                            <option value="2" <?php echo $pCols === 2 ? 'selected' : ''; ?>>2 Columns</option>
                                            <option value="3" <?php echo $pCols === 3 ? 'selected' : ''; ?>>3 Columns</option>
                                            <option value="4" <?php echo $pCols === 4 ? 'selected' : ''; ?>>4 Columns</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 9: Packages & Solutions -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">💼</span> Packages &amp; Solutions</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-packages-badge">Eyebrow</label>
                                    <input type="text" name="mods[packages_badge]" id="fw-packages-badge" class="fw-input" value="<?php echo htmlspecialchars($val('packages_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-packages-title">Title</label>
                                    <input type="text" name="mods[packages_title]" id="fw-packages-title" class="fw-input" value="<?php echo htmlspecialchars($val('packages_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Action Link</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[packages_action_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('packages_action_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[packages_action_url]" class="fw-input" placeholder="URL" value="<?php echo htmlspecialchars($val('packages_action_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-packages-limit">Limit</label>
                                    <input type="number" name="mods[packages_limit]" id="fw-packages-limit" class="fw-input" min="1" max="6" value="<?php echo htmlspecialchars($val('packages_limit', '3'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Panel 10: Membership Section -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">👑</span> Memberships</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-membership-badge">Badge</label>
                                    <input type="text" name="mods[membership_badge]" id="fw-membership-badge" class="fw-input" value="<?php echo htmlspecialchars($val('membership_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-membership-title">Title</label>
                                    <input type="text" name="mods[membership_title]" id="fw-membership-title" class="fw-input" value="<?php echo htmlspecialchars($val('membership_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-membership-desc">Description</label>
                                    <textarea name="mods[membership_description]" id="fw-membership-desc" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('membership_description'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">CTA Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[membership_cta_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('membership_cta_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[membership_cta_url]" class="fw-input" placeholder="URL" value="<?php echo htmlspecialchars($val('membership_cta_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Side Illustration Image</label>
                                    <div class="fw-media-picker-group">
                                        <div class="fw-media-preview-box" id="preview-box-membership-image">
                                            <?php $memImg = $val('membership_image_url'); ?>
                                            <?php if ($memImg !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($memImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Membership illustration">
                                            <?php else: ?>
                                                <span>Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="fw-media-inputs">
                                            <input type="text" name="mods[membership_image_url]" id="fw-membership-image-url" class="fw-input" value="<?php echo htmlspecialchars($memImg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="/uploads/illustration.svg">
                                            <div class="fw-media-btns">
                                                <button type="button" class="fw-btn-sm fw-open-media-modal" data-target="fw-membership-image-url" data-preview="preview-box-membership-image">Choose</button>
                                                <button type="button" class="fw-btn-sm fw-clear-media" data-target="fw-membership-image-url" data-preview="preview-box-membership-image">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 11: Latest Articles -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">📰</span> Latest Articles</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-latest-title">Section Heading</label>
                                    <input type="text" name="mods[latest_title]" id="fw-latest-title" class="fw-input" value="<?php echo htmlspecialchars($val('latest_title'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <input type="hidden" name="mods[latest_show_heading]" value="0">
                                    <label class="fw-checkbox-label">
                                        <input type="checkbox" name="mods[latest_show_heading]" value="1" <?php echo $boolVal('latest_show_heading', true) ? 'checked' : ''; ?>>
                                        Show Section Heading
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 12: Call To Action (CTA) -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">📣</span> Call to Action</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-cta-badge">Badge</label>
                                    <input type="text" name="mods[cta_badge]" id="fw-cta-badge" class="fw-input" value="<?php echo htmlspecialchars($val('cta_badge'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-cta-title">Headline</label>
                                    <textarea name="mods[cta_title]" id="fw-cta-title" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('cta_title'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-cta-desc">Description</label>
                                    <textarea name="mods[cta_description]" id="fw-cta-desc" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('cta_description'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Primary Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[cta_primary_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('cta_primary_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[cta_primary_url]" class="fw-input" placeholder="URL" value="<?php echo htmlspecialchars($val('cta_primary_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Secondary Button</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="mods[cta_secondary_text]" class="fw-input" placeholder="Text" value="<?php echo htmlspecialchars($val('cta_secondary_text'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="mods[cta_secondary_url]" class="fw-input" placeholder="URL" value="<?php echo htmlspecialchars($val('cta_secondary_url'), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 13: Footer -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">⚓</span> Footer</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-footer-brand">Footer Brand Name</label>
                                    <input type="text" name="mods[footer_brand_name]" id="fw-footer-brand" class="fw-input" value="<?php echo htmlspecialchars($val('footer_brand_name'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(\FavoriteCMS\Models\Setting::get('general', 'site_name', 'Favorite CMS'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-footer-summary">Footer Bio / Summary</label>
                                    <textarea name="mods[footer_summary]" id="fw-footer-summary" class="fw-textarea" rows="2"><?php echo htmlspecialchars($val('footer_summary'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-footer-copyright">Custom Copyright Text</label>
                                    <input type="text" name="mods[footer_copyright]" id="fw-footer-copyright" class="fw-input" value="<?php echo htmlspecialchars($val('footer_copyright'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="All rights reserved.">
                                </div>
                            </div>
                        </div>

                        <!-- Panel 14: Colors — Light Theme -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">☀️</span> Colors — Light Theme</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label">Primary Brand Accent</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('accent_color', '#2563eb'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-accent').value = this.value; fwTriggerLiveToken('--accent', this.value);">
                                        <input type="text" name="mods[accent_color]" id="fw-col-accent" class="fw-input" value="<?php echo htmlspecialchars($val('accent_color', '#2563eb'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value; fwTriggerLiveToken('--accent', this.value);">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Page Background</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('color_bg', '#f8fafc'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-bg').value = this.value; fwTriggerLiveToken('--bg', this.value);">
                                        <input type="text" name="mods[color_bg]" id="fw-col-bg" class="fw-input" value="<?php echo htmlspecialchars($val('color_bg', '#f8fafc'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value; fwTriggerLiveToken('--bg', this.value);">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Card / Surface Background</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('color_surface', '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-surface').value = this.value; fwTriggerLiveToken('--surface', this.value);">
                                        <input type="text" name="mods[color_surface]" id="fw-col-surface" class="fw-input" value="<?php echo htmlspecialchars($val('color_surface', '#ffffff'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value; fwTriggerLiveToken('--surface', this.value);">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Body Text Color</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('color_text', '#253044'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-text').value = this.value; fwTriggerLiveToken('--text', this.value);">
                                        <input type="text" name="mods[color_text]" id="fw-col-text" class="fw-input" value="<?php echo htmlspecialchars($val('color_text', '#253044'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value; fwTriggerLiveToken('--text', this.value);">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Border Color</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('color_border', '#e3e7ef'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-border').value = this.value; fwTriggerLiveToken('--border', this.value);">
                                        <input type="text" name="mods[color_border]" id="fw-col-border" class="fw-input" value="<?php echo htmlspecialchars($val('color_border', '#e3e7ef'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value; fwTriggerLiveToken('--border', this.value);">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 15: Colors — Dark Theme -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🌙</span> Colors — Dark Theme</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label">Dark Brand Accent</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('dark_accent_color', '#3b82f6'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-dark-accent').value = this.value;">
                                        <input type="text" name="mods[dark_accent_color]" id="fw-col-dark-accent" class="fw-input" value="<?php echo htmlspecialchars($val('dark_accent_color', '#3b82f6'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value;">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Dark Page Background</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('dark_color_bg', '#0b1120'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-dark-bg').value = this.value;">
                                        <input type="text" name="mods[dark_color_bg]" id="fw-col-dark-bg" class="fw-input" value="<?php echo htmlspecialchars($val('dark_color_bg', '#0b1120'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value;">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Dark Surface / Card Background</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('dark_color_surface', '#111827'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-dark-surface').value = this.value;">
                                        <input type="text" name="mods[dark_color_surface]" id="fw-col-dark-surface" class="fw-input" value="<?php echo htmlspecialchars($val('dark_color_surface', '#111827'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value;">
                                    </div>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label">Dark Text Color</label>
                                    <div class="fw-color-picker-row">
                                        <input type="color" class="fw-color-swatch" value="<?php echo htmlspecialchars($val('dark_color_text', '#cbd5e1'), ENT_QUOTES, 'UTF-8'); ?>" oninput="document.getElementById('fw-col-dark-text').value = this.value;">
                                        <input type="text" name="mods[dark_color_text]" id="fw-col-dark-text" class="fw-input" value="<?php echo htmlspecialchars($val('dark_color_text', '#cbd5e1'), ENT_QUOTES, 'UTF-8'); ?>" oninput="this.previousElementSibling.value = this.value;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 16: Typography & Layout -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">🔤</span> Typography &amp; Layout</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-site-layout">Sidebar Alignment</label>
                                    <?php $sLayout = $val('site_layout', 'right'); ?>
                                    <select name="mods[site_layout]" id="fw-site-layout" class="fw-select">
                                        <option value="right" <?php echo $sLayout === 'right' ? 'selected' : ''; ?>>Standard (Content on Left, Sidebar on Right)</option>
                                        <option value="left" <?php echo $sLayout === 'left' ? 'selected' : ''; ?>>Inverted (Sidebar on Left, Content on Right)</option>
                                        <option value="none" <?php echo $sLayout === 'none' ? 'selected' : ''; ?>>No Sidebar (Full Width Single Column)</option>
                                    </select>
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-font-size">Base Font Size (px)</label>
                                    <input type="number" name="mods[font_base_size]" id="fw-font-size" class="fw-input" min="13" max="22" value="<?php echo htmlspecialchars($val('font_base_size', '16'), ENT_QUOTES, 'UTF-8'); ?>" oninput="fwTriggerLiveToken('font-size', this.value + 'px');">
                                </div>
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-container-width">Container Max Width (px)</label>
                                    <input type="number" name="mods[container_width]" id="fw-container-width" class="fw-input" min="960" max="1920" step="10" value="<?php echo htmlspecialchars($val('container_width', '1280'), ENT_QUOTES, 'UTF-8'); ?>" oninput="fwTriggerLiveToken('--container-w', this.value + 'px');">
                                </div>
                            </div>
                        </div>

                        <!-- Panel 17: Custom CSS -->
                        <div class="fw-panel">
                            <button type="button" class="fw-panel-header">
                                <span class="fw-panel-title"><span class="fw-panel-icon">💻</span> Custom CSS</span>
                                <span class="fw-panel-arrow">&rsaquo;</span>
                            </button>
                            <div class="fw-panel-body">
                                <div class="fw-field">
                                    <label class="fw-label" for="fw-custom-css">Custom CSS Styles</label>
                                    <textarea name="mods[custom_css]" id="fw-custom-css" class="fw-textarea fw-textarea--code" placeholder="/* Add custom CSS rules here */&#10;.fw-hero { padding-block: 5rem; }"><?php echo htmlspecialchars($val('custom_css'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <span class="fw-help">Safe CSS rules only. JavaScript, @import, expressions, and behavioral injections are sanitized.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Right Preview Pane -->
        <main class="fw-customizer-preview-pane" id="fw-preview-pane">
            <div class="fw-iframe-wrapper mode-desktop" id="fw-iframe-wrapper">
                <div class="fw-iframe-loader" id="fw-iframe-loader">
                    <span>Updating preview&hellip;</span>
                </div>
                <iframe src="/?fw_preview=1" class="fw-preview-iframe" id="fw-preview-iframe" title="Favorite Web Live Preview"></iframe>
            </div>
        </main>
    </div>

    <!-- Universal Media Picker Modal -->
    <div class="fw-modal-backdrop" id="fw-media-modal" role="dialog" aria-modal="true" aria-labelledby="fw-modal-title" hidden style="display: none;">
        <div class="fw-modal">
            <div class="fw-modal-header">
                <h3 id="fw-modal-title">Select or Upload Media</h3>
                <button type="button" class="fw-modal-close" id="fw-modal-close-btn" aria-label="Close modal">&times;</button>
            </div>
            <div class="fw-modal-tabs">
                <button type="button" class="fw-modal-tab-btn active" data-tab="library">Media Library</button>
                <button type="button" class="fw-modal-tab-btn" data-tab="upload">Upload File</button>
                <button type="button" class="fw-modal-tab-btn" data-tab="url">Enter URL</button>
            </div>
            <div class="fw-modal-body">
                <!-- Tab 1: Library -->
                <div class="fw-tab-pane" id="fw-tab-library">
                    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                        <input type="text" id="fw-library-search" class="fw-input" placeholder="Search media library...">
                    </div>
                    <div class="fw-media-grid" id="fw-media-grid">
                        <!-- Loaded dynamically -->
                    </div>
                    <div id="fw-library-empty" style="display:none; text-align:center; padding: 30px; color: var(--admin-text-muted);">
                        No media found.
                    </div>
                </div>

                <!-- Tab 2: Upload -->
                <div class="fw-tab-pane" id="fw-tab-upload" style="display:none;">
                    <div class="fw-upload-dropzone" id="fw-upload-dropzone">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--admin-text-muted); margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p style="font-weight: 600; margin-bottom: 4px;">Drag &amp; Drop files here to upload</p>
                        <p style="font-size: 12px; color: var(--admin-text-muted); margin-bottom: 12px;">Supported: PNG, JPG, WEBP, SVG, GIF, MP4</p>
                        <label class="fw-btn-sm" style="display: inline-block; cursor: pointer;">
                            Select File From Device
                            <input type="file" id="fw-modal-file-input" style="display:none;" accept="image/*,video/*">
                        </label>
                    </div>
                    <div id="fw-upload-progress" style="display:none; margin-top: 14px; text-align: center;">
                        <span style="font-size: 12px; font-weight: 600;">Uploading file&hellip;</span>
                    </div>
                </div>

                <!-- Tab 3: Custom URL -->
                <div class="fw-tab-pane" id="fw-tab-url" style="display:none;">
                    <div class="fw-field">
                        <label class="fw-label" for="fw-custom-url-input">Direct Image or Video URL</label>
                        <input type="url" id="fw-custom-url-input" class="fw-input" placeholder="https://example.com/image.jpg">
                    </div>
                    <button type="button" class="fw-btn fw-btn--primary" id="fw-insert-url-btn" style="margin-top: 8px;">Use This URL</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    // State
    var isDirty = false;
    var currentActiveTargetInputId = null;
    var currentActivePreviewBoxId = null;
    var iframe = document.getElementById('fw-preview-iframe');
    var iframeWrapper = document.getElementById('fw-iframe-wrapper');
    var loader = document.getElementById('fw-iframe-loader');
    var form = document.getElementById('fw-customizer-form');
    var saveBtn = document.getElementById('fw-save-btn');
    var resetBtn = document.getElementById('fw-reset-btn');
    var resetForm = document.getElementById('fw-reset-form');
    var statusDot = document.getElementById('fw-status-dot');
    var statusText = document.getElementById('fw-status-text');

    // Accordion Toggle
    var panels = document.querySelectorAll('.fw-panel');
    panels.forEach(function(panel) {
        var header = panel.querySelector('.fw-panel-header');
        if (header) {
            header.addEventListener('click', function(e) {
                e.preventDefault();
                panel.classList.toggle('is-open');
            });
        }
    });

    // Device Switcher
    var deviceBtns = document.querySelectorAll('.fw-device-btn');
    deviceBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            deviceBtns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var mode = btn.getAttribute('data-device');
            iframeWrapper.className = 'fw-iframe-wrapper mode-' + mode;
        });
    });

    // Reload Preview Frame
    var reloadBtn = document.getElementById('fw-reload-preview-btn');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function() {
            if (loader) loader.classList.add('is-loading');
            iframe.src = iframe.src;
        });
    }
    if (iframe) {
        iframe.addEventListener('load', function() {
            if (loader) loader.classList.remove('is-loading');
        });
    }

    // Mark Dirty
    function markDirty() {
        if (!isDirty) {
            isDirty = true;
            if (statusDot) statusDot.classList.add('dirty');
            if (statusText) statusText.textContent = 'Unsaved changes';
        }
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    // Live Token postMessage Bridge
    window.fwTriggerLiveToken = function(property, value) {
        markDirty();
        try {
            if (iframe && iframe.contentWindow) {
                var tokens = {};
                tokens[property] = value;
                var targetOrigin = window.location.origin;
                iframe.contentWindow.postMessage({
                    type: 'fw_preview_update',
                    tokens: tokens
                }, targetOrigin);
            }
        } catch (e) {}
    };

    // Live Custom CSS Bridge
    var customCssInput = document.getElementById('fw-custom-css');
    if (customCssInput) {
        customCssInput.addEventListener('input', function() {
            try {
                if (iframe && iframe.contentWindow) {
                    var targetOrigin = window.location.origin;
                    iframe.contentWindow.postMessage({
                        type: 'fw_preview_update',
                        customCss: customCssInput.value
                    }, targetOrigin);
                }
            } catch (e) {}
        });
    }

    // Section Sort / Drag-and-Drop & Arrow Buttons
    var sortList = document.getElementById('fw-section-sort-list');
    if (sortList) {
        function updateSortArrows() {
            var items = sortList.querySelectorAll('.fw-section-sort-item');
            items.forEach(function(item, idx) {
                var upBtn = item.querySelector('.fw-sort-up');
                var downBtn = item.querySelector('.fw-sort-down');
                if (upBtn) upBtn.disabled = (idx === 0);
                if (downBtn) downBtn.disabled = (idx === items.length - 1);
            });
        }
        updateSortArrows();

        sortList.addEventListener('click', function(e) {
            var target = e.target.closest('.fw-sort-arrow-btn');
            if (!target) return;
            var item = target.closest('.fw-section-sort-item');
            if (!item) return;

            if (target.classList.contains('fw-sort-up')) {
                var prev = item.previousElementSibling;
                if (prev) {
                    sortList.insertBefore(item, prev);
                    markDirty();
                    updateSortArrows();
                }
            } else if (target.classList.contains('fw-sort-down')) {
                var next = item.nextElementSibling;
                if (next) {
                    sortList.insertBefore(next, item);
                    markDirty();
                    updateSortArrows();
                }
            }
        });

        // HTML5 Drag and Drop Reordering
        var draggedItem = null;
        sortList.addEventListener('dragstart', function(e) {
            draggedItem = e.target.closest('.fw-section-sort-item');
            if (draggedItem) {
                draggedItem.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });
        sortList.addEventListener('dragend', function() {
            if (draggedItem) {
                draggedItem.classList.remove('is-dragging');
                draggedItem = null;
                markDirty();
                updateSortArrows();
            }
        });
        sortList.addEventListener('dragover', function(e) {
            e.preventDefault();
            var target = e.target.closest('.fw-section-sort-item');
            if (target && target !== draggedItem) {
                var rect = target.getBoundingClientRect();
                var next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
                sortList.insertBefore(draggedItem, next && target.nextSibling || target);
            }
        });
    }

    // Dynamic Services Card Manager
    var servicesJsonInput = document.getElementById('fw-mod-services-json');
    var servicesContainer = document.getElementById('fw-services-container');
    var addServiceBtn = document.getElementById('fw-add-service-btn');
    var servicesData = [];

    try {
        servicesData = JSON.parse(servicesJsonInput.value || '[]');
        if (!Array.isArray(servicesData)) servicesData = [];
    } catch (e) {
        servicesData = [];
    }

    function renderServices() {
        if (!servicesContainer) return;
        servicesContainer.innerHTML = '';

        servicesData.forEach(function(svc, idx) {
            var card = document.createElement('div');
            card.className = 'fw-service-card';
            card.innerHTML = [
                '<div class="fw-service-card-top">',
                '  <span class="fw-service-card-title">Service #' + (idx + 1) + '</span>',
                '  <div style="display: flex; gap: 4px;">',
                '    <button type="button" class="fw-btn-sm fw-svc-up" ' + (idx === 0 ? 'disabled' : '') + '>&uarr;</button>',
                '    <button type="button" class="fw-btn-sm fw-svc-down" ' + (idx === servicesData.length - 1 ? 'disabled' : '') + '>&darr;</button>',
                '    <button type="button" class="fw-btn-sm fw-svc-del" style="color: var(--admin-danger, #ef4444);">&times;</button>',
                '  </div>',
                '</div>',
                '<div style="display: flex; flex-direction: column; gap: 6px;">',
                '  <input type="text" class="fw-input fw-svc-title" placeholder="Service Title" value="' + (svc.title ? escapeHtml(svc.title) : '') + '">',
                '  <textarea class="fw-textarea fw-svc-desc" rows="2" placeholder="Description">' + (svc.description ? escapeHtml(svc.description) : '') + '</textarea>',
                '  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">',
                '    <select class="fw-select fw-svc-icon">',
                '      <option value="download" ' + (svc.icon === 'download' ? 'selected' : '') + '>Download / Box</option>',
                '      <option value="growth" ' + (svc.icon === 'growth' ? 'selected' : '') + '>Growth / Chart</option>',
                '      <option value="browser" ' + (svc.icon === 'browser' ? 'selected' : '') + '>Browser / Web</option>',
                '      <option value="design" ' + (svc.icon === 'design' ? 'selected' : '') + '>Design / Palette</option>',
                '      <option value="document" ' + (svc.icon === 'document' ? 'selected' : '') + '>Document / Office</option>',
                '      <option value="support" ' + (svc.icon === 'support' ? 'selected' : '') + '>Support / Help</option>',
                '      <option value="cube" ' + (svc.icon === 'cube' ? 'selected' : '') + '>Package / Cube</option>',
                '      <option value="star" ' + (svc.icon === 'star' ? 'selected' : '') + '>Star / Premium</option>',
                '    </select>',
                '    <input type="text" class="fw-input fw-svc-url" placeholder="URL (/store)" value="' + (svc.url ? escapeHtml(svc.url) : '') + '">',
                '  </div>',
                '</div>'
            ].join('');

            // Bind change listeners
            var titleInput = card.querySelector('.fw-svc-title');
            var descInput = card.querySelector('.fw-svc-desc');
            var iconSelect = card.querySelector('.fw-svc-icon');
            var urlInput = card.querySelector('.fw-svc-url');

            function syncCard() {
                svc.title = titleInput.value;
                svc.description = descInput.value;
                svc.icon = iconSelect.value;
                svc.url = urlInput.value;
                syncServicesJson();
            }
            titleInput.addEventListener('input', syncCard);
            descInput.addEventListener('input', syncCard);
            iconSelect.addEventListener('change', syncCard);
            urlInput.addEventListener('input', syncCard);

            // Action buttons
            card.querySelector('.fw-svc-del').addEventListener('click', function() {
                servicesData.splice(idx, 1);
                syncServicesJson();
                renderServices();
            });
            var upBtn = card.querySelector('.fw-svc-up');
            if (upBtn) {
                upBtn.addEventListener('click', function() {
                    if (idx > 0) {
                        var tmp = servicesData[idx];
                        servicesData[idx] = servicesData[idx - 1];
                        servicesData[idx - 1] = tmp;
                        syncServicesJson();
                        renderServices();
                    }
                });
            }
            var downBtn = card.querySelector('.fw-svc-down');
            if (downBtn) {
                downBtn.addEventListener('click', function() {
                    if (idx < servicesData.length - 1) {
                        var tmp = servicesData[idx];
                        servicesData[idx] = servicesData[idx + 1];
                        servicesData[idx + 1] = tmp;
                        syncServicesJson();
                        renderServices();
                    }
                });
            }

            servicesContainer.appendChild(card);
        });
    }

    function syncServicesJson() {
        if (servicesJsonInput) {
            servicesJsonInput.value = JSON.stringify(servicesData);
            markDirty();
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    if (addServiceBtn) {
        addServiceBtn.addEventListener('click', function() {
            servicesData.push({
                title: 'New Service',
                description: 'Service description details.',
                icon: 'support',
                url: '/store?product_type=service',
                enabled: true
            });
            syncServicesJson();
            renderServices();
        });
    }
    renderServices();

    // Reset Defaults Action
    if (resetBtn && resetForm) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Reset all Favorite Web theme customizations and sections back to default values?')) {
                resetForm.submit();
            }
        });
    }

    // Save Form via Fetch (AJAX) with fallback
    if (saveBtn) {
        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            if (statusText) statusText.textContent = 'Saving changes...';

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(resp) {
                if (resp.ok) {
                    isDirty = false;
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Saved! ✓';
                    if (statusDot) statusDot.classList.remove('dirty');
                    if (statusText) statusText.textContent = 'All changes saved';

                    setTimeout(function() {
                        saveBtn.textContent = 'Save Changes';
                    }, 2000);

                    // Refresh preview frame to show latest server-side render
                    if (loader) loader.classList.add('is-loading');
                    iframe.src = iframe.src;
                } else {
                    throw new Error('Save failed with HTTP ' + resp.status);
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('An error occurred while saving: ' + err.message + '\nSubmitting standard form...');
                form.submit();
            });
        });
    }

    // Universal Media Modal Logic
    var mediaModal = document.getElementById('fw-media-modal');
    var modalCloseBtn = document.getElementById('fw-modal-close-btn');
    var modalTabBtns = document.querySelectorAll('.fw-modal-tab-btn');
    var mediaGrid = document.getElementById('fw-media-grid');
    var librarySearch = document.getElementById('fw-library-search');
    var libraryEmpty = document.getElementById('fw-library-empty');
    var dropzone = document.getElementById('fw-upload-dropzone');
    var fileInput = document.getElementById('fw-modal-file-input');
    var uploadProgress = document.getElementById('fw-upload-progress');
    var customUrlInput = document.getElementById('fw-custom-url-input');
    var insertUrlBtn = document.getElementById('fw-insert-url-btn');
    var lastActiveTrigger = null;

    function openMediaModal(targetInputId, previewBoxId, triggerEl) {
        lastActiveTrigger = triggerEl || document.activeElement;
        currentActiveTargetInputId = targetInputId;
        currentActivePreviewBoxId = previewBoxId;
        if (mediaModal) {
            mediaModal.hidden = false;
            mediaModal.classList.add('is-open');
            mediaModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Reset tabs to Library tab
            if (modalTabBtns.length > 0) {
                modalTabBtns.forEach(function(b) { b.classList.remove('active'); });
                modalTabBtns[0].classList.add('active');
            }
            var tabLib = document.getElementById('fw-tab-library');
            var tabUpload = document.getElementById('fw-tab-upload');
            var tabUrl = document.getElementById('fw-tab-url');
            if (tabLib) tabLib.style.display = 'block';
            if (tabUpload) tabUpload.style.display = 'none';
            if (tabUrl) tabUrl.style.display = 'none';

            // Set focus inside modal for accessibility
            setTimeout(function() {
                if (librarySearch) {
                    librarySearch.focus();
                } else if (modalCloseBtn) {
                    modalCloseBtn.focus();
                }
            }, 60);

            loadMediaLibrary('');
        }
    }

    function closeMediaModal() {
        if (mediaModal) {
            mediaModal.hidden = true;
            mediaModal.classList.remove('is-open');
            mediaModal.style.display = 'none';
            document.body.style.overflow = '';
            currentActiveTargetInputId = null;
            currentActivePreviewBoxId = null;

            // Restore focus to triggering element
            if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
                try {
                    lastActiveTrigger.focus();
                } catch (e) {}
            }
        }
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMediaModal();
        });
    }

    if (mediaModal) {
        mediaModal.addEventListener('click', function(e) {
            if (e.target === mediaModal) {
                e.preventDefault();
                e.stopPropagation();
                closeMediaModal();
            }
        });
    }

    // ESC Key Listener for Modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc' || e.keyCode === 27) {
            if (mediaModal && (!mediaModal.hidden || mediaModal.classList.contains('is-open') || mediaModal.style.display !== 'none')) {
                e.preventDefault();
                closeMediaModal();
            }
        }
    });

    // Modal Tabs
    modalTabBtns.forEach(function(tabBtn) {
        tabBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modalTabBtns.forEach(function(b) { b.classList.remove('active'); });
            tabBtn.classList.add('active');
            var tab = tabBtn.getAttribute('data-tab');
            var tabLib = document.getElementById('fw-tab-library');
            var tabUpload = document.getElementById('fw-tab-upload');
            var tabUrl = document.getElementById('fw-tab-url');
            if (tabLib) tabLib.style.display = (tab === 'library' ? 'block' : 'none');
            if (tabUpload) tabUpload.style.display = (tab === 'upload' ? 'block' : 'none');
            if (tabUrl) tabUrl.style.display = (tab === 'url' ? 'block' : 'none');
        });
    });

    // Delegate open / clear media buttons
    document.addEventListener('click', function(e) {
        var openBtn = e.target.closest('.fw-open-media-modal');
        if (openBtn) {
            e.preventDefault();
            openMediaModal(openBtn.getAttribute('data-target'), openBtn.getAttribute('data-preview'), openBtn);
            return;
        }
        var clearBtn = e.target.closest('.fw-clear-media');
        if (clearBtn) {
            e.preventDefault();
            var targetInput = document.getElementById(clearBtn.getAttribute('data-target'));
            var previewBox = document.getElementById(clearBtn.getAttribute('data-preview'));
            if (targetInput) targetInput.value = '';
            if (previewBox) previewBox.innerHTML = '<span>No media</span>';
            markDirty();
        }
    });

    // Apply chosen media
    function selectMedia(url) {
        if (currentActiveTargetInputId) {
            var input = document.getElementById(currentActiveTargetInputId);
            if (input) input.value = url;
        }
        if (currentActivePreviewBoxId) {
            var box = document.getElementById(currentActivePreviewBoxId);
            if (box) {
                box.innerHTML = '<img src="' + escapeHtml(url) + '" alt="Preview">';
            }
        }
        markDirty();
        closeMediaModal();
    }

    // Insert URL Tab
    if (insertUrlBtn && customUrlInput) {
        insertUrlBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var url = customUrlInput.value.trim();
            if (url) {
                selectMedia(url);
                customUrlInput.value = '';
            }
        });
        customUrlInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var url = customUrlInput.value.trim();
                if (url) {
                    selectMedia(url);
                    customUrlInput.value = '';
                }
            }
        });
    }

    // Load Media Library
    function loadMediaLibrary(search) {
        if (!mediaGrid) return;
        mediaGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 20px; color: var(--admin-text-muted);">Loading media items&hellip;</div>';
        if (libraryEmpty) libraryEmpty.style.display = 'none';

        var query = '/admin/media/library?category=all&page=1&per_page=30';
        if (search) query += '&s=' + encodeURIComponent(search);

        fetch(query, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            mediaGrid.innerHTML = '';
            var items = (data && data.items) || [];
            if (items.length === 0) {
                if (libraryEmpty) libraryEmpty.style.display = 'block';
                return;
            }
            items.forEach(function(item) {
                var card = document.createElement('div');
                card.className = 'fw-media-grid-item';
                card.title = item.title || item.filename;

                var imgUrl = item.thumbnail_url || item.url;
                card.innerHTML = [
                    '<img src="' + escapeHtml(imgUrl) + '" alt="' + escapeHtml(item.alt_text || item.filename) + '" loading="lazy">',
                    '<span class="fw-item-name">' + escapeHtml(item.filename) + '</span>'
                ].join('');

                card.addEventListener('click', function() {
                    selectMedia(item.url);
                });

                mediaGrid.appendChild(card);
            });
        })
        .catch(function(err) {
            mediaGrid.innerHTML = '<div style="grid-column: 1/-1; color: var(--admin-danger); text-align:center;">Failed to load library items.</div>';
        });
    }

    if (librarySearch) {
        var searchTimeout = null;
        librarySearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadMediaLibrary(librarySearch.value.trim());
            }, 300);
        });
    }

    // Upload Handler
    function handleFileUpload(file) {
        if (!file) return;
        if (uploadProgress) uploadProgress.style.display = 'block';

        var formData = new FormData();
        formData.append('_token', <?php echo json_encode($csrfToken); ?>);
        formData.append('file', file);

        fetch('/admin/media/upload-ajax', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(res) {
            if (uploadProgress) uploadProgress.style.display = 'none';
            if (res && res.success && res.media && res.media.url) {
                selectMedia(res.media.url);
            } else {
                alert('Upload failed: ' + ((res && res.message) || 'Unknown error'));
            }
        })
        .catch(function(err) {
            if (uploadProgress) uploadProgress.style.display = 'none';
            alert('Upload failed: ' + err.message);
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                handleFileUpload(fileInput.files[0]);
            }
        });
    }

    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('drag-over');
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
    }
})();
</script>

