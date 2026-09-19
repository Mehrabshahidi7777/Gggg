/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: { vazirmatn: ['Vazirmatn', 'sans-serif'] },
            colors: { primary: '#1e3a8a', secondary: '#f97316' },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
