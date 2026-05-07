export default {
    content: ["./src/Resources/**/*.blade.php", "./src/Resources/**/*.{vue,js}"],

    theme: {
        extend: {}
    },
    
    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /pos-/,
        },
        {
            pattern: /icon-/,
        }
    ],
};
