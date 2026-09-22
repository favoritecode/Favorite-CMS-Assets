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
                displayResult(resultPayload);
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

        function displayResult(result) {
            if (!result) return;

            resultContainer.style.display = 'block';
            resultBadge.textContent = result.type || 'TEXT';
            textBox.style.display = 'none';
            previewBox.style.display = 'none';
            downloadBox.style.display = 'none';

            const rType = (result.type || 'TEXT').toUpperCase();
            const rData = (result.data !== undefined) ? result.data : ((result.value !== undefined) ? result.value : result);

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

                // Check for media formats stream array and render interactive cards
                const existingFormatsContainer = document.getElementById('fwt-media-formats-container');
                const formatsList = Array.isArray(rData) ? rData : (rData && Array.isArray(rData.formats) ? rData.formats : null);
                const mediaTitle = (rData && rData.title) ? String(rData.title) : '';
                const mediaThumb = (rData && rData.thumbnail && isSafeDownloadUrl(rData.thumbnail)) ? String(rData.thumbnail) : '';

                if (formatsList && formatsList.length > 0 && formatsList[0] && (formatsList[0].url || formatsList[0].formatId)) {
                    let formatsHtml = '<div style="margin-bottom:20px;display:flex;flex-direction:column;gap:12px;">';

                    if (mediaTitle || mediaThumb) {
                        formatsHtml += '<div style="display:flex;gap:14px;align-items:center;padding:12px 14px;background:#f1f5f9;border-radius:8px;border:1px solid #cbd5e1;">';
                        if (mediaThumb) {
                            formatsHtml += '<img src="' + escapeHtml(mediaThumb) + '" alt="Thumbnail" style="width:72px;height:48px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
                        }
                        if (mediaTitle) {
                            formatsHtml += '<div style="font-size:14px;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(mediaTitle) + '</div>';
                        }
                        formatsHtml += '</div>';
                    }

                    formatsHtml += '<div style="display:flex;flex-direction:column;gap:8px;">';
                    formatsList.forEach(function (fmt) {
                        const isAudio = !fmt.vcodec || fmt.vcodec === 'none' || (fmt.hasAudio && !fmt.fps);
                        const ext = (fmt.ext || (isAudio ? 'mp3' : 'mp4')).toUpperCase();
                        const qualityStr = fmt.quality ? (fmt.quality + 'p') : (fmt.resolution || '');
                        const codecStr = fmt.vcodec && fmt.vcodec !== 'none' ? fmt.vcodec : (fmt.acodec && fmt.acodec !== 'none' ? fmt.acodec : '');
                        const lbl = fmt.label || (qualityStr ? (qualityStr + ' - ' + ext) : ext);
                        const sz = fmt.filesize ? (Math.round(fmt.filesize / (1024 * 1024) * 10) / 10 + ' MB') : (fmt.filesizeApprox ? '~approx' : '');
                        const badgeBg = isAudio ? '#059669' : '#2563eb';
                        const typeIcon = isAudio ? '🎵' : '🎬';

                        formatsHtml += '<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;gap:12px;flex-wrap:wrap;">'
                            + '<div style="display:flex;align-items:center;gap:10px;overflow:hidden;flex-wrap:wrap;">'
                            + '<span style="background:' + badgeBg + ';color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:4px;flex-shrink:0;">' + typeIcon + ' ' + ext + '</span>'
                            + '<span style="font-size:13px;font-weight:600;color:#1e293b;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;">' + escapeHtml(lbl) + '</span>'
                            + (codecStr ? '<span style="font-size:11px;color:#64748b;background:#e2e8f0;padding:1px 6px;border-radius:3px;">' + escapeHtml(codecStr) + '</span>' : '')
                            + (sz ? '<span style="color:#64748b;font-size:12px;flex-shrink:0;">(' + sz + ')</span>' : '')
                            + '</div>';

                        if (fmt.url && isSafeDownloadUrl(fmt.url)) {
                            formatsHtml += '<a href="' + escapeHtml(fmt.url) + '" target="_blank" rel="noopener noreferrer" style="padding:6px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;font-size:12px;font-weight:600;flex-shrink:0;">Download</a>';
                        } else if (fmt.job_id) {
                            const jobUrl = '/api/tools/' + encodeURIComponent(slug) + '/job/' + encodeURIComponent(fmt.job_id);
                            formatsHtml += '<a href="' + escapeHtml(jobUrl) + '" target="_blank" rel="noopener noreferrer" style="padding:6px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:5px;font-size:12px;font-weight:600;flex-shrink:0;">Download</a>';
                        }
                        formatsHtml += '</div>';
                    });
                    formatsHtml += '</div></div>';

                    let targetNode = existingFormatsContainer;
                    if (!targetNode) {
                        targetNode = document.createElement('div');
                        targetNode.id = 'fwt-media-formats-container';
                        textBox.parentNode.insertBefore(targetNode, textBox);
                    }
                    targetNode.innerHTML = formatsHtml;
                    targetNode.style.display = 'block';
                } else if (existingFormatsContainer) {
                    existingFormatsContainer.style.display = 'none';
                }
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
        }
    }
})();