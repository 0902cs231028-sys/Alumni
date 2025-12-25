/**
 * Nexus Core Orchestrator
 * Supreme Extreme Level: Atomic Themes & Kinetic Interactions
 */
(function() {
    'use strict';

    const CONFIG = {
        THEME_KEY: 'alumni_theme',
        RIPPLE_DURATION: 600,
        TRANSITION_MS: 300
    };

    const DOM = {
        root: document.documentElement,
        body: document.body
    };

    /**
     * 1. ATOMIC THEME ENGINE
     * Manages system-level visual states with zero-flicker logic.
     */
    const ThemeEngine = {
        init() {
            this.setupListeners();
            this.syncWithStorage();
        },

        get current() {
            return DOM.root.classList.contains('dark-theme') ? 'dark' : 'light';
        },

        apply(mode, persist = false) {
            if (mode !== 'dark' && mode !== 'light') return;

            // Trigger atomic class swap
            DOM.root.classList.toggle('dark-theme', mode === 'dark');
            DOM.root.style.setProperty('--current-theme', mode);
            
            this.updateUI(mode);

            if (persist) {
                try {
                    localStorage.setItem(CONFIG.THEME_KEY, mode);
                } catch (e) { console.warn('Storage blocked'); }
            }
        },

        updateUI(mode) {
            const toggles = document.querySelectorAll('#themeToggle, [data-theme-toggle="true"]');
            const isDark = mode === 'dark';
            
            toggles.forEach(btn => {
                if (!btn) return;
                const label = isDark ? 'Light' : 'Dark';
                btn.innerHTML = `<span class="theme-icon">${isDark ? '☀️' : '🌙'}</span> ${label}`;
                btn.setAttribute('aria-label', `Switch to ${label.toLowerCase()} mode`);
                btn.className = `btn btn-sm ${isDark ? 'btn-outline-light' : 'btn-outline-primary'} transition-all`;
            });
        },

        setupListeners() {
            // Global click delegation for performance
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('#themeToggle, [data-theme-toggle="true"]');
                if (btn) this.apply(this.current === 'dark' ? 'light' : 'dark', true);
            });

            // Reactive OS change detection
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(CONFIG.THEME_KEY)) {
                    this.apply(e.matches ? 'dark' : 'light');
                }
            });
        },

        syncWithStorage() {
            let mode = localStorage.getItem(CONFIG.THEME_KEY);
            if (!mode) {
                mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            this.apply(mode);
        }
    };

    /**
     * 2. KINETIC INTERACTION ENGINE
     * High-performance ripple and tactile feedback.
     */
    const InteractionEngine = {
        init() {
            document.addEventListener('mousedown', this.createRipple.bind(this), { passive: true });
        },

        createRipple(e) {
            const target = e.target.closest('.btn, [data-ripple="true"]');
            if (!target || target.dataset.ripple === 'false') return;

            const rect = target.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                top: ${y}px;
                left: ${x}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                pointer-events: none;
                transform: scale(0);
                opacity: 1;
                transition: transform ${CONFIG.RIPPLE_DURATION}ms cubic-bezier(0.4, 0, 0.2, 1), 
                            opacity ${CONFIG.RIPPLE_DURATION}ms ease-out;
            `;

            // Fix parent positioning for ripple containment
            if (getComputedStyle(target).position === 'static') {
                target.style.position = 'relative';
            }
            target.style.overflow = 'hidden';

            target.appendChild(ripple);

            // Trigger HW-accelerated animation via microtask
            requestAnimationFrame(() => {
                ripple.style.transform = 'scale(2.5)';
                ripple.style.opacity = '0';
            });

            setTimeout(() => ripple.remove(), CONFIG.RIPPLE_DURATION);
        }
    };

    // 3. EXECUTION
    ThemeEngine.init();
    InteractionEngine.init();

    // Export for external module usage
    window.NexusCore = { ThemeEngine, InteractionEngine };
})();
