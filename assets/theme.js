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
    const targets = root.querySelectorAll('.studio-image, .studio-copy, .story-panel, .blue-band, .productions .section-head, .partners, .faq > div, .contact, .spec-heading, .green-section .section-head, .green-image, .wall-specs');
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
