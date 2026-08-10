/**
 * NCLLC Pro Theme JavaScript
 * Modern interactive features and animations
 */

// Mark JS active early — allows CSS section-reveal (.js section) to safely start at opacity:0
document.documentElement.classList.add('js');

(function($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function() {
        
        // Smooth scrolling for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80
                }, 800, 'swing');
            }
        });

        // Header scroll effect
        let lastScroll = 0;
        const header = $('.site-header');
        
        $(window).on('scroll', function() {
            const currentScroll = $(this).scrollTop();
            
            if (currentScroll > 100) {
                header.addClass('scrolled');
            } else {
                header.removeClass('scrolled');
            }
            
            lastScroll = currentScroll;
        });

        // Reveal-on-scroll: any element with .animate-on-scroll (or the
        // .animate-fade-in / .animate-slide-left / .animate-slide-right /
        // .animate-scale-in variants — see style.css) gets .animated added
        // once it enters the viewport. IntersectionObserver instead of a
        // scroll-position poll — no per-scroll-event layout reads, and it
        // still fires correctly for elements already in view on load.
        // CSS handles prefers-reduced-motion (elements are simply visible
        // from the start there), so this doesn't need to special-case it.
        var revealSelector = '.animate-on-scroll, .animate-fade-in, .animate-slide-left, .animate-slide-right, .animate-scale-in';
        var revealTargets = document.querySelectorAll(revealSelector);

        if (revealTargets.length) {
            if ('IntersectionObserver' in window) {
                var revealObserver = new IntersectionObserver(function (entries, observer) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animated');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });

                revealTargets.forEach(function (el) {
                    revealObserver.observe(el);
                });
            } else {
                // No IntersectionObserver support: reveal immediately
                // rather than leaving content stuck hidden.
                revealTargets.forEach(function (el) {
                    el.classList.add('animated');
                });
            }
        }

        // Mobile menu toggle
        let menuScrollY = 0;

        function lockBodyScroll() {
            menuScrollY = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = '-' + menuScrollY + 'px';
            document.body.style.width = '100%';
            document.body.classList.add('menu-open');
        }

        function unlockBodyScroll() {
            document.body.classList.remove('menu-open');
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            window.scrollTo(0, menuScrollY);
        }

        $('#mobile-menu-toggle').on('click', function() {
            const isOpen = !$(this).hasClass('active');

            $(this).toggleClass('active', isOpen);
            $(this).attr('aria-expanded', isOpen ? 'true' : 'false');
            $('.nav-menu').toggleClass('mobile-active', isOpen);
            if (isOpen) {
                lockBodyScroll();
            } else {
                $('.nav-menu').find('.submenu-open').removeClass('submenu-open');
                unlockBodyScroll();
            }
        });

        // Mobile submenu toggle — tap parent link to expand/collapse
        $(document).on('click', '.nav-menu.mobile-active .menu-item-has-children > a', function(e) {
            e.preventDefault();
            var $parent = $(this).closest('.menu-item-has-children');
            var isOpen = $parent.hasClass('submenu-open');
            // Collapse siblings at the same level
            $parent.siblings('.submenu-open').removeClass('submenu-open');
            $parent.toggleClass('submenu-open', !isOpen);
        });

        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.main-navigation, .ajn-builder-cell-primary-menu, #mobile-menu-toggle').length) {
                $('#mobile-menu-toggle').removeClass('active');
                $('#mobile-menu-toggle').attr('aria-expanded', 'false');
                $('.nav-menu').removeClass('mobile-active').find('.submenu-open').removeClass('submenu-open');
                unlockBodyScroll();
            }
        });

        // Counter animation for stats
        function animateCounter() {
            $('.stat-number').each(function() {
                const $this = $(this);
                const originalText = $this.text().trim();
                const numericText = originalText.replace(/\D/g, '');
                const countTo = parseInt(numericText, 10);
                const suffix = originalText.replace(/[0-9]/g, '');
                
                if (!$this.hasClass('counted')) {
                    $this.addClass('counted');

                    if (!/^\d+[+%]?$/.test(originalText) || !numericText || Number.isNaN(countTo)) {
                        $this.text(originalText);
                        return;
                    }
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            $this.text(Math.floor(this.countNum) + suffix);
                        },
                        complete: function() {
                            $this.text(countTo + suffix);
                        }
                    });
                }
            });
        }

        // Trigger counter animation when stats section is visible
        $(window).on('scroll', function() {
            const statsSection = $('.stats');
            if (statsSection.length) {
                const statsSectionTop = statsSection.offset().top;
                const statsSectionBottom = statsSectionTop + statsSection.outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();
                
                if (statsSectionBottom > viewportTop && statsSectionTop < viewportBottom) {
                    animateCounter();
                }
            }
        });

        // Parallax effect for hero section
        $(window).on('scroll', function() {
            const scrolled = $(window).scrollTop();
            $('.hero-content').css('transform', 'translateY(' + (scrolled * 0.3) + 'px)');
        });

        // Add loading animation
        $('body').addClass('loaded');

        // Button ripple effect
        $('.btn').on('click', function(e) {
            const $button = $(this);
            const $ripple = $('<span class="ripple"></span>');
            
            const diameter = Math.max($button.width(), $button.height());
            const radius = diameter / 2;
            
            $ripple.css({
                width: diameter,
                height: diameter,
                left: e.pageX - $button.offset().left - radius,
                top: e.pageY - $button.offset().top - radius
            });
            
            $button.append($ripple);
            
            setTimeout(function() {
                $ripple.remove();
            }, 600);
        });

        // Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        }

        // Add smooth reveal animation to sections
        const revealSections = function() {
            const sections = document.querySelectorAll('section');
            const windowHeight = window.innerHeight;
            
            sections.forEach(function(section) {
                const sectionTop = section.getBoundingClientRect().top;
                const revealPoint = 150;
                
                if (sectionTop < windowHeight - revealPoint) {
                    section.classList.add('revealed');
                }
            });
        };
        
        window.addEventListener('scroll', revealSections);
        revealSections();

        // Form validation — inline banner instead of alert()
        $('form').on('submit', function(e) {
            var $form = $(this);
            var isValid = true;

            $form.find('.form-validation-error').remove();

            $form.find('input[required], textarea[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                var $banner = $('<div class="form-validation-error" role="alert">Please fill in all required fields.</div>');
                $form.find('input[required].error, textarea[required].error').first().before($banner);
                $banner[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });

        // Clear individual field error on input
        $('form').on('input', 'input[required], textarea[required]', function() {
            if ($(this).val().trim()) {
                $(this).removeClass('error');
            }
        });

        // Add active class to current nav item
        const currentUrl = window.location.href;
        $('.nav-menu a').each(function() {
            if (this.href === currentUrl) {
                $(this).addClass('active');
            }
        });

        // Scroll to top button
        const scrollTopBtn = $('<button class="scroll-to-top" aria-label="Scroll to top">↑</button>');
        $('body').append(scrollTopBtn);
        
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 300) {
                scrollTopBtn.addClass('visible');
            } else {
                scrollTopBtn.removeClass('visible');
            }
        });
        
        scrollTopBtn.on('click', function() {
            $('html, body').animate({ scrollTop: 0 }, 600);
        });

        // Note: scroll-to-top, ripple, section-reveal, and feature-icon CSS
        // are now in style.css (no longer injected dynamically).

        // FAQ Toggle functionality
        $('.faq-question').on('click keydown', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
                return;
            }

            e.preventDefault();

            const $item = $(this).closest('.faq-item');
            $item.toggleClass('active');
            $(this).attr('aria-expanded', $item.hasClass('active') ? 'true' : 'false');
            $(this).find('.faq-toggle').text(
                $item.hasClass('active') ? '−' : '+'
            );
        });

        // Smooth reveal for pricing cards
        const pricingCards = document.querySelectorAll('.pricing-card');
        pricingCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.animation = `scaleIn 0.6s ease-out forwards`;
            }, index * 150);
        });

        // Newsletter form — focus state handled via CSS :focus on .newsletter-form input

        // Process steps animation
        const processSteps = document.querySelectorAll('.process-step');
        const observerOptions = {
            threshold: 0.3,
            rootMargin: '0px 0px -100px 0px'
        };

        const processObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.animation = 'slideInFromLeft 0.8s ease-out forwards';
                    }, index * 200);
                    processObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        processSteps.forEach(step => {
            processObserver.observe(step);
        });

        // Contact Form Handler
        $('#contact-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                name: $(this).find('[name="name"]').val(),
                email: $(this).find('[name="email"]').val(),
                business: $(this).find('[name="business"]').val(),
                message: $(this).find('[name="message"]').val()
            };
            
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="loading-spinner"></span> Sending...').prop('disabled', true);
            
            // Simulate form submission (replace with actual AJAX call)
            var $form = $(this);
            setTimeout(() => {
                submitBtn.html('✓ Message Sent!').addClass('btn-sent');
                $form[0].reset();

                var $success = $('<div class="form-success-message" role="alert">Thank you for contacting University Place Office Suites! We will respond to your inquiry shortly.</div>');
                $form.prepend($success);
                $success[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                setTimeout(() => {
                    submitBtn.html(originalText).removeClass('btn-sent').prop('disabled', false);
                    $success.fadeOut(400, function() { $(this).remove(); });
                }, 5000);
            }, 1000);
        });

        // Performance optimization: Debounce scroll events
        function debounce(func, wait) {
            let timeout;

            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };

                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Apply debounce to scroll handlers
        const debouncedScroll = debounce(function() {
            animateOnScroll();
            revealSections();
        }, 10);

        $(window).on('scroll', debouncedScroll);

        console.log('🚀 NCLLC Pro Theme loaded successfully!');
    });

})(jQuery);
