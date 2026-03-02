import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './assets/src/js/**/*.{js,jsx}',
        './blocks/**/*.block',
        './layouts/**/*.htm',
        './pages/**/*.htm',
        './partials/**/*.htm',
        './content/**/*.htm',
        '../../../plugins/**/components/**/*.htm',
    ],
    theme: {
        extend: {
            colors: {
                // Primary cooking colors
                'primary': '#E85A4F',      // Warm tomato red
                'primary-dark': '#D94A3F', // Darker tomato
                'secondary': '#4A7C59',    // Fresh herb green
                'secondary-light': '#6B9D7D', // Light herb green
                'accent': '#F4A259',       // Golden/orange (honey/turmeric)
                'accent-light': '#FFD699', // Light honey
                // Neutral tones
                'cream': '#FDF8F3',        // Warm off-white
                'warm-gray': '#F5F0EB',    // Slightly warm gray
                'charcoal': '#2D3436',     // Dark charcoal
                'text-muted': '#636E72',   // Muted text
                // Supporting colors
                'terracotta': '#D4A59A',   // Terracotta accent
                'sage': '#9CAF88',         // Sage green
                'butter': '#F9E4B7',       // Butter yellow
            },
            fontFamily: {
                'sans': ['Inter', ...defaultTheme.fontFamily.sans],
                'display': ['Playfair Display', 'Georgia', 'serif'],
            },
        }
    },
    plugins: [forms],
};
