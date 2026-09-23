<?php
/**
 * Admin Frontend Design Interactive Preview View
 *
 * Variables:
 * - $currentDesign : FrontendDesign
 * - $allDesigns    : array<FrontendDesign>
 * - $csrfToken     : string
 */
$sampleMedia = [
    'title' => 'Sample 4K Nature Video Asset No Copyright',
    'thumbnail' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=640&q=80',
    'duration_string' => '3:45',
    'channel' => 'Cinematic Nature Assets',
    'videos' => [
        [
            'formatId' => '137',
            'label' => '1080p - H.264 - MP4',
            'quality' => 1080,
            'ext' => 'mp4',
            'filesize' => 45678900,
            'vcodec' => 'H.264',
            'acodec' => 'AAC',
            'hasAudio' => true,
            'url' => 'https://example.com/download/sample_1080p.mp4',
        ],
        [
            'formatId' => '136',
            'label' => '720p - H.264 - MP4',
            'quality' => 720,
            'ext' => 'mp4',
            'filesize' => 24567800,
            'vcodec' => 'H.264',
            'acodec' => 'AAC',
            'hasAudio' => true,
            'url' => 'https://example.com/download/sample_720p.mp4',
        ],
        [
            'formatId' => '18',
            'label' => '360p - H.264 - MP4',
            'quality' => 360,
            'ext' => 'mp4',
            'filesize' => 8456780,
            'vcodec' => 'H.264',
            'acodec' => 'AAC',
            'hasAudio' => true,
            'url' => 'https://example.com/download/sample_360p.mp4',
        ],
    ],
    'audios' => [
        [
            'formatId' => '140',
            'label' => '320 kbps - High Quality MP3',
            'bitrate' => 320,
            'ext' => 'mp3',
            'filesize' => 4567890,
            'acodec' => 'MP3',
            'url' => 'https://example.com/download/sample_audio_320k.mp3',
        ],
    ],
];
?>
<div class="fwt-admin-wrap" style="max-width: 1200px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <a href="/admin/page/favorite-web-tools-frontend-designs" style="color: #64748b; text-decoration: none; font-size: 13px;">
                ← Back to Frontend Designs
            </a>
            <h1 style="font-size: 22px; font-weight: 700; margin: 6px 0 0 0; color: #1e293b;">
                Interactive Design Sandbox & Preview
            </h1>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <label style="font-size: 13px; font-weight: 600; color: #475569;">Active Design:</label>
            <select id="fwt-preview-design-select" onchange="switchDesign(this.value)"
                    style="padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-weight: 600;">
                <?php foreach ($allDesigns as $d): ?>
                    <option value="<?php echo htmlspecialchars($d->getSlug(), ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo ($d->getSlug() === $currentDesign->getSlug()) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d->getName(), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="/admin/page/favorite-web-tools-frontend-designs?action=edit&id=<?php echo (int)$currentDesign->getId(); ?>"
               id="fwt-edit-current-link"
               style="padding: 8px 14px; font-size: 13px; font-weight: 600; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">
                Edit Design ↗
            </a>
        </div>
    </div>

    <!-- 2-Column Split: Controls/Mock JSON on left, Live Canvas on right -->
    <div style="display: grid; grid-template-columns: 420px 1fr; gap: 24px; align-items: start;">
        <!-- Left Panel: Data & Payload -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0; color: #0f172a;">Mock API Payload</h3>
                <button type="button" onclick="loadSampleMedia()" style="background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 12px; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                    Reset Sample
                </button>
            </div>

            <p style="font-size: 12px; color: #64748b; margin: 0 0 12px 0;">
                Edit the JSON payload below to simulate API responses and verify how the template normalizes and formats the data.
            </p>

            <textarea id="fwt-mock-json" rows="18"
                      style="width: 100%; font-family: Consolas, monospace; font-size: 12px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; background: #0f172a; color: #38bdf8; line-height: 1.4;"><?php echo json_encode($sampleMedia, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></textarea>

            <button type="button" id="fwt-render-btn" onclick="executePreview()"
                    style="width: 100%; margin-top: 14px; background: #059669; color: #fff; border: none; padding: 10px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 6px;">
                ⚡ Re-Render Canvas
            </button>
        </div>

        <!-- Right Panel: Live Rendered Canvas -->
        <div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-top-left-radius: 10px; border-top-right-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 600; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px;">
                    <span>Live UI Preview Output</span>
                    <span id="fwt-render-status" style="font-size: 11px; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px;">Ready</span>
                </div>
                <div style="font-size: 12px; color: #64748b;" id="fwt-format-count">
                    Rendering inside scoped container
                </div>
            </div>

            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-top: none; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; padding: 24px; min-height: 480px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <!-- Dynamic Injected CSS for preview -->
                <style id="fwt-preview-scoped-css"></style>

                <!-- Scoped Design Container -->
                <div id="fwt-preview-output-box" class="fwt-design-container" data-design="<?php echo htmlspecialchars($currentDesign->getSlug(), ENT_QUOTES, 'UTF-8'); ?>">
                    <!-- Rendered HTML will appear here -->
                    <div style="text-align: center; color: #94a3b8; padding: 60px 20px;">
                        Click <strong>Re-Render Canvas</strong> to see live output.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
let CURRENT_SLUG = <?php echo json_encode($currentDesign->getSlug()); ?>;

function switchDesign(slug) {
    CURRENT_SLUG = slug;
    document.getElementById('fwt-preview-output-box').setAttribute('data-design', slug);
    executePreview();
}

function loadSampleMedia() {
    const sample = <?php echo json_encode($sampleMedia); ?>;
    document.getElementById('fwt-mock-json').value = JSON.stringify(sample, null, 2);
    executePreview();
}

async function executePreview() {
    const statusEl = document.getElementById('fwt-render-status');
    const container = document.getElementById('fwt-preview-output-box');
    const styleEl = document.getElementById('fwt-preview-scoped-css');
    const jsonStr = document.getElementById('fwt-mock-json').value;

    statusEl.textContent = 'Rendering...';
    statusEl.style.background = '#fef3c7';
    statusEl.style.color = '#92400e';

    try {
        const formData = new FormData();
        formData.append('_token', CSRF_TOKEN);
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('action', 'render_preview');
        formData.append('slug', CURRENT_SLUG);
        formData.append('mock_json', jsonStr);

        const postUrl = window.location.pathname.includes('favorite-web-tools-frontend-designs') ? window.location.pathname : '/admin/page/favorite-web-tools-frontend-designs';
        const resp = await fetch(postUrl, {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();
        if (data.success) {
            styleEl.textContent = data.css || '';
            container.innerHTML = data.rendered_html;
            statusEl.textContent = 'Rendered OK';
            statusEl.style.background = '#dcfce7';
            statusEl.style.color = '#166534';
        } else {
            container.innerHTML = '<div style="color: #dc2626; padding: 20px;">Render Error: ' + (data.error || 'Unknown') + '</div>';
            statusEl.textContent = 'Error';
            statusEl.style.background = '#fee2e2';
            statusEl.style.color = '#991b1b';
        }
    } catch (err) {
        container.innerHTML = '<div style="color: #dc2626; padding: 20px;">Network/Script Error: ' + err.message + '</div>';
        statusEl.textContent = 'Failed';
        statusEl.style.background = '#fee2e2';
        statusEl.style.color = '#991b1b';
    }
}

// Auto-render on first page load
document.addEventListener('DOMContentLoaded', () => {
    executePreview();
});
</script>

