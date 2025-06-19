<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'EUREKA') }} Indonesia</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-blue-600">EUREKA</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition duration-300">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-300">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Carousel Section -->
    <section class="relative pt-16 overflow-hidden">
        <div class="relative h-screen">
            <!-- Carousel Container -->
            <div id="hero-carousel" class="relative h-full">
                @if($heroSlides->count() > 0)
                    @foreach($heroSlides as $index => $slide)
                        <div class="carousel-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 bg-gradient-to-br {{ $slide->background_gradient }}" 
                             @if($slide->hasBackgroundImage()) style="{{ $slide->getBackgroundStyle() }}" @endif>
                            <!-- Overlay for better text readability when using background images -->
                            @if($slide->hasBackgroundImage())
                                <div class="absolute inset-0 bg-black bg-opacity-40"></div>
                            @endif
                            <div class="relative flex items-center justify-center h-full">
                                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                                    <div class="max-w-4xl mx-auto">
                                        @if($slide->icon_svg)
                                            <div class="mb-6 flex justify-center">
                                                {!! $slide->icon_svg !!}
                                            </div>
                                        @endif
                                        <h1 class="text-4xl md:text-6xl font-bold {{ $slide->text_color }} mb-6 leading-tight">
                                            {{ $slide->title }}
                                        </h1>
                                        <p class="text-xl md:text-2xl {{ $slide->text_color === 'text-white' ? 'text-blue-100' : 'text-gray-600' }} mb-8 leading-relaxed">
                                            {{ $slide->subtitle }}
                                        </p>
                                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                            <a href="{{ $slide->primary_button_url }}" class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-lg {{ $slide->button_color }} hover:bg-gray-50 transition duration-300 shadow-lg">
                                                {{ $slide->primary_button_text }}
                                                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                                </svg>
                                            </a>
                                            @if($slide->secondary_button_text && $slide->secondary_button_url)
                                                <a href="{{ $slide->secondary_button_url }}" class="inline-flex items-center px-8 py-4 border-2 border-white text-lg font-medium rounded-lg {{ $slide->text_color }} hover:bg-white hover:text-blue-600 transition duration-300">
                                                    {{ $slide->secondary_button_text }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Default slide if no slides in database -->
                    <div class="carousel-slide active absolute inset-0 bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-800">
                        <div class="flex items-center justify-center h-full">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                                <div class="max-w-4xl mx-auto">
                                    <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                                        Welcome to EUREKA
                                    </h1>
                                    <p class="text-xl md:text-2xl text-blue-100 mb-8 leading-relaxed">
                                        Interactive Team Building Platform for Modern Education.
                                    </p>
                                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-lg text-blue-600 bg-white hover:bg-gray-50 transition duration-300 shadow-lg">
                                            Get Started
                                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                            </svg>
                                        </a>
                                        <a href="https://wa.me/6281380999992?text=Saya%20ingin%20tahu%20lebih%20lanjut%20tentang%20EUREKA%20" class="inline-flex items-center px-8 py-4 border-2 border-white text-lg font-medium rounded-lg text-white hover:bg-white hover:text-blue-600 transition duration-300">
                                            Contact Us
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Carousel Controls -->
            @if($heroSlides->count() > 1)
                <button id="prev-slide" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition duration-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button id="next-slide" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition duration-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <!-- Carousel Indicators -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-3">
                    @foreach($heroSlides as $index => $slide)
                        <button class="carousel-indicator w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100 transition duration-300" data-slide="{{ $index }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose EUREKA?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Experience the future of interactive learning with our innovative platform designed for modern education.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-100 hover:shadow-lg transition duration-300">
                    <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Interactive Quizzes</h3>
                    <p class="text-gray-600">Engage with dynamic quizzes that adapt to your learning pace and provide instant feedback.</p>
                </div>

                <!-- Feature 2 -->
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-green-50 to-teal-100 hover:shadow-lg transition duration-300">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Progress Tracking</h3>
                    <p class="text-gray-600">Monitor your learning journey with detailed analytics and progress visualization.</p>
                </div>

                <!-- Feature 3 -->
                <div class="text-center p-8 rounded-xl bg-gradient-to-br from-purple-50 to-pink-100 hover:shadow-lg transition duration-300">
                    <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Team Collaboration</h3>
                    <p class="text-gray-600">Work together with your team, compete with others, and share knowledge effectively.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to Start Your Learning Journey?</h2>
            <p class="text-xl text-blue-100 mb-8">Join thousands of learners who are already exploring knowledge with EUREKA.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://wa.me/6281380999992?text=Saya%20ingin%20tahu%20lebih%20lanjut%20tentang%20EUREKA%20" class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-lg text-blue-600 bg-white hover:bg-gray-50 transition duration-300 shadow-lg">
                    Contact Us
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 border-2 border-white text-lg font-medium rounded-lg text-white hover:bg-white hover:text-blue-600 transition duration-300">
                    Sign In to Account
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-2xl font-bold mb-4">EUREKA</h3>
                <p class="text-gray-400 mb-6">Interactive Learning Platform</p>
                <div class="text-sm text-gray-500">
                    © {{ date('Y') }} EUREKA. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <!-- Carousel JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.carousel-slide');
            const indicators = document.querySelectorAll('.carousel-indicator');
            const prevBtn = document.getElementById('prev-slide');
            const nextBtn = document.getElementById('next-slide');
            let currentSlide = 0;
            let slideInterval;

            function showSlide(index) {
                // Hide all slides
                slides.forEach(slide => {
                    slide.classList.remove('active');
                    slide.style.opacity = '0';
                });

                // Update indicators
                indicators.forEach(indicator => {
                    indicator.classList.remove('bg-opacity-100');
                    indicator.classList.add('bg-opacity-50');
                });

                // Show current slide
                if (slides[index]) {
                    slides[index].classList.add('active');
                    slides[index].style.opacity = '1';
                }

                // Update current indicator
                if (indicators[index]) {
                    indicators[index].classList.remove('bg-opacity-50');
                    indicators[index].classList.add('bg-opacity-100');
                }

                currentSlide = index;
            }

            function nextSlide() {
                const next = (currentSlide + 1) % slides.length;
                showSlide(next);
            }

            function prevSlide() {
                const prev = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(prev);
            }

            function startAutoSlide() {
                if (slides.length > 1) {
                    slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
                }
            }

            function stopAutoSlide() {
                clearInterval(slideInterval);
            }

            // Initialize
            showSlide(0);
            startAutoSlide();

            // Event listeners
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    stopAutoSlide();
                    nextSlide();
                    startAutoSlide();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    stopAutoSlide();
                    prevSlide();
                    startAutoSlide();
                });
            }

            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    stopAutoSlide();
                    showSlide(index);
                    startAutoSlide();
                });
            });

            // Pause auto-slide on hover
            const carousel = document.getElementById('hero-carousel');
            carousel.addEventListener('mouseenter', stopAutoSlide);
            carousel.addEventListener('mouseleave', startAutoSlide);
        });
    </script>

    <style>
        .carousel-slide {
            transition: opacity 0.5s ease-in-out;
            opacity: 0;
        }

        .carousel-slide.active {
            opacity: 1;
        }
    </style>
</body>
</html>