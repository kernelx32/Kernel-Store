document.addEventListener('DOMContentLoaded', function() {
    // Testimonial Slider
    const testimonialContainer = document.querySelector('.testimonial-container');
    const testimonialSlides = document.querySelectorAll('.testimonial-slide');
    const prevButton = document.querySelector('.testimonial-prev');
    const nextButton = document.querySelector('.testimonial-next');
    const dots = document.querySelectorAll('.testimonial-dot');
    
    let currentSlide = 0;
    const slideCount = testimonialSlides.length;
    
    // Initialize slider
    updateSlider();
    
    // Previous button click
    if (prevButton) {
        prevButton.addEventListener('click', function() {
            currentSlide = (currentSlide - 1 + slideCount) % slideCount;
            updateSlider();
        });
    }
    
    // Next button click
    if (nextButton) {
        nextButton.addEventListener('click', function() {
            currentSlide = (currentSlide + 1) % slideCount;
            updateSlider();
        });
    }
    
    // Dot navigation
    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            currentSlide = parseInt(this.getAttribute('data-index'));
            updateSlider();
        });
    });
    
    // Auto slide every 5 seconds
    let autoSlideInterval = setInterval(function() {
        currentSlide = (currentSlide + 1) % slideCount;
        updateSlider();
    }, 5000);
    
    // Pause auto slide on hover
    const testimonialSlider = document.querySelector('.testimonial-slider');
    if (testimonialSlider) {
        testimonialSlider.addEventListener('mouseenter', function() {
            clearInterval(autoSlideInterval);
        });
        
        testimonialSlider.addEventListener('mouseleave', function() {
            autoSlideInterval = setInterval(function() {
                currentSlide = (currentSlide + 1) % slideCount;
                updateSlider();
            }, 5000);
        });
    }
    
    // Update slider position and active dot
    function updateSlider() {
        if (testimonialContainer) {
            testimonialContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        }
        
        dots.forEach((dot, index) => {
            if (index === currentSlide) {
                dot.classList.add('bg-indigo-600');
                dot.classList.remove('bg-gray-600');
            } else {
                dot.classList.remove('bg-indigo-600');
                dot.classList.add('bg-gray-600');
            }
        });
    }
    
    // Mobile Menu Toggle
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        });
    }
    
    // User Dropdown Toggle
    const userDropdownButton = document.querySelector('.user-dropdown-button');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userDropdownButton && userDropdown) {
        userDropdownButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (!userDropdown.classList.contains('hidden')) {
                userDropdown.classList.add('hidden');
            }
        });
        
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});