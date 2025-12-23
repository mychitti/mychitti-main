class ImageCarousel {
    constructor() {
        this.$container = $('#carouselContainer');
        this.$track = $('#carouselTrack');
        this.$dotsContainer = $('#dotsContainer');
        this.$prevBtn = $('#prevBtn');
        this.$nextBtn = $('#nextBtn');
        
        this.currentIndex = 0;
        this.autoplayInterval = null;
        this.$slides = this.$track.find('.r7t1-slide');
        this.isMobile = $(window).width() < 700;
        
        this.init();
    }
    
    init() {
        if (this.$slides.length === 0) {
            console.warn('No slides found in carousel');
            return;
        }

        // Check if we should hide navigation based on slides and screen size
        this.checkNavigationVisibility();
        
        this.createDots();
        this.updateCarousel();
        this.bindEvents();
        
        // Only start autoplay if navigation is needed
        if (this.shouldShowNavigation()) {
            this.startAutoplay();
        }

        // Mark as initialized
        this.$container.addClass('initialized');
    }
    
    bindEvents() {
        // Navigation buttons
        this.$prevBtn.on('click', () => this.previousSlide());
        this.$nextBtn.on('click', () => this.nextSlide());
        
        // Keyboard navigation
        $(document).on('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.previousSlide();
            if (e.key === 'ArrowRight') this.nextSlide();
        });
        
        $(window).on('resize', () => this.handleResize());
        
        // Touch/swipe support
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        this.$track.on('touchstart', (e) => {
            startX = e.originalEvent.touches[0].clientX;
            isDragging = true;
            this.pauseAutoplay();
        });
        
        this.$track.on('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.originalEvent.touches[0].clientX;
        });
        
        this.$track.on('touchend', () => {
            if (!isDragging) return;
            const diff = startX - currentX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.previousSlide();
                }
            }
            isDragging = false;
            this.resumeAutoplay();
        });

        // Pause autoplay on hover
        this.$container.on('mouseenter', () => {
            this.pauseAutoplay();
        });

        this.$container.on('mouseleave', () => {
            this.resumeAutoplay();
        });

        // Handle image loading errors
        this.$slides.each((index, slide) => {
            const $slide = $(slide);
            const $img = $slide.find('.slide-image');
            if ($img.length) {
                $img.on('error', () => {
                    $slide.find('.v3l9-content').addClass('slide-loading');
                });
            }
        });
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = $(window).width() < 700;
        
        if (wasMobile !== this.isMobile) {
            // Reset to first slide when changing layouts
            this.currentIndex = 0;
            this.checkNavigationVisibility();
            this.createDots();
            this.updateCarousel();
            
            // Restart autoplay if needed
            this.pauseAutoplay();
            if (this.shouldShowNavigation()) {
                this.startAutoplay();
            }
        }
    }
    
    createDots() {
        this.$dotsContainer.empty();
        
        if (!this.shouldShowNavigation()) return;
        
        const totalDots = this.getTotalDots();
        
        for (let i = 0; i < totalDots; i++) {
            const $dot = $('<button>')
                .addClass('n6z3-dot')
                .on('click', () => this.goToSlide(i));
            this.$dotsContainer.append($dot);
        }
    }
    
    updateCarousel() {
        if (this.$slides.length === 0) return;
        
        const slidesToShow = this.getSlidesToShow();
        const slideWidth = 100 / slidesToShow;
        const translateX = -this.currentIndex * slideWidth;
        
        this.$track.css('transform', `translateX(${translateX}%)`);
        
        // Update dots
        const $dots = this.$dotsContainer.find('.n6z3-dot');
        $dots.each((index, dot) => {
            $(dot).toggleClass('t8r5-active', index === this.currentIndex);
        });
    }
    
    nextSlide() {
        if (!this.shouldShowNavigation()) return;
        
        const maxIndex = this.getMaxIndex();
        
        // Loop back to beginning
        if (this.currentIndex >= maxIndex) {
            this.currentIndex = 0;
        } else {
            this.currentIndex++;
        }
        this.updateCarousel();
    }
    
    previousSlide() {
        if (!this.shouldShowNavigation()) return;
        
        const maxIndex = this.getMaxIndex();
        
        // Loop to end
        if (this.currentIndex <= 0) {
            this.currentIndex = maxIndex;
        } else {
            this.currentIndex--;
        }
        this.updateCarousel();
    }
    
    goToSlide(index) {
        const maxIndex = this.getMaxIndex();
        if (index >= 0 && index <= maxIndex) {
            this.currentIndex = index;
            this.updateCarousel();
        }
    }

    // Helper methods
    getSlidesToShow() {
        return this.isMobile ? 2 : 1;
    }

    getMaxIndex() {
        const slidesToShow = this.getSlidesToShow();
        return Math.max(0, this.$slides.length - slidesToShow);
    }

    getTotalDots() {
        return this.getMaxIndex() + 1;
    }

    shouldShowNavigation() {
        const slidesToShow = this.getSlidesToShow();
        return this.$slides.length > slidesToShow;
    }

    checkNavigationVisibility() {
        if (this.shouldShowNavigation()) {
            this.$container.removeClass('single-slide');
        } else {
            this.$container.addClass('single-slide');
        }
    }
    
    startAutoplay() {
        this.autoplayInterval = setInterval(() => {
            this.nextSlide();
        }, 4000); // Change slide every 4 seconds
    }

    pauseAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
        }
    }

    resumeAutoplay() {
        if (this.shouldShowNavigation()) {
            this.startAutoplay();
        }
    }
}

// Initialize carousel when DOM is loaded
$(document).ready(() => {
    new ImageCarousel();
});