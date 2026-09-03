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
});
