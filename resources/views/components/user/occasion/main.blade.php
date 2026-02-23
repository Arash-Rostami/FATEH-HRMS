@php
    $occasionType = null;
    $message = '';
    $title = '';

    // Check Birthday
    if (function_exists('isSpecialDay') && isSpecialDay('birthdate')) {
         $birthdayQuotes = [
            "Wishing you a day filled with laughter and joy.",
            "May your day be as special as you are.",
            "Another year, another reason to celebrate!",
            "May this year be the best one yet.",
            "Today is all about you. Enjoy every moment.",
            "Wishing you health, happiness, and success in the year ahead.",
            "Embrace the new age with grace and positivity.",
            "Life is a journey, and today is a new beginning.",
            "Birthdays are nature's way of telling us to eat more cake!",
            "Celebrate the gift of life with gratitude and love.",
            "A year older, a year wiser.",
            "You are never too old to set another goal or dream a new dream.",
            "Age is merely the number of years the world has been enjoying you.",
            "In the end, it's not the years in your life that count. It's the life in your years.",
            "Every year on your birthday, you get a chance to start new.",
            "A birthday is the first day of another 365-day journey around the sun. Enjoy the trip!",
            "Growing older is a privilege denied to many. Celebrate it!",
            "Youth is a gift of nature, but age is a work of art.",
            "Remember, age is merely the number of years the world has been enjoying you!",
        ];
        $occasionType = 'birthday';
        $title = 'تولدتان مبارک ' . (auth()->user()->forename ?? 'عزیز') . '!';
        $message = $birthdayQuotes[array_rand($birthdayQuotes)];

        // Cache mechanism
        cache()->put('birthdate'.auth()->id(), 'shown', now()->addHours(8));
    }
    // Check Anniversary
    elseif (function_exists('isSpecialDay') && isSpecialDay('start_date')) {
        $startDateQuotes = [
          "Congratulations on reaching another milestone in your career!",
          "Here's to a year of growth, learning, and success.",
          "Your dedication and hard work have made a significant impact on our team.",
          "Cheers to a year filled with achievements and new opportunities.",
          "Thank you for your commitment and contributions to our company.",
          "Your journey with us has been nothing short of remarkable.",
          "May this anniversary mark the beginning of even greater accomplishments.",
          "Your professionalism and dedication inspire us all.",
          "A year of dedication, teamwork, and excellence. Well done!",
          "Your passion for what you do shines through in your work.",
          "May your career continue to thrive and prosper with each passing year.",
          "Your journey with us has been a fantastic adventure. Here's to many more!",
          "Congratulations on achieving another successful year in your career.",
          "Your hard work and commitment are truly appreciated.",
          "Your contributions have made our team stronger and more successful.",
          "May this anniversary be a stepping stone to even greater achievements.",
          "Your enthusiasm and dedication continue to inspire us.",
          "One year down, many more to go. Your future here is bright!",
          "Thank you for your dedication and passion in everything you do.",
          "Your journey with us is a testament to your outstanding skills and commitment.",
        ];
        $occasionType = 'anniversary';
        $title = 'سالگرد همکاریتان مبارک ' . (auth()->user()->forename ?? 'عزیز') . '!';
        $message = $startDateQuotes[array_rand($startDateQuotes)];

        // Cache mechanism
        cache()->put('start_date'.auth()->id(), 'shown', now()->addHours(8));
    }
@endphp

@if($occasionType)
    <div
        x-data="{ show: true }"
        x-show="show"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        class="fixed inset-0 z-[100] flex items-center justify-center px-4"
        style="display: none;"
    >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="show = false"></div>

        <!-- Confetti Canvas (Placeholder for JS lib) -->
        <canvas id="confetti-canvas" class="absolute inset-0 w-full h-full pointer-events-none z-[101]"></canvas>

        <!-- Modal Content -->
        <div class="relative w-full max-w-md bg-[var(--md-sys-color-surface)] rounded-3xl shadow-2xl border border-[var(--md-sys-color-outline-variant)] overflow-hidden z-[102]">

            <!-- Header Image/Graphic -->
            <div class="h-32 bg-gradient-to-br from-[var(--md-sys-color-primary)] to-[var(--md-sys-color-tertiary)] flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('/images/patterns/confetti.png')] opacity-20 bg-cover"></div>
                <span class="material-symbols-rounded text-6xl text-white drop-shadow-lg animate-bounce">
                    {{ $occasionType === 'birthday' ? 'cake' : 'workspace_premium' }}
                </span>
            </div>

            <!-- Body -->
            <div class="p-6 md:p-8 text-center space-y-4">
                <h2 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">
                    {{ $title }}
                </h2>

                <p class="text-[var(--md-sys-color-on-surface-variant)] text-base leading-relaxed italic dir-ltr">
                    &ldquo;{{ $message }}&rdquo;
                </p>

                <div class="pt-4">
                    <button
                        @click="show = false"
                        class="w-full py-3 px-6 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] font-bold shadow-lg shadow-[var(--md-sys-color-primary)]/30 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-200"
                    >
                        متشکرم!
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
