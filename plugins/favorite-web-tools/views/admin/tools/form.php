<?php
/**
 * Admin Tool Create/Edit Form View — Universal Tool Platform Builder
 *
 * Variables:
 * - $tool               : Tool
 * - $categories         : array<ToolCategory>
 * - $pythonServices     : array<PythonService>
 * - $registeredHandlers : array<string, string>
 * - $csrfToken          : string
 * - $isEdit             : bool
 * - $flashSuccess       : ?string
 * - $flashError         : ?string
 */

$inputSchemaJson = !empty($tool->input_schema) ? json_encode($tool->input_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : "{}";
$outputSchemaJson = !empty($tool->output_schema) ? json_encode($tool->output_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : "{}";
$uiSchemaJson = !empty($tool->ui_schema) ? json_encode($tool->ui_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : "{}";

$extLibs = $tool->external_libraries ?? [];
$extLibsText = is_array($extLibs) ? implode("\n", $extLibs) : (string)$extLibs;
$currentHandlerId = $tool->handler_id ?? '';
$currentEngine = strtoupper($tool->engine ?? 'PHP');
?>
<div class="fwt-admin-wrap" style="max-width: 960px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1e293b;">
    <div style="margin-bottom: 20px;">
        <a href="/admin/page/favorite-web-tools" style="color: #2563eb; text-decoration: none; font-size: 13px; font-weight: 500;">← Back to Tools List</a>
        <h1 style="font-size: 24px; font-weight: 700; margin: 8px 0 4px 0; color: #0f172a;">
            <?php echo $isEdit ? 'Edit Tool: ' . htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8') : 'Universal Tool Builder — Create Tool'; ?>
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 0;">
            Configure tools powered by controlled PHP handlers, Python API endpoints, or client-side HTML / CSS / JavaScript engines.
        </p>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div style="background: #e7f7ed; border-left: 4px solid #28a745; padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; color: #155724; font-size: 14px;">
            <strong>Success:</strong> <?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div style="background: #fdf2f2; border-left: 4px solid #dc3545; padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; color: #721c24; font-size: 14px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/page/favorite-web-tools" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo (int)($tool->id ?? 0); ?>">

        <!-- Section 1: Basic Information -->
        <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                1. Basic Information
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Tool Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($tool->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required
                           style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Slug (URL Identifier)</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($tool->slug ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="auto-generated if empty"
                           style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Description</label>
                <textarea name="description" rows="2" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box;"><?php echo htmlspecialchars($tool->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Category</label>
                    <select name="category_id" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat->id; ?>" <?php echo ($tool->category_id === $cat->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Access Mode *</label>
                    <select name="access_mode" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                        <option value="FREE" <?php echo $tool->access_mode === 'FREE' ? 'selected' : ''; ?>>FREE (Public)</option>
                        <option value="LOGIN_REQUIRED" <?php echo $tool->access_mode === 'LOGIN_REQUIRED' ? 'selected' : ''; ?>>LOGIN_REQUIRED</option>
                        <option value="MEMBERSHIP_REQUIRED" <?php echo $tool->access_mode === 'MEMBERSHIP_REQUIRED' ? 'selected' : ''; ?>>MEMBERSHIP_REQUIRED</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status *</label>
                    <select name="status" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-sizing: border-box;">
                        <option value="DRAFT" <?php echo $tool->status === 'DRAFT' ? 'selected' : ''; ?>>DRAFT (Hidden)</option>
                        <option value="ACTIVE" <?php echo $tool->status === 'ACTIVE' ? 'selected' : ''; ?>>ACTIVE (Live)</option>
                        <option value="DISABLED" <?php echo $tool->status === 'DISABLED' ? 'selected' : ''; ?>>DISABLED</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Canonical Execution Engine -->
        <div style="margin-bottom: 28px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 12px 0;">
                2. Execution Engine Selection
            </h3>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Canonical Engine *</label>
                <select name="engine" id="fwt-engine-select" onchange="updateEnginePanels()"
                        style="width: 100%; max-width: 360px; padding: 9px 12px; font-size: 14px; font-weight: 600; border: 2px solid #2563eb; border-radius: 6px; background: #fff; color: #1e293b;">
                    <option value="PHP" <?php echo $currentEngine === 'PHP' ? 'selected' : ''; ?>>PHP (Controlled Handler)</option>
                    <option value="HTML" <?php echo $currentEngine === 'HTML' ? 'selected' : ''; ?>>HTML Engine</option>
                    <option value="CSS" <?php echo $currentEngine === 'CSS' ? 'selected' : ''; ?>>CSS Engine</option>
                    <option value="JAVASCRIPT" <?php echo $currentEngine === 'JAVASCRIPT' ? 'selected' : ''; ?>>JavaScript Engine</option>
                    <option value="PYTHON_API" <?php echo $currentEngine === 'PYTHON_API' ? 'selected' : ''; ?>>Python API Service</option>
                </select>
            </div>

            <!-- Panel A: PHP Controlled Handlers -->
            <div id="fwt-panel-php" style="display: none; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 10px 0; color: #334155;">Controlled PHP Handler</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Registered Controlled Handler</label>
                        <select name="handler_id" id="fwt-handler-id-select" style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                            <option value="">-- Select Controlled Handler --</option>
                            <?php foreach ($registeredHandlers as $hId => $hName): ?>
                                <option value="<?php echo htmlspecialchars($hId, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($currentHandlerId === $hId || (empty($currentHandlerId) && !empty($tool->handler_class) && str_ends_with($tool->handler_class, str_replace(' ', '', ucwords(str_replace('-', ' ', $hId))) . 'Handler'))) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hName . ' (' . $hId . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Choose from the 29 verified server-side PHP handlers.</div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">PHP Handler Class (optional fallback)</label>
                        <input type="text" name="handler_class" value="<?php echo htmlspecialchars($tool->handler_class ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="FavoriteCMS\Tools\Handlers\JsonFormatterHandler"
                               style="width: 100%; padding: 9px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>
            </div>

            <!-- Panel B: Python API Service -->
            <div id="fwt-panel-python" style="display: none; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 10px 0; color: #334155;">Python Microservice Integration</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Python Service *</label>
                        <select name="python_service_id" id="fwt-python-service-select" onchange="updateResolvedEndpointPreview()"
                                style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                            <option value="" data-base-url="" data-default-endpoint="/download/api">-- Select Active Python Service --</option>
                            <?php foreach ($pythonServices as $ps): ?>
                                <option value="<?php echo (int)$ps->id; ?>"
                                        data-base-url="<?php echo htmlspecialchars($ps->base_url, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-default-endpoint="<?php echo htmlspecialchars($ps->default_endpoint_path ?? '/download/api', ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($tool->python_service_id === $ps->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ps->name, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            Active Python services configured in <a href="/admin/page/favorite-web-tools-python-services" target="_blank" style="color: #2563eb;">Python Services</a>.
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Endpoint Path</label>
                        <input type="text" name="python_endpoint" id="fwt-python-endpoint-input"
                               value="<?php echo htmlspecialchars($tool->python_endpoint ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="/download/api"
                               oninput="updateResolvedEndpointPreview()"
                               style="width: 100%; padding: 9px 12px; font-size: 13px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">e.g. <code>/download/api</code></div>
                    </div>

                    <!-- Read-only Resolved Endpoint Preview -->
                    <div style="grid-column: 1 / -1; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 3px;">
                            Resolved Endpoint: <span id="fwt-resolved-endpoint-preview" style="font-family: monospace; color: #2563eb; font-weight: 700;">None (select a service)</span>
                        </div>
                        <div style="font-size: 11px; color: #64748b;">
                            Informational preview. The selected Python service determines the base host. Host overrides are blocked.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel C: Custom HTML / CSS / JavaScript Code -->
            <div id="fwt-panel-custom-code" style="display: none; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 6px 0; color: #334155;">Client-Side Tool Source Code</h4>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">
                    Define the custom HTML markup, scoped CSS stylesheets, and interactive client JavaScript. Custom code is rendered directly in the tool workspace.
                </p>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        External Libraries (CDN URLs, one per line)
                    </label>
                    <textarea name="external_libraries" rows="3" placeholder="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js&#10;https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"
                              style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($extLibsText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Only HTTPS/HTTP URLs allowed. Scripts (.js) and stylesheets (.css) are automatically loaded before execution.</div>
                </div>

                <div style="margin-bottom: 14px;" id="fwt-field-html-source">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">HTML Template / Workspace Layout</label>
                    <textarea name="html_source" rows="8" placeholder="<div class='my-tool-container'>&#10;  <input type='text' id='my-input' placeholder='Enter data...'>&#10;  <button id='my-run-btn'>Process</button>&#10;  <div id='my-output'></div>&#10;</div>"
                              style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($tool->html_source ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div style="margin-bottom: 14px;" id="fwt-field-css-source">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Scoped CSS Stylesheet</label>
                    <textarea name="css_source" rows="6" placeholder=".my-tool-container { display: flex; flex-direction: column; gap: 12px; }&#10;.my-tool-container input { padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; }"
                              style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($tool->css_source ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div style="margin-bottom: 14px;" id="fwt-field-js-source">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Client-side JavaScript Code</label>
                    <textarea name="js_source" rows="8" placeholder="document.getElementById('my-run-btn').addEventListener('click', function() {&#10;    const val = document.getElementById('my-input').value;&#10;    document.getElementById('my-output').innerText = 'Processed: ' + val;&#10;});"
                              style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($tool->js_source ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Runs safely in a client-side scoped closure after DOM initialization.</div>
                </div>
            </div>
        </div>

        <!-- Section 3: UI & Display -->
        <div style="margin-bottom: 28px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 16px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                3. UI & Display
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Icon (Emoji or Identifier)</label>
                    <input type="text" name="icon" value="<?php echo htmlspecialchars($tool->icon ?? '🛠️', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="🛠️, code, palette, qrcode..."
                           style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Use emojis or identifiers (code, palette, qrcode, etc.)</div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Thumbnail URL</label>
                    <input type="text" name="thumbnail" value="<?php echo htmlspecialchars($tool->thumbnail ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="https://.../thumbnail.png"
                           style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Display Order</label>
                    <input type="number" name="display_order" value="<?php echo (int)($tool->display_order ?? 0); ?>"
                           style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                            Frontend Presentation Design
                        </label>
                        <select name="frontend_design_slug" id="fwt-frontend-design-select"
                                style="width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                            <option value="">Default Tool UI (Standard Output & JSON Fallback)</option>
                            <?php if (!empty($designs)): ?>
                                <?php foreach ($designs as $design): ?>
                                    <option value="<?php echo htmlspecialchars($design->getSlug(), ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo (($tool->frontend_design_slug ?? '') === $design->getSlug()) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($design->getName(), ENT_QUOTES, 'UTF-8'); ?>
                                        (<?php echo $design->isBuiltin() ? 'Built-in' : 'Custom'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                            Decouples execution engine from frontend card/table/grid presentation.
                        </div>
                    </div>
                    <div>
                        <a href="/admin/web-tools/frontend-designs" target="_blank"
                           style="display: inline-flex; align-items: center; gap: 4px; padding: 9px 14px; font-size: 13px; font-weight: 600; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; text-decoration: none; white-space: nowrap;">
                            🎨 Manage Designs ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: JSON Schemas (Dynamic Schema-Driven Tools) -->
        <div style="margin-bottom: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">
                    4. JSON Schemas (Dynamic Form Builder)
                </h3>
                <button type="button" onclick="toggleSchemaDetails()" id="fwt-schema-toggle-btn"
                        style="background: transparent; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 10px; font-size: 12px; cursor: pointer; color: #475569;">
                    Expand / Collapse
                </button>
            </div>

            <div id="fwt-schemas-container">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Input Schema (JSON)</label>
                        <textarea name="input_schema" rows="7" style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($inputSchemaJson, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Output Schema (JSON)</label>
                        <textarea name="output_schema" rows="7" style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($outputSchemaJson, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">UI Schema (JSON)</label>
                    <textarea name="ui_schema" rows="3" style="width: 100%; padding: 8px 12px; font-size: 12px; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?php echo htmlspecialchars($uiSchemaJson, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Submit Controls -->
        <div style="display: flex; gap: 12px; align-items: center; padding-top: 12px; border-top: 1px solid #f1f5f9;">
            <button type="submit" style="padding: 11px 28px; font-size: 14px; font-weight: 600; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; transition: background 0.15s ease;">
                <?php echo $isEdit ? 'Save Changes' : 'Create Tool'; ?>
            </button>
            <a href="/admin/page/favorite-web-tools" style="padding: 11px 18px; font-size: 14px; color: #475569; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateResolvedEndpointPreview() {
    var svcSelect = document.getElementById('fwt-python-service-select');
    var epInput = document.getElementById('fwt-python-endpoint-input');
    var preview = document.getElementById('fwt-resolved-endpoint-preview');
    if (!svcSelect || !preview) return;

    var selectedOpt = svcSelect.options[svcSelect.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) {
        preview.textContent = 'None (select a service)';
        return;
    }

    var baseUrl = selectedOpt.getAttribute('data-base-url') || '';
    var defaultEp = selectedOpt.getAttribute('data-default-endpoint') || '/download/api';

    if (epInput && (!epInput.value || epInput.value.trim() === '')) {
        epInput.value = defaultEp;
    }

    var ep = epInput ? epInput.value.trim() : '';
    if (!ep) ep = defaultEp;

    if (ep.startsWith('http://') || ep.startsWith('https://')) {
        preview.textContent = ep;
    } else {
        if (!ep.startsWith('/')) ep = '/' + ep;
        preview.textContent = baseUrl.replace(/\/+$/, '') + ep;
    }
}

function updateEnginePanels() {
    var engineSelect = document.getElementById('fwt-engine-select');
    var val = engineSelect ? engineSelect.value.toUpperCase() : 'PHP';

    var panelPhp = document.getElementById('fwt-panel-php');
    var panelPython = document.getElementById('fwt-panel-python');
    var panelCustom = document.getElementById('fwt-panel-custom-code');

    var fieldHtml = document.getElementById('fwt-field-html-source');
    var fieldCss = document.getElementById('fwt-field-css-source');
    var fieldJs = document.getElementById('fwt-field-js-source');

    if (panelPhp) panelPhp.style.display = (val === 'PHP') ? 'block' : 'none';
    if (panelPython) {
        panelPython.style.display = (val === 'PYTHON_API') ? 'block' : 'none';
        if (val === 'PYTHON_API') {
            updateResolvedEndpointPreview();
        }
    }
    if (panelCustom) panelCustom.style.display = (val === 'HTML' || val === 'CSS' || val === 'JAVASCRIPT') ? 'block' : 'none';

    if (fieldHtml) fieldHtml.style.display = (val === 'CSS') ? 'none' : 'block';
    if (fieldJs) fieldJs.style.display = (val === 'CSS') ? 'none' : 'block';
    if (fieldCss) fieldCss.style.display = 'block';
}

function toggleSchemaDetails() {
    var container = document.getElementById('fwt-schemas-container');
    if (container) {
        container.style.display = (container.style.display === 'none') ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateEnginePanels();
    updateResolvedEndpointPreview();
});
</script>
