const mix = require('laravel-mix');
const fs = require('fs');
const path = require('path');

function walkFiles(dir, extensions, fileList = []) {
    if (!fs.existsSync(dir)) {
        return fileList;
    }

    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            walkFiles(fullPath, extensions, fileList);
            continue;
        }

        const ext = path.extname(entry.name).toLowerCase();
        if (!extensions.includes(ext)) {
            continue;
        }

        if (entry.name.endsWith('.min.js') || entry.name.endsWith('.min.css')) {
            continue;
        }

        fileList.push(fullPath.replace(/\\/g, '/'));
    }

    return fileList;
}

mix.js('resources/js/app.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', []);

const cssFiles = walkFiles('public/assets', ['.css']);
const jsFiles = walkFiles('public/assets', ['.js']);

cssFiles.forEach((source) => {
    const minifiedTarget = source.replace(/\.css$/i, '.min.css');
    mix.minify({ src: source, output: minifiedTarget });
});

jsFiles.forEach((source) => {
    const minifiedTarget = source.replace(/\.js$/i, '.min.js');
    mix.minify({ src: source, output: minifiedTarget });
});

mix.version([
    'public/js/app.js',
    'public/css/app.css',
    ...cssFiles,
    ...jsFiles,
    ...cssFiles.map((file) => file.replace(/\.css$/i, '.min.css')),
    ...jsFiles.map((file) => file.replace(/\.js$/i, '.min.js')),
]);
