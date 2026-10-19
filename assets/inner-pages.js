/* Designed inner pages: staggered scroll reveals, counting stats and the contact form. */
(function () {
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function reveal() {
        const pages = document.querySelectorAll('.bh-page');
        if (!pages.length || motion.matches || !('IntersectionObserver' in window)) return;
        const selector = [
            '.section-head', '.bh-split-media', '.bh-split-copy', '.story-panel', '.bh-fcard', '.bh-stat', '.bh-logo-grid > *',
            '.bh-company', '.bh-squadron', '.bh-month', '.bh-video-copy', '.bh-video-frame', '.bh-gallery > *',
            '.bh-info-item', '.bh-contact-form', '.bh-cta-inner', '.bh-prose-inner', '.bh-media-pair > *', '.faq-list',
            '.bh-network', '.spec-heading', '.bh-stage-table', '.green-image', '.wall-specs', '.bh-embed', '.bh-faq > div:first-child'
        ].join(',');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
                count(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        pages.forEach(page => {
            page.querySelectorAll(selector).forEach(element => {
                if (element.closest('.page-hero, .bh-intro, .stage-hero')) return;
                const siblings = Array.from(element.parentElement.children).filter(child => child.matches(selector));
                const index = Math.max(0, siblings.indexOf(element));
                element.style.setProperty('--bh-delay', Math.min(index, 6) * 0.08 + 's');
                element.classList.add('bh-reveal');
                observer.observe(element);
            });
        });
        motion.addEventListener('change', event => {
            if (!event.matches) return;
            observer.disconnect();
            document.querySelectorAll('.bh-page .bh-reveal').forEach(element => element.classList.add('is-visible'));
        });
    }

    /* Count stats such as "32,000" or "500+" up from zero once, keeping their original formatting. */
    function count(container) {
        container.querySelectorAll('.bh-stat-value').forEach(element => {
            const original = element.textContent;
            const match = original.match(/^([^\d]*)([\d,]+)(.*)$/);
            if (!match || element.dataset.counted) return;
            element.dataset.counted = '1';
            const target = parseInt(match[2].replace(/,/g, ''), 10);
            const started = performance.now();
            const duration = 1400;
            function frame(now) {
                const progress = Math.min(1, (now - started) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = match[1] + Math.round(target * eased).toLocaleString('en-GB') + match[3];
                if (progress < 1) requestAnimationFrame(frame); else element.textContent = original;
            }
            requestAnimationFrame(frame);
        });
    }

    /* Contact form: record time on page (spam check) and submit without leaving the page when possible. */
    function forms() {
        document.querySelectorAll('[data-bh-form]').forEach(form => {
            const started = Date.now();
            const elapsed = form.querySelector('[data-elapsed]');
            const status = form.querySelector('.form-status');
            form.addEventListener('submit', event => {
                if (elapsed) elapsed.value = String(Date.now() - started);
                if (!window.fetch || !window.FormData) return;
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                form.querySelectorAll('.field-error').forEach(error => { error.textContent = ''; });
                form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
                button.disabled = true;
                fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                    .then(response => response.json())
                    .then(result => {
                        status.innerHTML = '';
                        const message = document.createElement('p');
                        message.className = result.ok ? 'form-success' : 'form-error';
                        message.textContent = result.message;
                        status.append(message);
                        if (result.ok) {
                            form.reset();
                            return;
                        }
                        Object.entries(result.errors || {}).forEach(([key, text]) => {
                            const field = form.querySelector('[name="bh_' + key + '"]');
                            if (!field) return;
                            field.setAttribute('aria-invalid', 'true');
                            const error = field.parentElement.querySelector('.field-error');
                            if (error) error.textContent = text;
                        });
                    })
                    .catch(() => {
                        status.innerHTML = '<p class="form-error">Your message could not be sent. Please email sales@blackhangarstudios.com.</p>';
                    })
                    .finally(() => { button.disabled = false; });
            });
        });
    }

    /* Poster rows: keep moving on their own, and visitors can swipe, drag or scroll them (no pause button). */
    function marquees() {
        document.querySelectorAll('.bh-row, .bh-latest-row').forEach(row => {
            const track = row.querySelector('.bh-track, .bh-latest-track');
            if (!track || row.dataset.bhScroll) return;
            row.dataset.bhScroll = '1';
            const style = getComputedStyle(track);
            const duration = parseFloat(style.animationDuration) || 60;
            const direction = (row.classList.contains('bh-reverse') || track.classList.contains('bh-latest-reverse') || style.animationDirection === 'reverse') ? -1 : 1;
            row.classList.add('bh-js-scroll');
            let half = track.scrollWidth / 2;
            let position = direction < 0 ? half : 0;
            let resumeAt = 0, dragging = false, moved = false, startX = 0, startPosition = 0, last = performance.now();
            row.scrollLeft = position;
            const hold = ms => { resumeAt = performance.now() + ms; };
            const wrap = value => { if (half <= 0) return value; while (value >= half) value -= half; while (value < 0) value += half; return value; };
            window.addEventListener('resize', () => { half = track.scrollWidth / 2; });
            row.addEventListener('scroll', () => { if (!dragging && Math.abs(row.scrollLeft - position) > 3) { position = row.scrollLeft; hold(2500); } }, { passive: true });
            row.addEventListener('touchstart', () => hold(3000), { passive: true });
            row.addEventListener('wheel', () => hold(2500), { passive: true });
            row.addEventListener('pointerdown', event => {
                if (event.pointerType !== 'mouse' || event.button !== 0) return;
                dragging = true; moved = false; startX = event.clientX; startPosition = row.scrollLeft;
            });
            window.addEventListener('pointermove', event => {
                if (!dragging) return;
                const delta = event.clientX - startX;
                if (Math.abs(delta) > 5) { moved = true; row.classList.add('is-dragging'); }
                position = wrap(startPosition - delta); row.scrollLeft = position; hold(2500);
            });
            window.addEventListener('pointerup', () => { if (!dragging) return; dragging = false; setTimeout(() => row.classList.remove('is-dragging'), 0); hold(2000); });
            row.addEventListener('click', event => { if (moved) { event.preventDefault(); event.stopPropagation(); moved = false; } }, true);
            row.addEventListener('focusin', () => hold(6000));
            function frame(now) {
                const delta = Math.min(64, now - last) / 1000;
                last = now;
                if (!dragging && now >= resumeAt && !motion.matches && half > 0) {
                    position = wrap(position + direction * (half / duration) * delta);
                    row.scrollLeft = position;
                } else if (!dragging) {
                    position = wrap(row.scrollLeft);
                    if (Math.abs(position - row.scrollLeft) > 1) row.scrollLeft = position;
                }
                requestAnimationFrame(frame);
            }
            requestAnimationFrame(frame);
        });
    }

    function init() {
        reveal();
        forms();
        marquees();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
