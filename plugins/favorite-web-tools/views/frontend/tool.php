<?php
/**
 * Frontend Individual Tool View
 *
 * Variables:
 * - $tool          : Tool
 * - $category      : ?ToolCategory
 * - $accessCheck   : array{can_access:bool, reason:string}
 * - $userId        : int
 * - $isAdmin       : bool
 * - $relatedTools  : array<Tool>
 * - $csrfToken     : string
 * - $accessControl : AccessControlService
 */

$canAccess = $accessCheck['can_access'] ?? false;
$accessReason = $accessCheck['reason'] ?? '';
$inputSchema = $tool->input_schema ?? [];
$outputSchema = $tool->output_schema ?? [];
$uiSchema = $tool->ui_schema ?? [];
$submitLabel = $uiSchema['submit_label'] ?? 'Run Tool';
?>
<div class="fwt-tool-page-wrap">
    <!-- Breadcrumb -->
    <nav class="fwt-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a>
        <span class="fwt-breadcrumb-sep">/</span>
        <a href="/tools">Tools</a>
        <?php if ($category): ?>
            <span class="fwt-breadcrumb-sep">/</span>
            <a href="/tools?category=<?php echo urlencode($category->slug); ?>">
                <?php echo htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
        <span class="fwt-breadcrumb-sep">/</span>
        <span class="fwt-breadcrumb-current">
            <?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </nav>

    <!-- Admin Preview Notice -->
    <?php if (!$tool->isActive() && $isAdmin): ?>
        <div class="fwt-admin-preview-alert">
            <strong>Admin Preview Mode:</strong> This tool is currently in <code><?php echo htmlspecialchars($tool->status, ENT_QUOTES, 'UTF-8'); ?></code> status and is hidden from public visitors.
        </div>
    <?php endif; ?>

    <!-- Tool Page Layout Grid: Main Content on Left, ONLY Right Sidebar on Right -->
    <div class="fwt-tool-layout fwt-tool-layout--with-sidebar">
        <!-- Main Tool Column -->
        <div class="fwt-tool-main-col">
            <!-- Tool Header Card -->
            <div class="fwt-tool-header">
                <div class="fwt-tool-header-content">
                    <div class="fwt-tool-header-left">
                        <div class="fwt-tool-header-icon-box">
                            <?php echo \FavoriteCMS\Tools\Support\IconRenderer::render($tool->icon ?: '🛠️', 'fwt-header-icon', 28); ?>
                        </div>
                        <div class="fwt-tool-header-text">
                            <h1 class="fwt-tool-title">
                                <?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?>
                            </h1>
                            <p class="fwt-tool-desc">
                                <?php echo htmlspecialchars($tool->description ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="fwt-tool-badges">
                        <?php if ($category): ?>
                            <span class="fwt-badge fwt-badge-category">
                                <?php echo htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($tool->access_mode === 'FREE'): ?>
                            <span class="fwt-badge fwt-badge-free">Free Access</span>
                        <?php elseif ($tool->access_mode === 'LOGIN_REQUIRED'): ?>
                            <span class="fwt-badge fwt-badge-login">Login Required</span>
                        <?php elseif ($tool->access_mode === 'MEMBERSHIP_REQUIRED'): ?>
                            <span class="fwt-badge fwt-badge-member">⭐ Member Only</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Access Gate if user not permitted -->
            <?php if (!$canAccess && !$isAdmin): ?>
                <?php
                $mode = ($tool->access_mode === 'LOGIN_REQUIRED' || $userId <= 0) ? 'LOGIN_REQUIRED' : 'MEMBERSHIP_REQUIRED';
                include __DIR__ . '/access-gate.php';
                ?>
            <?php else: ?>
                <?php
                $hasCustomWorkspace = !empty($tool->html_source);
                $externalLibs = $tool->external_libraries ?? [];
                if (is_string($externalLibs)) {
                    $externalLibs = json_decode($externalLibs, true) ?: [];
                }
                ?>

                <?php if (!empty($externalLibs) && is_array($externalLibs)): ?>
                    <?php foreach ($externalLibs as $libUrl): ?>
                        <?php
                        $cleanUrl = trim((string)$libUrl);
                        if (!preg_match('/^https?:\/\//i', $cleanUrl)) {
                            continue;
                        }
                        $path = parse_url($cleanUrl, PHP_URL_PATH) ?? '';
                        ?>
                        <?php if (str_ends_with(strtolower($path), '.css')): ?>
                            <link rel="stylesheet" href="<?php echo htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php else: ?>
                            <script src="<?php echo htmlspecialchars($cleanUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($tool->css_source)): ?>
                    <style id="fwt-custom-tool-css-<?php echo (int)($tool->id ?? 0); ?>">
                        <?php echo $tool->css_source; ?>
                    </style>
                <?php endif; ?>

                <?php if ($hasCustomWorkspace): ?>
                    <!-- Custom Tool Interactive Workspace (HTML/CSS/JS Engine) -->
                    <div class="fwt-custom-tool-workspace" id="fwt-custom-workspace">
                        <?php echo $tool->html_source; ?>
                    </div>

                    <?php if (!empty($tool->js_source)): ?>
                        <script>
                        (function() {
                            function initTool() {
                                try {
                                    <?php echo $tool->js_source; ?>
                                } catch (err) {
                                    // Suppress internal debugging
                                }
                            }
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', initTool);
                            } else {
                                initTool();
                            }
                        })();
                        </script>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Standard Schema-Driven Tool Workspace (PHP / Python API / Fallback) -->
                    <div class="fwt-tool-workspace">
                        <form id="fwt-execution-form" data-slug="<?php echo htmlspecialchars($tool->slug, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                            <!-- Dynamic Inputs -->
                            <div class="fwt-inputs-container">
                                <?php if (empty($inputSchema)): ?>
                                    <!-- Fallback default textarea if no schema defined -->
                                    <div class="fwt-form-group">
                                        <label for="fwt-input-content" class="fwt-label">
                                            Input Content
                                        </label>
                                        <textarea name="text" id="fwt-input-content" rows="8" placeholder="Paste or type content here..."
                                                  class="fwt-input-control fwt-code-textarea"></textarea>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($inputSchema as $fieldName => $schema): ?>
                                        <?php
                                        $fieldLabel = $schema['label'] ?? ucwords(str_replace('_', ' ', (string)$fieldName));
                                        $fieldType = strtolower((string)($schema['type'] ?? 'text'));
                                        $placeholder = $schema['placeholder'] ?? '';
                                        $helpText = $schema['help'] ?? '';
                                        $required = !empty($schema['required']);
                                        $defaultVal = $schema['default'] ?? '';
                                        $fieldId = 'fwt-field-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string)$fieldName);
                                        ?>
                                        <div class="fwt-form-group">
                                            <label for="<?php echo $fieldId; ?>" class="fwt-label">
                                                <span>
                                                    <?php echo htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if ($required): ?>
                                                        <span class="fwt-required" title="Required">*</span>
                                                    <?php endif; ?>
                                                </span>
                                            </label>

                                            <?php if ($fieldType === 'textarea' || $fieldType === 'json' || $fieldType === 'code'): ?>
                                                <textarea name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                          id="<?php echo $fieldId; ?>"
                                                          rows="<?php echo $schema['rows'] ?? 8; ?>"
                                                          placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"
                                                          class="fwt-input-control fwt-code-textarea"><?php echo htmlspecialchars((string)$defaultVal, ENT_QUOTES, 'UTF-8'); ?></textarea>

                                            <?php elseif ($fieldType === 'select'): ?>
                                                <select name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                        id="<?php echo $fieldId; ?>"
                                                        class="fwt-input-control fwt-select">
                                                    <?php foreach (($schema['options'] ?? []) as $optKey => $optVal): ?>
                                                        <?php
                                                        $val = is_string($optKey) ? $optKey : $optVal;
                                                        $lbl = $optVal;
                                                        $isSelected = ($val == $defaultVal);
                                                        ?>
                                                        <option value="<?php echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars((string)$lbl, ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                            <?php elseif ($fieldType === 'number'): ?>
                                                <input type="number"
                                                       name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                       id="<?php echo $fieldId; ?>"
                                                       value="<?php echo htmlspecialchars((string)$defaultVal, ENT_QUOTES, 'UTF-8'); ?>"
                                                       placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"
                                                       <?php echo isset($schema['min']) ? 'min="' . (int)$schema['min'] . '"' : ''; ?>
                                                       <?php echo isset($schema['max']) ? 'max="' . (int)$schema['max'] . '"' : ''; ?>
                                                       <?php echo isset($schema['step']) ? 'step="' . $schema['step'] . '"' : ''; ?>
                                                       class="fwt-input-control">

                                            <?php elseif ($fieldType === 'checkbox'): ?>
                                                <label class="fwt-checkbox-label">
                                                    <input type="checkbox"
                                                           name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                           id="<?php echo $fieldId; ?>"
                                                           value="1"
                                                           <?php echo !empty($defaultVal) ? 'checked' : ''; ?>
                                                           class="fwt-checkbox">
                                                    <span><?php echo htmlspecialchars($schema['checkbox_label'] ?? $fieldLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                                </label>

                                            <?php elseif ($fieldType === 'file'): ?>
                                                <input type="file"
                                                       name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                       id="<?php echo $fieldId; ?>"
                                                       <?php echo !empty($schema['accept']) ? 'accept="' . htmlspecialchars($schema['accept'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>
                                                       class="fwt-input-file">

                                            <?php else: ?>
                                                <input type="<?php echo $fieldType === 'url' ? 'url' : 'text'; ?>"
                                                       name="<?php echo htmlspecialchars((string)$fieldName, ENT_QUOTES, 'UTF-8'); ?>"
                                                       id="<?php echo $fieldId; ?>"
                                                       value="<?php echo htmlspecialchars((string)$defaultVal, ENT_QUOTES, 'UTF-8'); ?>"
                                                       placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"
                                                       class="fwt-input-control">
                                            <?php endif; ?>

                                            <?php if ($helpText !== ''): ?>
                                                <div class="fwt-help-text">
                                                    <?php echo htmlspecialchars($helpText, ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Action Controls -->
                            <div class="fwt-action-controls">
                                <button type="submit" id="fwt-submit-btn" class="fwt-btn fwt-btn-primary">
                                    <span id="fwt-submit-text"><?php echo htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span id="fwt-spinner" class="fwt-spinner" style="display: none;"></span>
                                </button>

                                <button type="button" id="fwt-reset-btn" class="fwt-btn fwt-btn-secondary">
                                    Clear
                                </button>
                            </div>
                        </form>

                        <!-- Execution Error Alert -->
                        <div id="fwt-error-alert" class="fwt-error-alert" style="display: none;">
                            <div class="fwt-error-alert-content">
                                <span class="fwt-error-icon">⚠️</span>
                                <div id="fwt-error-message"></div>
                            </div>
                        </div>

                        <!-- Dynamic Result Area -->
                        <div id="fwt-result-container" class="fwt-result-container" style="display: none;">
                            <div class="fwt-result-header">
                                <div class="fwt-result-title-row">
                                    <h3 class="fwt-result-heading">Result</h3>
                                    <span id="fwt-result-badge" class="fwt-badge fwt-badge-result">TEXT</span>
                                </div>

                                <div class="fwt-result-actions">
                                    <button type="button" id="fwt-copy-btn" class="fwt-btn fwt-btn-copy">
                                        <span id="fwt-copy-icon">📋</span>
                                        <span id="fwt-copy-label">Copy</span>
                                    </button>
                                    <a id="fwt-download-btn" href="#" download="" class="fwt-btn fwt-btn-primary fwt-btn-download" style="display: none;">
                                        <span>⬇ Download</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Text / JSON Result Box -->
                            <div id="fwt-result-text-box" style="display: none;">
                                <pre id="fwt-result-code" class="fwt-result-code"></pre>
                            </div>

                            <!-- HTML / CSS Isolated Preview Frame -->
                            <div id="fwt-result-preview-box" class="fwt-result-preview-box" style="display: none;">
                                <div class="fwt-preview-label">Live Preview (Sandbox Isolated):</div>
                                <iframe id="fwt-preview-frame" sandbox="allow-same-origin allow-scripts" class="fwt-preview-frame"></iframe>
                            </div>

                            <!-- Download / File Info Box -->
                            <div id="fwt-result-download-box" class="fwt-result-download-box" style="display: none;">
                                <div class="fwt-download-icon">📦</div>
                                <div id="fwt-download-filename" class="fwt-download-filename"></div>
                                <div id="fwt-download-filesize" class="fwt-download-filesize"></div>
                                <a id="fwt-download-action-link" href="#" class="fwt-btn fwt-btn-primary">
                                    Download File
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- RIGHT Sidebar Column (ONLY Right Sidebar, No Left Sidebar) -->
        <aside class="fwt-tool-sidebar-col" aria-label="Tool Information and Related Tools">
            <!-- Tool Details Card -->
            <div class="fwt-card fwt-sidebar-widget">
                <h3 class="fwt-sidebar-widget-title">About This Tool</h3>
                <div class="fwt-sidebar-details">
                    <div class="fwt-sidebar-detail-row">
                        <span class="fwt-sidebar-detail-label">Engine:</span>
                        <span class="fwt-sidebar-detail-val"><?php echo htmlspecialchars($tool->engine, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php if ($category): ?>
                    <div class="fwt-sidebar-detail-row">
                        <span class="fwt-sidebar-detail-label">Category:</span>
                        <span class="fwt-sidebar-detail-val">
                            <a href="/tools?category=<?php echo urlencode($category->slug); ?>"><?php echo htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8'); ?></a>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="fwt-sidebar-detail-row">
                        <span class="fwt-sidebar-detail-label">Access:</span>
                        <span class="fwt-sidebar-detail-val"><?php echo htmlspecialchars($tool->access_mode, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="fwt-sidebar-detail-row">
                        <span class="fwt-sidebar-detail-label">Status:</span>
                        <span class="fwt-sidebar-detail-val"><?php echo htmlspecialchars($tool->status, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Related Tools in Same Category -->
            <?php if (!empty($relatedTools)): ?>
                <div class="fwt-card fwt-sidebar-widget fwt-related-tools">
                    <h3 class="fwt-sidebar-widget-title">
                        <?php echo $category ? 'More in ' . htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') : 'Related Tools'; ?>
                    </h3>
                    <div class="fwt-sidebar-tools-list">
                        <?php foreach ($relatedTools as $rel): ?>
                            <a href="/tools/<?php echo urlencode($rel->slug); ?>" class="fwt-sidebar-rel-link">
                                <span class="fwt-sidebar-rel-icon"><?php echo \FavoriteCMS\Tools\Support\IconRenderer::render($rel->icon ?: '🛠️', 'fwt-rel-icon', 20); ?></span>
                                <div class="fwt-sidebar-rel-meta">
                                    <div class="fwt-sidebar-rel-name"><?php echo htmlspecialchars($rel->name, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="fwt-sidebar-rel-mode"><?php echo htmlspecialchars($rel->access_mode, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
