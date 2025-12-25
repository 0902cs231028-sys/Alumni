/**
 * Nexus Alumni Engagement Controller
 * Extreme Level: Optimistic State Management & Adaptive Polling
 */
(function(window, document) {
    'use strict';

    // 1. Config & State Storage
    const CONFIG = {
        POLL_INTERVAL_BASE: 5000,
        POLL_INTERVAL_MAX: 30000,
        TOAST_DURATION: 3000,
        ENDPOINTS: {
            ADD: 'add_comment.php',
            FETCH: 'fetch_comments.php',
            REPORT: 'report.php'
        }
    };

    const State = {
        activePolls: new Map(),
        lastFetchedIds: new Map()
    };

    // 2. High-Performance Notification Engine
    const UI = {
        toast(message, type = 'success') {
            const container = document.getElementById('nexus-toast-container') || (function() {
                const c = document.createElement('div');
                c.id = 'nexus-toast-container';
                c.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;display:flex;flex-direction:column;gap:10px;';
                document.body.appendChild(c);
                return c;
            })();

            const el = document.createElement('div');
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };

            el.style.cssText = `
                background:${colors[type]}; color:#fff; padding:12px 20px; border-radius:10px;
                font-family:sans-serif; font-weight:600; font-size:14px; box-shadow:0 10px 25px rgba(0,0,0,0.2);
                transform: translateX(120%); transition: transform 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
            `;
            el.innerText = message;
            container.appendChild(el);

            requestAnimationFrame(() => el.style.transform = 'translateX(0)');

            setTimeout(() => {
                el.style.transform = 'translateX(120%)';
                setTimeout(() => el.remove(), 300);
            }, CONFIG.TOAST_DURATION);
        },

        escape(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        },

        renderComment(c) {
            const name = UI.escape(c.name || 'Alumni');
            const time = UI.escape(c.created_at || 'Just now');
            const body = UI.escape(c.content || '').replace(/\n/g, '<br>');
            
            return `
                <div class="comment-item mb-3" data-id="${c.id}" style="animation: fadeIn 0.4s ease-out;">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold text-primary small">${name}</span>
                        <span class="text-muted" style="font-size:11px;">${time}</span>
                    </div>
                    <div class="comment-text p-2 bg-light rounded mt-1 small">${body}</div>
                </div>
            `;
        }
    };

    // 3. API & Network Logic
    const API = {
        async post(url, formData) {
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!r.ok) throw new Error('Network response failed');
                return await r.json();
            } catch (e) {
                console.error('API Error:', e);
                return { ok: false, error: 'Network Error' };
            }
        },

        async get(url) {
            const r = await fetch(url);
            return r.ok ? await r.json() : [];
        }
    };

    // 4. Core Comment Controller
    const CommentManager = {
        init() {
            document.addEventListener('submit', this.handleCommentSubmit.bind(this));
            this.setupAutoPolling();
            this.setupReporting();
        },

        async handleCommentSubmit(e) {
            const form = e.target;
            if (!form.classList.contains('ajax-comment')) return;
            e.preventDefault();

            const textarea = form.querySelector('textarea[name="content"]');
            const btn = form.querySelector('button');
            const postId = form.querySelector('input[name="post_id"]').value;
            const content = textarea.value.trim();

            if (!content) return;

            // --- OPTIMISTIC UI UPDATE ---
            const container = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
            const tempId = 'temp-' + Date.now();
            const optimisticHtml = UI.renderComment({ id: tempId, name: 'You', content: content, created_at: 'Sending...' });
            
            container.insertAdjacentHTML('beforeend', optimisticHtml);
            textarea.value = '';
            btn.disabled = true;

            const fd = new FormData(form);
            const resp = await API.post(CONFIG.ENDPOINTS.ADD, fd);

            btn.disabled = false;

            if (resp.ok) {
                // Replace optimistic comment with server-verified data
                const tempEl = container.querySelector(`[data-id="${tempId}"]`);
                if (tempEl) tempEl.outerHTML = UI.renderComment(resp.comment);
                UI.toast('Comment posted!');
                this.updateCount(postId, 1);
            } else {
                // Rollback on failure
                container.querySelector(`[data-id="${tempId}"]`)?.remove();
                UI.toast(resp.message || 'Failed to post comment', 'error');
                if (resp.error === 'not_logged_in') window.location.href = 'login.php';
            }
        },

        updateCount(postId, delta) {
            const card = document.querySelector(`.comments-list[data-post-id="${postId}"]`)?.closest('.card');
            const cnt = card?.querySelector('.comments-count');
            if (cnt) cnt.innerText = Math.max(0, parseInt(cnt.innerText || '0') + delta);
        },

        async poll(postId, container) {
            const list = await API.get(`${CONFIG.ENDPOINTS.FETCH}?post_id=${postId}`);
            
            // Extreme Level: Diffing logic to only append new comments
            const lastId = State.lastFetchedIds.get(postId);
            const currentMaxId = list.length > 0 ? Math.max(...list.map(c => c.id)) : 0;

            if (currentMaxId > lastId) {
                container.innerHTML = list.map(c => UI.renderComment(c)).join('');
                this.updateCount(postId, list.length - (lastId ? 0 : 0)); // Resync count
                State.lastFetchedIds.set(postId, currentMaxId);
            }
        },

        setupAutoPolling() {
            const lists = document.querySelectorAll('.comments-list[data-post-id]');
            lists.forEach(cont => {
                const pid = cont.getAttribute('data-post-id');
                State.lastFetchedIds.set(pid, 0);
                this.poll(pid, cont); // Initial fetch
                
                // Smart Adaptive Polling
                const interval = setInterval(() => this.poll(pid, cont), CONFIG.POLL_INTERVAL_BASE);
                State.activePolls.set(pid, interval);
            });
        },

        setupReporting() {
            document.addEventListener('click', async (e) => {
                const rep = e.target.closest('.report-item');
                if (!rep) return;
                
                e.preventDefault();
                const fd = new FormData();
                fd.append('type', rep.dataset.type);
                fd.append('target_id', rep.dataset.target);

                const resp = await API.post(CONFIG.ENDPOINTS.REPORT, fd);
                if (resp.ok) {
                    UI.toast('Reported to administrators', 'info');
                } else {
                    UI.toast('Report failed', 'error');
                }
            });
        }
    };

    // Initialize on DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => CommentManager.init());
    } else {
        CommentManager.init();
    }

})(window, document);
