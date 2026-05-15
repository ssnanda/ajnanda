/**
 * NCLLC Pro Theme JavaScript
 * Modern interactive features and animations
 */

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

        // Animate elements on scroll
        function animateOnScroll() {
            $('.animate-on-scroll').each(function() {
                const elementTop = $(this).offset().top;
                const elementBottom = elementTop + $(this).outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();
                
                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $(this).addClass('animated');
                }
            });
        }
        
        // Run on scroll
        $(window).on('scroll', animateOnScroll);
        
        // Run on page load
        animateOnScroll();

        // Mobile menu toggle
        $('#mobile-menu-toggle').on('click', function() {
            const isOpen = !$(this).hasClass('active');

            $(this).toggleClass('active', isOpen);
            $(this).attr('aria-expanded', isOpen ? 'true' : 'false');
            $('.nav-menu').toggleClass('mobile-active', isOpen);
            $('body').toggleClass('menu-open');
        });

        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.main-navigation, .ajn-builder-cell-primary-menu, #mobile-menu-toggle').length) {
                $('#mobile-menu-toggle').removeClass('active');
                $('#mobile-menu-toggle').attr('aria-expanded', 'false');
                $('.nav-menu').removeClass('mobile-active');
                $('body').removeClass('menu-open');
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

        // Feature cards hover effect enhancement
        $('.feature-card').on('mouseenter', function() {
            $(this).find('.feature-icon').css('transform', 'scale(1.1) rotate(5deg)');
        }).on('mouseleave', function() {
            $(this).find('.feature-icon').css('transform', 'scale(1) rotate(0deg)');
        });

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

        // Form validation enhancement
        $('form').on('submit', function(e) {
            let isValid = true;
            
            $(this).find('input[required], textarea[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
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

        // Add CSS for scroll to top button
        $('<style>')
            .text(`
                .scroll-to-top {
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    width: 50px;
                    height: 50px;
                    background: var(--primary);
                    color: white;
                    border: none;
                    border-radius: 50%;
                    font-size: 1.5rem;
                    cursor: pointer;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    z-index: 999;
                }
                .scroll-to-top.visible {
                    opacity: 1;
                    visibility: visible;
                }
                .scroll-to-top:hover {
                    background: var(--primary-dark);
                    transform: translateY(-3px);
                    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
                }
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s ease-out;
                    pointer-events: none;
                }
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                body.loaded {
                    animation: fadeIn 0.5s ease-in;
                }
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                .feature-icon {
                    transition: transform 0.3s ease;
                }
                section {
                    opacity: 0;
                    transform: translateY(20px);
                    transition: opacity 0.6s ease, transform 0.6s ease;
                }
                section.revealed {
                    opacity: 1;
                    transform: translateY(0);
                }
            `)
            .appendTo('head');

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

        // Newsletter form enhancement
        $('.newsletter-form input').on('focus', function() {
            $(this).css({
                'border-color': 'white',
                'background': 'rgba(255,255,255,0.2)'
            });
        }).on('blur', function() {
            $(this).css({
                'border-color': 'rgba(255,255,255,0.3)',
                'background': 'rgba(255,255,255,0.1)'
            });
        });

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
            step.style.opacity = '0';
            processObserver.observe(step);
        });

        // Add hover effect to pricing cards
        $('.pricing-card').on('mouseenter', function() {
            if (!$(this).hasClass('featured')) {
                $(this).css('background', 'linear-gradient(135deg, #f9fafb 0%, #ffffff 100%)');
            }
        }).on('mouseleave', function() {
            if (!$(this).hasClass('featured')) {
                $(this).css('background', 'white');
            }
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
            setTimeout(() => {
                submitBtn.html('✓ Message Sent!').css('background', 'var(--success)');
                $(this)[0].reset();
                
                setTimeout(() => {
                    submitBtn.html(originalText).css('background', '').prop('disabled', false);
                }, 3000);
                
                alert('Thank you for contacting NC LLC Agents Inc! We will respond to your inquiry shortly.');
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
