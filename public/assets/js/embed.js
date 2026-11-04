(function() {
    'use strict';

    // Find all embed containers on the host page
    function initEmbeds() {
        const containers = document.querySelectorAll('[data-qfunnel-embed]');
        containers.forEach(container => {
            if (container.getAttribute('data-qfunnel-loaded') === 'true') return;

            const slug = container.getAttribute('data-qfunnel-embed');
            const host = container.getAttribute('data-qfunnel-host') || window.location.origin;
            const mode = container.getAttribute('data-qfunnel-mode') || 'inline'; // inline, popup, or floating
            const height = container.getAttribute('data-qfunnel-height') || '720px';

            const quizUrl = `${host.replace(/\/$/, '')}/quiz/${encodeURIComponent(slug)}?embedded=1`;

            if (mode === 'inline') {
                createInlineEmbed(container, quizUrl, height);
            } else if (mode === 'popup') {
                createPopupEmbed(container, quizUrl);
            } else if (mode === 'floating') {
                createFloatingEmbed(container, quizUrl);
            }

            container.setAttribute('data-qfunnel-loaded', 'true');
        });
    }

    function createInlineEmbed(container, quizUrl, height) {
        const iframe = document.createElement('iframe');
        iframe.src = quizUrl;
        iframe.style.width = '100%';
        iframe.style.height = height;
        iframe.style.border = 'none';
        iframe.style.borderRadius = '16px';
        iframe.style.overflow = 'hidden';
        iframe.setAttribute('allow', 'camera; microphone; autoplay; encrypted-media;');
        iframe.setAttribute('loading', 'lazy');
        iframe.title = 'Interactive Quiz Funnel';

        container.appendChild(iframe);
    }

    function createPopupEmbed(triggerEl, quizUrl) {
        // Build modal backdrop and iframe
        const modalId = 'qfunnel-modal-' + Math.random().toString(36).substring(2, 9);
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);z-index:999999;display:none;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;';

        const card = document.createElement('div');
        card.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);width:100%;max-width:min(680px, calc(100vw - 32px));height:90vh;max-height:850px;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);z-index:1000000;display:flex;flex-direction:column;box-sizing:border-box;margin:0;';

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '✕';
        closeBtn.style.cssText = 'position:absolute;top:14px;right:16px;background:#f1f5f9;border:none;border-radius:50%;width:32px;height:32px;font-size:14px;font-weight:bold;cursor:pointer;z-index:10;color:#0f172a;line-height:32px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.1);';
        closeBtn.onclick = function() { modal.style.display = 'none'; };

        // Close on clicking backdrop
        modal.onclick = function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        };

        const iframe = document.createElement('iframe');
        iframe.src = quizUrl;
        iframe.style.cssText = 'width:100%;height:100%;border:none;flex:1;';

        card.appendChild(closeBtn);
        card.appendChild(iframe);
        modal.appendChild(card);
        document.body.appendChild(modal);

        triggerEl.style.cursor = 'pointer';
        triggerEl.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        });
    }

    function createFloatingEmbed(triggerEl, quizUrl) {
        const floatBtn = document.createElement('div');
        floatBtn.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999998;cursor:pointer;';
        floatBtn.innerHTML = `
            <button style="display:flex;align-items:center;gap:8px;padding:14px 20px;border-radius:50px;background:#5B5BD6;color:#fff;border:none;font-family:sans-serif;font-size:14px;font-weight:bold;box-shadow:0 10px 25px rgba(91,91,214,0.4);cursor:pointer;transition:transform 0.2s;">
                <span>⚡</span>
                <span>Take the Quiz</span>
            </button>
        `;

        document.body.appendChild(floatBtn);
        createPopupEmbed(floatBtn, quizUrl);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmbeds);
    } else {
        initEmbeds();
    }

    // Expose global API
    window.QFunnel = {
        init: initEmbeds,
        openPopup: function(slug, host) {
            const baseUrl = (host || window.location.origin).replace(/\/$/, '');
            const quizUrl = `${baseUrl}/quiz/${encodeURIComponent(slug)}?embedded=1`;
            const dummy = document.createElement('div');
            createPopupEmbed(dummy, quizUrl);
            dummy.click();
        }
    };
})();
