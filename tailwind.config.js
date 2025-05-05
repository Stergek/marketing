module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './vendor/filament/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {},
    },
    plugins: [],
    safelist: [
        'text-green-600',
        'dark:text-green-400',
        'text-red-600',
        'dark:text-red-400',
        // 'text-gray-600',
        // 'dark:text-gray-400',
        // 'text-gray-900',
        // 'dark:text-gray-100',
        'bg-white',
        // 'dark:bg-gray-800',
    ],
};