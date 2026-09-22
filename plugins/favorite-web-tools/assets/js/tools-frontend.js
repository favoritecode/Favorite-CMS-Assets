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
            const fileInputs = form.querySelectorAll('input[type="file"]');
            let hasFiles = false;
            fileInputs.forEach(input => {
                if (input.files && input.files.length > 0) hasFiles = true;
            });
            setLoading(true);
            hideError();
            try {
                let response;
                const endpoint = '/api/tools/' + encodeURIComponent(slug) + '/execute';
                if (hasFiles) {
                    const formData = new FormData(form);
                    response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                } else {
                    const formData = new FormData(form);
                    const inputs = {};
                    formData.forEach((value, key) => {
                        if (key !== '_token') {
                            inputs[key] = value;
                        }
                    });
                    response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': formData.get('_token') || ''
                        },
                        body: JSON.stringify({ inputs: inputs })
                    });
                }
                const data = await response.json();
                if (!response.ok || !data.success) {
                    const errMsg = (data.error && data.error.message) ? data.error.message : 'Execution failed. Please check your input and try again.';
                    showError(errMsg);
                    resultContainer.style.display = 'none';
                } else {
                    displayResult(data.result);
                }
            } catch (err) {
                showError('Network error or server unreachable. Please try again.');
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
        function displayResult(result) {
            if (!result) return;
            resultContainer.style.display = 'block';
            resultBadge.textContent = result.type || 'TEXT';
            textBox.style.display = 'none';
            previewBox.style.display = 'none';
            downloadBox.style.display = 'none';
            const rType = (result.type || 'TEXT').toUpperCase();
            const rData = result.data;
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
    }
})();