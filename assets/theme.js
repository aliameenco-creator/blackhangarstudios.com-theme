document.addEventListener('click', function (event) {
    const pause = event.target.closest('.bh-pause');
    if (pause) {
        const section = pause.closest('.bh-marquee');
        const stopped = section.classList.toggle('is-paused');
        pause.setAttribute('aria-pressed', String(stopped));
        pause.textContent = stopped ? 'Resume moving posters' : 'Pause moving posters';
    }
    const play = event.target.closest('.bh-video button');
    if (play) {
        const wrapper = play.closest('.bh-video');
        const id = wrapper.dataset.video;
        if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
        const iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1';
        iframe.title = 'Production trailer';
        iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        wrapper.replaceChildren(iframe);
    }
});

function bhRevealPageSections() {
    const root = document.querySelector('main.bh-built');
    if (!root || !('IntersectionObserver' in window)) return;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motion.matches) return;
    const targets = root.querySelectorAll('.studio-image, .studio-copy, .story-panel, .blue-band, .productions .section-head, .bh-home-posters .bh-card, .partners, .faq > div, .contact, .spec-heading, .green-section .section-head, .green-image, .wall-specs');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });
    targets.forEach(target => {
        target.classList.add('scroll-reveal');
        observer.observe(target);
    });
    motion.addEventListener('change', event => {
        if (event.matches) {
            observer.disconnect();
            targets.forEach(target => target.classList.add('is-visible'));
        }
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bhRevealPageSections);
} else {
    bhRevealPageSections();
}

function bhInitTestimonials() {
    document.querySelectorAll('.wp-site-blocks .bh-client-testimonials').forEach(section => {
        if (section.dataset.carouselReady) return;
        const slides = Array.from(section.querySelectorAll(':scope > .bh-testimonial-copy'));
        if (slides.length < 2) return;
        section.dataset.carouselReady = 'true';
        const stage = document.createElement('div');
        stage.className = 'bh-testimonial-stage';
        section.append(stage);
        slides.forEach(slide => stage.append(slide));
        const controls = document.createElement('div');
        controls.className = 'bh-testimonial-controls';
        controls.setAttribute('aria-label', 'Testimonial controls');
        section.append(controls);
        let active = 0, paused = false, hovered = false, focused = false, transitionTimer;
        const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const dots = slides.map((slide, index) => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'bh-testimonial-dot';
            button.setAttribute('aria-label', 'Show testimonial from ' + slide.querySelector('h3').textContent);
            button.addEventListener('click', () => change(index));
            controls.append(button); return button;
        });
        const pause = document.createElement('button');
        pause.type = 'button'; pause.textContent = 'Pause'; pause.setAttribute('aria-pressed', 'false');
        pause.addEventListener('click', () => {
            paused = !paused;
            pause.textContent = paused ? 'Resume' : 'Pause';
            pause.setAttribute('aria-pressed', String(paused));
        });
        controls.append(pause);
        [['Previous testimonial', '‹', -1], ['Next testimonial', '›', 1]].forEach(([label, symbol, step]) => {
            const arrow = document.createElement('button');
            arrow.type = 'button'; arrow.className = 'bh-testimonial-arrow ' + (step < 0 ? 'bh-testimonial-prev' : 'bh-testimonial-next');
            arrow.textContent = symbol; arrow.setAttribute('aria-label', label);
            arrow.addEventListener('click', () => change((active + step + slides.length) % slides.length));
            section.append(arrow);
        });
        function change(index) {
            window.clearTimeout(transitionTimer);
            if (motion.matches) { show(index); return; }
            slides[active].classList.remove('is-active');
            transitionTimer = window.setTimeout(() => show(index), 350);
        }
        function show(index) {
            active = index;
            slides.forEach((slide, i) => {
                slide.classList.toggle('is-active', i === active);
                slide.setAttribute('aria-hidden', String(i !== active));
                slide.inert = i !== active;
                dots[i].setAttribute('aria-current', String(i === active));
            });
        }
        section.addEventListener('mouseenter', () => { hovered = true; });
        section.addEventListener('mouseleave', () => { hovered = false; });
        section.addEventListener('focusin', () => { focused = true; });
        section.addEventListener('focusout', event => { focused = section.contains(event.relatedTarget); });
        show(0);
        window.setInterval(() => {
            if (section.isConnected && !paused && !hovered && !focused && !motion.matches && !document.hidden) change((active + 1) % slides.length);
        }, 8000);
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bhInitTestimonials);
else bhInitTestimonials();
