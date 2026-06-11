// tailwind.config.js
const brand = {
    50:'#fff3e8',100:'#ffe0c2',200:'#ffc184',300:'#ff9a3d',400:'#ff750f',
    500:'#f26500',600:'#d95500',700:'#ad3f00',800:'#733000',900:'#391800',
};

export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: brand,
                // se quiser que TUDO que é "blue-*" do projeto use sua paleta:
                 blue: brand,
            },
        },
    },
    plugins: [],
};
