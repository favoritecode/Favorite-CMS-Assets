(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initCatalogSearch();
        initToolExecution();
    });

    function initCatalogSearch() {
        const searchInput = document.getElementById('fwt-catalog-search-input');
        if (!searchInput) return;

        let debounceTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                const form = searchInput.closest('form');
                if (form) {
                    form.submit();
                }
            }, 600);
        });
    }

    function initToolExecution() {
        const form = document.getElementById('fwt-execution-form');
        if (!form) return;

        const slug = form.dataset.slug;
        const submitBtn = document.getElementById('fwt-submit-btn');
        const submitText = document.getElementById('fwt-submit-text');
        const spinner = document.getElementById('fwt-spinner');
        const resetBtn = document.getElementById('fwt-reset-btn');
        const errorAlert = document.getElementById('fwt-error-alert');
        const errorMessage = document.getElementById('fwt-error-message');
        const resultContainer = document.getElementById('fwt-result-container');
        const resultBadge = document.getElementById('fwt-result-badge');
        const textBox = document.getElementById('fwt-result-text-box');
        const resultCode = document.getElementById('fwt-result-code');
        const previewBox = document.getElementById('fwt-result-preview-box');
        const previewFrame = document.getElementById('fwt-preview-frame');
        const downloadBox = document.getElementById('fwt-result-download-box');
        const downloadFilename = document.getElementById('fwt-download-filename');
        const downloadFilesize = document.getElementById('fwt-download-filesize');
        const downloadLink = document.getElementById('fwt-download-action-link');
        const copyBtn = document.getElementById('fwt-copy-btn');
        const copyLabel = document.getElementById('fwt-copy-label');

        let lastResultRawText = '';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            setLoading(true);
            hideError();

            const endpoint = form.dataset.endpoint || ('/api/tools/' + encodeURIComponent(slug) + '/execute');
            const formData = new FormData(form);

            // Populate both inputs[...] array keys and flat keys for maximum server compatibility
            const flatInputs = {};
            formData.forEach((value, key) => {
                if (key !== '_token' && !key.startsWith('inputs[')) {
                    flatInputs[key] = value;
                }
            });
            for (const [k, v] of Object.entries(flatInputs)) {
                if (!formData.has('inputs[' + k + ']')) {
                    formData.append('inputs[' + k + ']', v);
                }
            }

            let response;
            try {
                response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': formData.get('_token') || ''
                    }
                });
            } catch (networkErr) {
                showError('Network error or server unreachable. Please try again.');
                resultContainer.style.display = 'none';
                setLoading(false);
                return;
            }

            try {
                let data = null;
                const contentType = (response.headers && response.headers.get('content-type')) ? response.headers.get('content-type') : '';
                if (contentType.includes('application/json')) {
                    try {
                        data = await response.json();
                    } catch (parseErr) {
                        data = null;
                    }
                } else {
                    const textResp = await response.text();
                    try {
                        data = JSON.parse(textResp);
                    } catch (e) {
                        data = null;
                    }
                }

                if (!response.ok || !data || !data.success) {
                    let errMsg = '';
                    if (data && data.error) {
                        errMsg = (typeof data.error === 'object' && data.error.message) ? data.error.message : String(data.error);
                    } else if (data && data.message) {
                        errMsg = String(data.message);
                    } else {
                        switch (response.status) {
                            case 400:
                                errMsg = 'Invalid request parameters (HTTP 400). Please check your input.';
                                break;
                            case 401:
                                errMsg = 'Please log in to use this tool (HTTP 401).';
                                break;
                            case 403:
                                errMsg = 'Access denied. You do not have permission to run this tool (HTTP 403).';
                                break;
                            case 404:
                                errMsg = 'Tool execution endpoint not found (HTTP 404).';
                                break;
                            case 413:
                                errMsg = 'Request payload too large (HTTP 413).';
                                break;
                            case 422:
                                errMsg = 'Input validation failed (HTTP 422). Please provide a valid input.';
                                break;
                            case 502:
                                errMsg = 'External Python service error or gateway failure (HTTP 502).';
                                break;
                            case 504:
                                errMsg = 'The external service request timed out (HTTP 504). Please try again.';
                                break;
                            case 500:
                                errMsg = 'A server error occurred during tool execution (HTTP 500).';
                                break;
                            default:
                                errMsg = 'Server returned HTTP ' + response.status + '.';
                        }
                    }
                    showError(sanitizeClientError(errMsg));
                    resultContainer.style.display = 'none';
                    return;
                }

                const resultPayload = data.result || data.data || data;
                displayResult(resultPayload, data);
            } catch (renderErr) {
                showError('An error occurred while displaying the tool result.');
                resultContainer.style.display = 'none';
            } finally {
                setLoading(false);
            }
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                form.reset();
                hideError();
                resultContainer.style.display = 'none';
                const formatsContainer = document.getElementById('fwt-media-formats-container');
                if (formatsContainer) formatsContainer.style.display = 'none';
                if (previewFrame) previewFrame.srcdoc = '';
            });
        }

        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                if (!lastResultRawText) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(lastResultRawText).then(() => {
                        setCopied(true);
                    }).catch(() => {
                        fallbackCopy(lastResultRawText);
                    });
                } else {
                    fallbackCopy(lastResultRawText);
                }
            });
        }

        function setCopied(isCopied) {
            if (isCopied) {
                copyLabel.textContent = 'Copied!';
                setTimeout(() => {
                    copyLabel.textContent = 'Copy';
                }, 2000);
            }
        }

        function fallbackCopy(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                setCopied(true);
            } catch (e) {}
            document.body.removeChild(ta);
        }

        function setLoading(isLoading) {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                spinner.style.display = 'inline-block';
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                spinner.style.display = 'none';
            }
        }

        function showError(msg) {
            errorMessage.textContent = msg;
            errorAlert.style.display = 'block';
        }

        function hideError() {
            errorAlert.style.display = 'none';
            errorMessage.textContent = '';
        }

        function sanitizeClientError(msg) {
            if (!msg || typeof msg !== 'string') return 'Execution failed. Please check your input and try again.';
            return msg
                .replace(/[a-zA-Z]:[\\/][\w\s.-]+([\\/][\w\s.-]+)*/g, '[path]')
                .replace(/\/(?:var|tmp|home|etc|usr|opt|app|src|tests|views|plugins|themes)\/[\w\s.-]+/gi, '[path]')
                .replace(/FavoriteCMS\\[\w\\]+/g, '')
                .trim();
        }

        function isSafeDownloadUrl(url) {
            if (!url || typeof url !== 'string') return false;
            const trimmed = url.trim().toLowerCase();
            return trimmed.startsWith('https://') || trimmed.startsWith('http://') || trimmed.startsWith('/');
        }

        function isFormatCandidate(item) {
            if (!item || typeof item !== 'object') return false;
            return Boolean(
                item.url ||
                item.formatId ||
                item.format_id ||
                item.quality ||
                item.resolution ||
                item.bitrate ||
                item.ext ||
                item.vcodec ||
                item.acodec ||
                item.download_url
            );
        }

        function extractMediaMeta(payload) {
            if (!payload || typeof payload !== 'object') return {};

            const candidates = [
                payload,
                payload.result,
                payload.data,
                payload.value,
                payload.data && payload.data.result,
                payload.result && payload.result.data,
                payload.data && payload.data.value,
                payload.result && payload.result.value
            ];

            let title = '';
            let thumbnail = '';
            let duration = '';
            let normalizedUrl = '';

            for (let i = 0; i < candidates.length; i++) {
                const c = candidates[i];
                if (!c || typeof c !== 'object' || Array.isArray(c)) continue;
                if (!title && c.title) title = String(c.title);
                if (!thumbnail && c.thumbnail) thumbnail = String(c.thumbnail);
                if (!duration && (c.duration_string || c.duration)) {
                    duration = String(c.duration_string || (c.duration + 's'));
                }
                if (!normalizedUrl && c.normalized_url) normalizedUrl = String(c.normalized_url);
            }

            return { title, thumbnail, duration, normalizedUrl };
        }

        function processAndSortFormats(rawList) {
            if (!Array.isArray(rawList)) return [];

            const seenKeys = new Set();
            const processed = [];

            rawList.forEach(function (item) {
                if (!item || typeof item !== 'object') return;

                const ext = String(item.ext || item.extension || item.container || '').toLowerCase();
                const formatId = String(item.formatId || item.format_id || item.id || '').trim();
                const vcodec = String(item.vcodec || item.video_codec || '').trim();
                const acodec = String(item.acodec || item.audio_codec || '').trim();
                const hasVcodec = vcodec !== '' && vcodec.toLowerCase() !== 'none';
                const hasAcodec = acodec !== '' && acodec.toLowerCase() !== 'none';

                const isAudioCategory = item._streamCategory === 'audio';
                const isAudioExt = ['m4a', 'mp3', 'aac', 'opus', 'wav', 'ogg', 'flac'].includes(ext);
                const isAudioOnly = isAudioCategory || (!hasVcodec && (hasAcodec || item.bitrate || isAudioExt));

                const isVideoCategory = item._streamCategory === 'video';
                const isVideoExt = ['mp4', 'webm', 'mkv', 'avi', 'mov', 'flv'].includes(ext);
                const hasExplicitAudio = (item.hasAudio === true || item.has_audio === true);
                const hasExplicitVideo = (item.hasVideo === true || item.has_video === true);

                let streamType = 'video_audio';
                if (ext === 'm3u8' || ext === 'mpd' || String(item.label || '').toLowerCase().includes('hls')) {
                    streamType = 'hls';
                } else if (isAudioOnly) {
                    streamType = 'audio_only';
                } else if (hasExplicitAudio && (hasVcodec || hasExplicitVideo || isVideoCategory)) {
                    streamType = 'video_audio';
                } else if (hasVcodec && hasAcodec) {
                    streamType = 'video_audio';
                } else if (hasVcodec || isVideoCategory || hasExplicitVideo || isVideoExt) {
                    streamType = (item.hasAudio === false || !hasAcodec) ? 'video_only' : 'video_audio';
                }

                let numResolution = 0;
                if (item.quality) {
                    const qMatch = String(item.quality).match(/\d+/);
                    if (qMatch) numResolution = parseInt(qMatch[0], 10);
                }
                if (!numResolution && item.resolution) {
                    const rMatch = String(item.resolution).match(/(\d+)[pP]|\b(\d{3,4})x(\d{3,4})\b/);
                    if (rMatch) {
                        numResolution = rMatch[1] ? parseInt(rMatch[1], 10) : parseInt(rMatch[3], 10);
                    }
                }
                if (!numResolution && item.height) {
                    numResolution = parseInt(item.height, 10) || 0;
                }

                let numBitrate = 0;
                if (item.bitrate) {
                    const bMatch = String(item.bitrate).match(/\d+/);
                    if (bMatch) numBitrate = parseInt(bMatch[0], 10);
                }

                let qualityLabel = '';
                if (streamType === 'audio_only') {
                    qualityLabel = numBitrate > 0 ? (numBitrate + ' kbps') : (item.quality ? String(item.quality) : 'Audio');
                } else if (numResolution > 0) {
                    qualityLabel = numResolution + 'p';
                } else if (item.quality) {
                    qualityLabel = String(item.quality);
                } else if (item.resolution) {
                    qualityLabel = String(item.resolution);
                } else {
                    qualityLabel = ext ? ext.toUpperCase() : 'Video';
                }

                let codecLabel = '';
                if (streamType === 'video_audio') {
                    if (hasVcodec && hasAcodec) {
                        codecLabel = vcodec + ' / ' + acodec;
                    } else if (hasVcodec) {
                        codecLabel = vcodec + (hasExplicitAudio ? ' / Audio' : '');
                    } else if (hasAcodec) {
                        codecLabel = acodec;
                    }
                } else if (streamType === 'video_only') {
                    codecLabel = hasVcodec ? vcodec : '';
                } else if (streamType === 'audio_only') {
                    codecLabel = hasAcodec ? acodec : '';
                } else {
                    codecLabel = hasVcodec ? vcodec : (hasAcodec ? acodec : '');
                }

                let typeBadgeText = 'Video + Audio';
                let typeBadgeClass = 'fwt-media-badge-video-audio';
                let sortRank = 1;
                if (streamType === 'video_only') {
                    typeBadgeText = 'Video only';
                    typeBadgeClass = 'fwt-media-badge-video-only';
                    sortRank = 2;
                } else if (streamType === 'audio_only') {
                    typeBadgeText = 'Audio only';
                    typeBadgeClass = 'fwt-media-badge-audio-only';
                    sortRank = 3;
                } else if (streamType === 'hls') {
                    typeBadgeText = 'HLS / Stream';
                    typeBadgeClass = 'fwt-media-badge-hls';
                    sortRank = 4;
                }

                let filesizeLabel = '';
                const bytes = parseInt(item.filesize || item.file_size, 10);
                if (!isNaN(bytes) && bytes > 0) {
                    if (bytes >= 1024 * 1024) {
                        filesizeLabel = (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                    } else if (bytes >= 1024) {
                        filesizeLabel = (bytes / 1024).toFixed(1) + ' KB';
                    } else {
                        filesizeLabel = bytes + ' B';
                    }
                } else if (item.filesize_approx || item.filesizeApprox) {
                    filesizeLabel = '~approx';
                }

                const rawUrl = item.url || item.download_url || '';
                const hasSafeUrl = isSafeDownloadUrl(rawUrl);

                // Deduplication stable key (Section 8)
                const dedupKey = [
                    formatId,
                    ext,
                    qualityLabel,
                    codecLabel,
                    streamType,
                    numResolution,
                    numBitrate
                ].join('|').toLowerCase();

                if (seenKeys.has(dedupKey)) {
                    return;
                }
                seenKeys.add(dedupKey);

                processed.push({
                    formatId: formatId,
                    ext: ext,
                    qualityLabel: qualityLabel,
                    codecLabel: codecLabel,
                    streamType: streamType,
                    typeBadgeText: typeBadgeText,
                    typeBadgeClass: typeBadgeClass,
                    sortRank: sortRank,
                    numResolution: numResolution,
                    numBitrate: numBitrate,
                    fps: item.fps || '',
                    bytes: bytes || 0,
                    filesizeLabel: filesizeLabel,
                    label: item.label || '',
                    rawUrl: rawUrl,
                    hasSafeUrl: hasSafeUrl
                });
            });

            // User-friendly sorting (Section 9)
            processed.sort(function (a, b) {
                if (a.sortRank !== b.sortRank) {
                    return a.sortRank - b.sortRank;
                }
                if (a.sortRank === 1 || a.sortRank === 2) {
                    if (b.numResolution !== a.numResolution) {
                        return b.numResolution - a.numResolution;
                    }
                    const aFps = parseInt(a.fps, 10) || 0;
                    const bFps = parseInt(b.fps, 10) || 0;
                    if (bFps !== aFps) return bFps - aFps;
                    return (b.bytes || 0) - (a.bytes || 0);
                }
                if (a.sortRank === 3) {
                    if (b.numBitrate !== a.numBitrate) {
                        return b.numBitrate - a.numBitrate;
                    }
                    return (b.bytes || 0) - (a.bytes || 0);
                }
                return 0;
            });

            return processed;
        }

        function extractMediaFormats(payload) {
            if (!payload || typeof payload !== 'object') {
                return [];
            }

            if (Array.isArray(payload)) {
                if (payload.length > 0 && isFormatCandidate(payload[0])) {
                    return processAndSortFormats(payload);
                }
                for (let i = 0; i < payload.length; i++) {
                    const res = extractMediaFormats(payload[i]);
                    if (res && res.length > 0) return res;
                }
                return [];
            }

            const candidates = [
                payload,
                payload.result,
                payload.data,
                payload.value,
                payload.data && payload.data.result,
                payload.result && payload.result.data,
                payload.data && payload.data.value,
                payload.result && payload.result.value
            ];

            for (let i = 0; i < candidates.length; i++) {
                const c = candidates[i];
                if (!c || typeof c !== 'object' || Array.isArray(c)) continue;

                // Check for videos and/or audios arrays (actual production response structure)
                const hasVideos = Array.isArray(c.videos) && c.videos.length > 0;
                const hasAudios = Array.isArray(c.audios) && c.audios.length > 0;
                if (hasVideos || hasAudios) {
                    const combined = [];
                    if (hasVideos) {
                        c.videos.forEach(function (v) {
                            if (v && typeof v === 'object') {
                                combined.push(Object.assign({ _streamCategory: 'video' }, v));
                            }
                        });
                    }
                    if (hasAudios) {
                        c.audios.forEach(function (a) {
                            if (a && typeof a === 'object') {
                                combined.push(Object.assign({ _streamCategory: 'audio' }, a));
                            }
                        });
                    }
                    if (combined.length > 0) {
                        return processAndSortFormats(combined);
                    }
                }

                // Check for explicit formats, streams, media, or items array
                const listKeys = ['formats', 'streams', 'media', 'items'];
                for (let k = 0; k < listKeys.length; k++) {
                    const arr = c[listKeys[k]];
                    if (Array.isArray(arr) && arr.length > 0 && isFormatCandidate(arr[0])) {
                        return processAndSortFormats(arr);
                    }
                }
            }

            return [];
        }

        function renderMediaDownloadSection(formats, meta, rawJson) {
            let html = '<div class="fwt-media-results-wrap">';

            // Header Card
            if (meta.title || meta.thumbnail) {
                html += '<div class="fwt-media-header-card">';
                if (meta.thumbnail && isSafeDownloadUrl(meta.thumbnail)) {
                    html += '<img src="' + escapeHtml(meta.thumbnail) + '" alt="Thumbnail" class="fwt-media-thumb">';
                }
                html += '<div class="fwt-media-info">';
                if (meta.title) {
                    html += '<div class="fwt-media-title">' + escapeHtml(meta.title) + '</div>';
                }
                if (meta.duration) {
                    html += '<div class="fwt-media-duration">Duration: ' + escapeHtml(meta.duration) + '</div>';
                }
                html += '</div></div>';
            }

            html += '<h4 class="fwt-media-section-heading">Available Downloads (' + formats.length + ')</h4>';
            html += '<div class="fwt-media-cards-list">';

            formats.forEach(function (fmt) {
                html += '<div class="fwt-media-card">';
                html += '<div class="fwt-media-card-left">';
                html += '<span class="fwt-media-badge-type ' + escapeHtml(fmt.typeBadgeClass) + '">' + escapeHtml(fmt.typeBadgeText) + '</span>';
                html += '<span class="fwt-media-quality">' + escapeHtml(fmt.qualityLabel) + '</span>';
                html += '<span class="fwt-media-ext">' + escapeHtml((fmt.ext || 'FILE').toUpperCase()) + '</span>';
                if (fmt.codecLabel) {
                    html += '<span class="fwt-media-codec">' + escapeHtml(fmt.codecLabel) + '</span>';
                }
                if (fmt.filesizeLabel) {
                    html += '<span class="fwt-media-size">' + escapeHtml(fmt.filesizeLabel) + '</span>';
                }
                if (fmt.label && fmt.label !== fmt.qualityLabel && !fmt.label.includes(fmt.qualityLabel)) {
                    html += '<span class="fwt-media-label" title="' + escapeHtml(fmt.label) + '">' + escapeHtml(fmt.label) + '</span>';
                }
                html += '</div>';

                html += '<div class="fwt-media-card-right">';
                if (fmt.hasSafeUrl) {
                    html += '<a href="' + escapeHtml(fmt.rawUrl) + '" target="_blank" rel="noopener noreferrer" download class="fwt-media-btn-download">'
                         + '<span>⬇ Download</span></a>';
                } else {
                    html += '<span class="fwt-media-btn-disabled">Download unavailable</span>';
                }
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';

            if (rawJson) {
                html += '<details class="fwt-media-raw-details">'
                      + '<summary>View Raw JSON / Technical Details</summary>'
                      + '<pre class="fwt-result-code">' + escapeHtml(rawJson) + '</pre>'
                      + '</details>';
            }

            html += '</div>';
            return html;
        }

        function renderTemplateSafe(template, context) {
            if (!template || typeof template !== 'string') return '';
            if (!context || typeof context !== 'object') context = {};

            // Disallow raw HTML bypass tokens {{{ }}} or {{& }}
            template = template.replace(/\{\{\{\s*([a-zA-Z0-9_\-\.]+)\s*\}\}\}/g, '{{$1}}');
            template = template.replace(/\{\{&\s*([a-zA-Z0-9_\-\.]+)\s*\}\}/g, '{{$1}}');

            return renderBlock(template, context);

            function resolveVal(key, ctx) {
                if (key === '.' || key === 'this') return ctx.value !== undefined ? ctx.value : ctx;
                if (ctx && typeof ctx === 'object' && Object.prototype.hasOwnProperty.call(ctx, key)) {
                    return ctx[key];
                }
                if (key.indexOf('.') !== -1) {
                    const parts = key.split('.');
                    let cur = ctx;
                    for (let i = 0; i < parts.length; i++) {
                        if (cur && typeof cur === 'object' && Object.prototype.hasOwnProperty.call(cur, parts[i])) {
                            cur = cur[parts[i]];
                        } else {
                            return null;
                        }
                    }
                    return cur;
                }
                return null;
            }

            function isTruthy(val) {
                if (val === null || val === undefined) return false;
                if (typeof val === 'boolean') return val;
                if (Array.isArray(val)) return val.length > 0;
                if (typeof val === 'string') {
                    const s = val.trim();
                    return s !== '' && s !== '0' && s.toLowerCase() !== 'false';
                }
                if (typeof val === 'number') return val !== 0;
                return Boolean(val);
            }

            function renderBlock(tmpl, ctx) {
                // If blocks
                const ifPattern = /\{\{#if\s+([!a-zA-Z0-9_\-\.]+)\}\}((?:(?!\{\{#if).)*?)\{\{\/if\}\}/s;
                while (ifPattern.test(tmpl)) {
                    tmpl = tmpl.replace(ifPattern, function (_, expr, body) {
                        let negate = false;
                        if (expr.startsWith('!')) {
                            negate = true;
                            expr = expr.substring(1);
                        }
                        let val = resolveVal(expr, ctx);
                        let truthy = isTruthy(val);
                        if (negate) truthy = !truthy;
                        const parts = body.split('{{else}}');
                        return renderBlock(truthy ? parts[0] : (parts[1] || ''), ctx);
                    });
                }

                // Inverted blocks {{^key}}...{{/key}}
                const invPattern = /\{\{\^([a-zA-Z0-9_\-\.]+)\}\}((?:(?!\{\{\^).)*?)\{\{\/\1\}\}/s;
                while (invPattern.test(tmpl)) {
                    tmpl = tmpl.replace(invPattern, function (_, key, body) {
                        let val = resolveVal(key, ctx);
                        if (!isTruthy(val)) {
                            return renderBlock(body, ctx);
                        }
                        return '';
                    });
                }

                // Loops / sections {{#key}}...{{/key}}
                const secPattern = /\{\{#([a-zA-Z0-9_\-\.]+)\}\}((?:(?!\{\{#[a-zA-Z0-9_\-\.]+).)*?)\{\{\/\1\}\}/s;
                while (secPattern.test(tmpl)) {
                    tmpl = tmpl.replace(secPattern, function (_, key, body) {
                        let val = resolveVal(key, ctx);
                        if (Array.isArray(val)) {
                            if (val.length === 0) return '';
                            let out = '';
                            for (let i = 0; i < val.length; i++) {
                                const item = val[i];
                                const itemCtx = (typeof item === 'object' && item !== null) ? Object.assign({}, ctx, item) : Object.assign({}, ctx, { value: item, '.': item });
                                itemCtx['@index'] = i;
                                itemCtx['@number'] = i + 1;
                                itemCtx['@first'] = (i === 0);
                                itemCtx['@last'] = (i === val.length - 1);
                                out += renderBlock(body, itemCtx);
                            }
                            return out;
                        }
                        if (isTruthy(val)) {
                            const sub = (typeof val === 'object' && val !== null) ? Object.assign({}, ctx, val) : ctx;
                            return renderBlock(body, sub);
                        }
                        return '';
                    });
                }

                // Variables {{key}}
                tmpl = tmpl.replace(/\{\{([@a-zA-Z0-9_\-\.]+)\}\}/g, function (_, key) {
                    let val = resolveVal(key, ctx);
                    if (val === null || val === undefined || typeof val === 'object') return '';

                    const kLower = key.toLowerCase();
                    const isUrl = kLower === 'download_url' || kLower === 'url' || kLower === 'thumbnail' || kLower.endsWith('_url') || kLower.endsWith('.download_url') || kLower.endsWith('.url');
                    if (isUrl) {
                        const u = String(val).trim();
                        if (isSafeDownloadUrl(u)) {
                            return escapeHtml(u);
                        }
                        return '';
                    }

                    return escapeHtml(String(val));
                });

                return tmpl;
            }
        }

        function displayResult(result, fullResponse) {
            if (!result) return;

            const responseObj = fullResponse || {};
            resultContainer.style.display = 'block';
            resultBadge.textContent = result.type || 'TEXT';
            textBox.style.display = 'none';
            previewBox.style.display = 'none';
            downloadBox.style.display = 'none';

            const designOutput = document.getElementById('fwt-design-output');
            const dynamicCss = document.getElementById('fwt-design-dynamic-css');
            const existingFormatsContainer = document.getElementById('fwt-media-formats-container');
            if (existingFormatsContainer) {
                existingFormatsContainer.style.display = 'none';
                existingFormatsContainer.innerHTML = '';
            }
            if (designOutput) {
                designOutput.style.display = 'none';
                designOutput.innerHTML = '';
            }

            // 1. Universal Frontend Design Pipeline (from server response)
            if (responseObj.design && (responseObj.rendered_html || responseObj.design.template_html)) {
                const design = responseObj.design;
                const designSlug = design.slug || 'custom';
                resultBadge.textContent = design.name || 'DESIGN';

                if (dynamicCss && design.css) {
                    dynamicCss.textContent = design.css;
                }

                let finalHtml = responseObj.rendered_html;
                if (!finalHtml && design.template_html && responseObj.normalized_data) {
                    finalHtml = renderTemplateSafe(design.template_html, responseObj.normalized_data);
                }

                if (designOutput && finalHtml) {
                    designOutput.setAttribute('data-design', designSlug);
                    designOutput.innerHTML = finalHtml;
                    designOutput.style.display = 'block';

                    // Optional Script execution (if explicitly enabled in design)
                    if (design.js_enabled && design.js) {
                        try {
                            const runner = new Function(design.js);
                            runner.call(designOutput);
                        } catch (jsErr) {
                            console.warn('Design script execution error:', jsErr);
                        }
                    }

                    const rData = (result.data !== undefined) ? result.data : ((result.value !== undefined) ? result.value : result);
                    lastResultRawText = typeof rData === 'object' ? JSON.stringify(rData, null, 2) : String(rData ?? '');

                    resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    return;
                }
            }

            const rType = (result.type || 'TEXT').toUpperCase();
            const rData = (result.data !== undefined) ? result.data : ((result.value !== undefined) ? result.value : result);

            // 2. Client-side Media Extraction & Card Rendering fallback if payload is media streams
            const mediaFormats = extractMediaFormats(result);
            if (mediaFormats.length > 0 && (rType === 'JSON' || rType === 'TEXT')) {
                const mediaMeta = extractMediaMeta(result);
                let rawJsonStr = '';
                if (typeof rData === 'object' && rData !== null) {
                    try {
                        rawJsonStr = JSON.stringify(rData, null, 2);
                    } catch (e) {
                        rawJsonStr = String(rData);
                    }
                } else {
                    rawJsonStr = String(rData ?? '');
                }

                lastResultRawText = rawJsonStr;
                resultBadge.textContent = 'MEDIA';

                let targetNode = designOutput || existingFormatsContainer;
                if (!targetNode) {
                    targetNode = document.createElement('div');
                    targetNode.id = 'fwt-media-formats-container';
                    textBox.parentNode.insertBefore(targetNode, textBox);
                }

                targetNode.innerHTML = renderMediaDownloadSection(mediaFormats, mediaMeta, rawJsonStr);
                targetNode.style.display = 'block';

                resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }

            // 3. Standard Non-Design Output (Text, JSON, HTML, CSS, File Download)
            if (rType === 'TEXT' || rType === 'JSON') {
                textBox.style.display = 'block';
                let displayVal = '';
                if (typeof rData === 'object' && rData !== null) {
                    displayVal = JSON.stringify(rData, null, 2);
                } else {
                    displayVal = String(rData ?? '');
                }
                resultCode.textContent = displayVal;
                lastResultRawText = displayVal;
            } else if (rType === 'HTML') {
                textBox.style.display = 'block';
                previewBox.style.display = 'block';
                const htmlStr = String(rData ?? '');
                resultCode.textContent = htmlStr;
                lastResultRawText = htmlStr;
                if (previewFrame) {
                    previewFrame.srcdoc = htmlStr;
                }
            } else if (rType === 'CSS') {
                textBox.style.display = 'block';
                previewBox.style.display = 'block';
                const cssStr = String(rData ?? '');
                resultCode.textContent = cssStr;
                lastResultRawText = cssStr;
                if (previewFrame) {
                    const sampleHtml = '<!DOCTYPE html><html><head><style>' + cssStr + '</style></head>'
                        + '<body style="font-family:sans-serif;padding:20px;">'
                        + '<h2>CSS Preview Box</h2><p>This content is styled by your output in an isolated sandbox.</p>'
                        + '<button style="padding:8px 16px;border-radius:4px;">Sample Button</button>'
                        + '</body></html>';
                    previewFrame.srcdoc = sampleHtml;
                }
            } else if (rType === 'DOWNLOAD' || rType === 'FILE') {
                downloadBox.style.display = 'block';
                if (typeof rData === 'object' && rData !== null) {
                    downloadFilename.textContent = rData.filename || 'download.bin';
                    downloadFilesize.textContent = rData.file_size ? (Math.round(rData.file_size / 1024) + ' KB') : '';
                    const downloadUrl = rData.download_url || ('/api/tools/download/' + encodeURIComponent(rData.reference || ''));
                    downloadLink.href = downloadUrl;
                    downloadLink.setAttribute('download', rData.filename || 'download.bin');
                } else {
                    downloadFilename.textContent = 'file';
                    downloadLink.href = String(rData);
                }
                lastResultRawText = downloadLink.href;
            } else {
                textBox.style.display = 'block';
                resultCode.textContent = String(rData ?? '');
                lastResultRawText = String(rData ?? '');
            }

            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = String(str || '');
            return div.innerHTML;
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }
})();