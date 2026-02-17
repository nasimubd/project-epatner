<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy"
        content="speculation-rules=(), interest-cohort=(), browsing-topics=()">
    <title>{{ config('app.name', 'ePatner') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">

    <!-- Choose one of these font options -->
    <!-- Option A: Inter (Modern, Clean) -->
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Option B: Poppins (Friendly, Rounded) -->
    <!-- <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" /> -->

    <!-- Option C: Roboto (Google's Material Design) -->
    <!-- <link href="https://fonts.bunny.net/css?family=roboto:400,500,600,700&display=swap" rel="stylesheet" /> -->

    <!-- Option D: Nunito (Soft, Friendly) -->
    <!-- <link href="https://fonts.bunny.net/css?family=nunito:400,500,600,700&display=swap" rel="stylesheet" /> -->

    <!-- Option E: Open Sans (Highly Readable) -->
    <!-- <link href="https://fonts.bunny.net/css?family=open-sans:400,500,600,700&display=swap" rel="stylesheet" /> -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Translate Script -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,hi,bn,te,ta,gu,mr,kn,ml,pa,or,as,ur',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false,
                gaTrack: true,
                gaId: 'UA-XXXXX-X'
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <style>
        /* Custom Font Application */
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

        /* Apply font to all text elements */
        * {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Custom Google Translate Styling */
        .goog-te-banner-frame.skiptranslate {
            display: none !important;
        }

        .goog-te-gadget {
            font-family: 'Inter', sans-serif !important;
            font-size: 10px !important;
        }

        .goog-te-gadget-simple {
            background-color: transparent !important;
            border: none !important;
            padding: 0 !important;
            font-size: 10px !important;
            color: #374151 !important;
            text-align: center !important;
            font-family: 'Inter', sans-serif !important;
        }

        .goog-te-gadget-simple:hover {
            background-color: transparent !important;
        }

        .goog-te-gadget-icon {
            display: none !important;
        }

        .goog-te-gadget-simple .goog-te-menu-value span {
            color: inherit !important;
            font-size: 10px !important;
            text-transform: uppercase !important;
            font-weight: 500 !important;
            font-family: 'Inter', sans-serif !important;
        }

        body {
            top: 0px !important;
        }

        #google_translate_element {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        /* Sidebar specific styling */
        .sidebar-translate-item #google_translate_element .goog-te-gadget-simple {
            width: 100% !important;
            text-align: center !important;
        }

        /* Google Translate Banner Spacing */
        .goog-te-banner-frame {
            margin-bottom: 20px !important;
            padding-bottom: 10px !important;
        }

        /* Add spacing when translation is active */
        body.translated {
            padding-top: 20px !important;
        }

        /* Style the translate menu frame */
        .goog-te-menu-frame {
            z-index: 9999 !important;
            margin-top: 5px !important;
        }

        /* Add spacing to the translate notification bar if it appears */
        .goog-te-ftab {
            margin-bottom: 15px !important;
        }

        /* Ensure content doesn't overlap with translate elements */
        .main-content-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Add spacing when Google Translate modifies the page */
        html[translate="yes"] body,
        html[class*="translated"] body {
            margin-top: 20px !important;
        }
    </style>
</head>

<body class="font-sans antialiased" style="background-color: #E1E6F1; font-family: 'Inter', sans-serif;">
    <div class="min-h-screen flex">
        <!-- Include the new admin sidebar navigation component -->
        <x-admin-sidebar-navigation />

        <x-print-modal />

        <!-- Main Content -->
        <div class="flex-1 min-h-screen main-content-wrapper">
            <div class="p-4 lg:p-10 lg:ml-16 flex-1 overflow-y-auto" style="background-color: #E1E6F1; padding-top: 20px;">
                <div class="container mx-auto" style="margin-top: 20px;">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script rel="preconnect" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script rel="preconnect" defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // Additional customization for Google Translate
        document.addEventListener('DOMContentLoaded', function() {
            // Hide Google Translate banner
            const style = document.createElement('style');
            style.innerHTML = `
                .goog-te-banner-frame { 
                    display: none !important; 
                }
                .goog-te-menu-frame { 
                    z-index: 9999 !important; 
                    margin-top: 5px !important;
                }
                /* Add spacing when translation is active */
                body[class*="translated"] {
                    padding-top: 20px !important;
                }
                /* Style any translate notification elements */
                .goog-te-ftab {
                    margin-bottom: 15px !important;
                }
            `;
            document.head.appendChild(style);

            // Remove top margin added by Google Translate
            document.body.style.top = '0px';

            // Monitor for translation changes and add spacing
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' &&
                        (mutation.attributeName === 'class' || mutation.attributeName === 'translate')) {
                        // Add spacing when translation is detected
                        if (document.body.classList.contains('translated') ||
                            document.documentElement.hasAttribute('translate')) {
                            document.body.style.paddingTop = '20px';
                        }
                    }
                });
            });

            // Start observing
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class', 'translate']
            });

            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class', 'translate']
            });
        });
    </script>

    @stack('scripts')
</body>

</html>