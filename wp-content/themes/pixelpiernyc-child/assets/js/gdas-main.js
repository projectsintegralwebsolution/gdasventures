/**
 * G Das Ventures - Interactive Scripts
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Header scroll
    const header = document.getElementById('gdas-header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 30) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // 2. Mobile Menu Toggle
    const mobileToggle = document.getElementById('gdas-mobile-toggle');
    const mobileDrawer = document.getElementById('gdas-mobile-drawer');
    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', function() {
            const isOpen = mobileDrawer.classList.contains('active');
            mobileDrawer.classList.toggle('active');
            mobileToggle.setAttribute('aria-expanded', !isOpen);
        });
    }

    // 3. Interactive Accordion (SPG Style)
    const accordionHeaders = document.querySelectorAll('.gdas-accordion-header');
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const item = this.parentElement;
            const body = this.nextElementSibling;
            const isOpen = item.classList.contains('active');

            // Close all others
            document.querySelectorAll('.gdas-accordion-item').forEach(other => {
                if (other !== item) {
                    other.classList.remove('active');
                    if (other.querySelector('.gdas-accordion-body')) {
                        other.querySelector('.gdas-accordion-body').style.maxHeight = null;
                    }
                }
            });

            // Toggle current
            if (isOpen) {
                item.classList.remove('active');
                body.style.maxHeight = null;
            } else {
                item.classList.add('active');
                body.style.maxHeight = body.scrollHeight + 'px';
            }
        });
    });

    // 4. Founder Pitch Form AJAX Submission
    const pitchForm = document.getElementById('gdas-pitch-form');
    const formMsg = document.getElementById('gdas-form-msg');

    if (pitchForm && formMsg) {
        pitchForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = pitchForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Submitting...';
            submitBtn.disabled = true;

            formMsg.className = 'gdas-form-msg';
            formMsg.style.display = 'none';

            const formData = new FormData(pitchForm);
            formData.append('action', 'gdas_submit_pitch');
            formData.append('nonce', gdasData.nonce);

            fetch(gdasData.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (data.success) {
                    formMsg.className = 'gdas-form-msg success';
                    formMsg.textContent = data.data.message;
                    formMsg.style.display = 'block';
                    pitchForm.reset();
                } else {
                    formMsg.className = 'gdas-form-msg error';
                    formMsg.textContent = data.data.message || 'Please check your submission and try again.';
                    formMsg.style.display = 'block';
                }
            })
            .catch(err => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                formMsg.className = 'gdas-form-msg error';
                formMsg.textContent = 'Network error. Please try again or email us directly at contact@gdasventures.com';
                formMsg.style.display = 'block';
            });
        });
    }

    // =========================================================
    // 5. Hero Banner Slider Engine
    // =========================================================
    const sliderWrap = document.querySelector('.gdas-hero-slider-wrap');
    if (sliderWrap) {
        const slides = sliderWrap.querySelectorAll('.gdas-slide');
        const dots = sliderWrap.querySelectorAll('.gdas-dot');
        const prevBtn = sliderWrap.querySelector('.gdas-prev-slide');
        const nextBtn = sliderWrap.querySelector('.gdas-next-slide');
        let currentIndex = 0;
        let slideTimer = null;
        const autoPlayInterval = 6500; // 6.5s

        function goToSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;

            slides.forEach((slide, i) => {
                if (i === index) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            dots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });

            currentIndex = index;
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            slideTimer = setInterval(nextSlide, autoPlayInterval);
        }

        function stopAutoplay() {
            if (slideTimer) {
                clearInterval(slideTimer);
                slideTimer = null;
            }
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                startAutoplay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                startAutoplay();
            });
        }

        dots.forEach((dot, i) => {
            dot.addEventListener('click', function() {
                goToSlide(i);
                startAutoplay();
            });
        });

        // Pause on hover
        sliderWrap.addEventListener('mouseenter', stopAutoplay);
        sliderWrap.addEventListener('mouseleave', startAutoplay);

        // Touch Swipe Support
        let startX = 0;
        sliderWrap.addEventListener('touchstart', function(e) {
            startX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        sliderWrap.addEventListener('touchend', function(e) {
            let endX = e.changedTouches[0].screenX;
            if (startX - endX > 45) {
                nextSlide();
            } else if (endX - startX > 45) {
                prevSlide();
            }
            startAutoplay();
        }, { passive: true });

        startAutoplay();
    }

    // =========================================================
    // 6. SPG Leaders Style Flyout Drawer Menu
    // =========================================================
    const burgerBtn = document.getElementById('gdas-burger-btn');
    const flyoutMenu = document.getElementById('gdas-flyout-menu');
    const flyoutClose = document.getElementById('gdas-flyout-close');
    const flyoutBackdrop = document.getElementById('gdas-flyout-backdrop');

    function openFlyout() {
        if (flyoutMenu) {
            flyoutMenu.classList.add('active');
            flyoutMenu.setAttribute('aria-hidden', 'false');
            if (burgerBtn) burgerBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeFlyout() {
        if (flyoutMenu) {
            flyoutMenu.classList.remove('active');
            flyoutMenu.setAttribute('aria-hidden', 'true');
            if (burgerBtn) burgerBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    }

    if (burgerBtn) {
        burgerBtn.addEventListener('click', openFlyout);
    }

    if (flyoutClose) {
        flyoutClose.addEventListener('click', closeFlyout);
    }

    if (flyoutBackdrop) {
        flyoutBackdrop.addEventListener('click', closeFlyout);
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && flyoutMenu && flyoutMenu.classList.contains('active')) {
            closeFlyout();
        }
    });

    // =========================================================
    // 7. SPG Leaders Scroll-Driven Text Reveal & Dimming Engine
    // =========================================================
    // A. Word-by-Word Continuous Text Reveal Scrub
    const scrubHeadings = document.querySelectorAll(
        '.elementor-element-fa0a00d h2, .elementor-element-fa0a00d .elementor-heading-title, .vamtam-has-text-reveal-anim, .gdas-text-reveal'
    );

    const scrubWordsList = [];

    scrubHeadings.forEach(heading => {
        // Only wrap once
        if (heading.getAttribute('data-scrub-ready') === 'true') return;

        const rawText = heading.textContent.trim();
        if (!rawText) return;

        const words = rawText.split(/\s+/);
        heading.innerHTML = '';
        heading.setAttribute('data-scrub-ready', 'true');
        heading.style.opacity = '1';

        const wordsInHeading = [];
        words.forEach(word => {
            const span = document.createElement('span');
            span.className = 'vamtam-tra-word gdas-scrub-word';
            span.textContent = word + ' ';
            heading.appendChild(span);
            wordsInHeading.push(span);
        });

        scrubWordsList.push({
            container: heading,
            words: wordsInHeading
        });
    });

    // B. Card & Section Reading Focus Observer
    const focusItems = document.querySelectorAll(
        '.elementor-element-763c2fd .elementor-widget-icon-box, ' +
        '.elementor-element-27a8a01 .elementor-widget-icon-box, ' +
        '.gdas-section-black .elementor-widget-icon-box, ' +
        '.elementor-element-0e9134f .elementor-widget-icon-box, ' +
        '.elementor-element-287d573 .elementor-widget-icon-box, ' +
        '.elementor-widget-accordion .elementor-accordion-item'
    );

    focusItems.forEach(item => {
        item.classList.add('gdas-scroll-focus-item');
    });

    // High Performance Scroll Handler (requestAnimationFrame)
    let isTicking = false;

    function updateScrollInteractions() {
        const vHeight = window.innerHeight;
        const targetGazeY = vHeight * 0.48; // Eye-level focus center
        const focusRange = vHeight * 0.38;

        // 1. Update Word Scrub Opacity
        scrubWordsList.forEach(group => {
            const groupRect = group.container.getBoundingClientRect();
            // Check if group is anywhere near the viewport
            if (groupRect.bottom > -100 && groupRect.top < vHeight + 100) {
                group.words.forEach(wordSpan => {
                    const wRect = wordSpan.getBoundingClientRect();
                    const wordCenterY = wRect.top + (wRect.height * 0.5);
                    const dist = Math.abs(wordCenterY - targetGazeY);

                    // Near target gaze center -> 1.0; moving away -> 0.28
                    let opacity = 1 - (dist / focusRange) * 0.72;
                    if (opacity < 0.28) opacity = 0.28;
                    if (opacity > 1) opacity = 1;

                    wordSpan.style.opacity = opacity.toFixed(3);
                });
            }
        });

        // 2. Update Card / Section Dimming Focus
        const focusTopThreshold = vHeight * 0.12;
        const focusBottomThreshold = vHeight * 0.88;

        focusItems.forEach(item => {
            const rect = item.getBoundingClientRect();
            const itemCenterY = rect.top + (rect.height * 0.5);

            // If item center is in the active reading zone
            if (itemCenterY >= focusTopThreshold && itemCenterY <= focusBottomThreshold) {
                item.classList.add('gdas-in-focus');
                item.classList.remove('gdas-dimmed');
            } else {
                item.classList.remove('gdas-in-focus');
                item.classList.add('gdas-dimmed');
            }
        });

        isTicking = false;
    }

    function onScroll() {
        if (!isTicking) {
            window.requestAnimationFrame(updateScrollInteractions);
            isTicking = true;
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });

    // Accordion Click Robustness
    document.addEventListener('click', function(e) {
        const title = e.target.closest('.elementor-widget-accordion .elementor-tab-title');
        if (!title) return;
        const content = title.nextElementSibling;
        if (content && content.classList.contains('elementor-tab-content')) {
            setTimeout(() => {
                const isActive = title.classList.contains('elementor-active');
                if (isActive) {
                    content.style.display = 'block';
                }
            }, 50);
        }
    });

    // Initial pass on load
    updateScrollInteractions();
    setTimeout(updateScrollInteractions, 300);
});
