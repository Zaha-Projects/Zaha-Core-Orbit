<?php

// Static compilation only: no application bootstrap, template rendering, or database access.
require dirname(__DIR__).'/vendor/autoload.php';

$root = dirname(__DIR__);
$compiler = new Illuminate\View\Compilers\BladeCompiler(
    new Illuminate\Filesystem\Filesystem(),
    sys_get_temp_dir().'/ramadan-blade-static'
);
$views = [
    '_form', '_reference_picker', '_planning_collections',
    '_execution_need_details', '_historic_needs', '_supporting_entity',
];

foreach ($views as $view) {
    $path = 'resources/views/pages/events/ramadan/'.$view.'.blade.php';
    $compiled = $compiler->compileString(file_get_contents($root.'/'.$path));
    // PHP lint alone misses malformed PHP open tags emitted as literal HTML.
    foreach (token_get_all($compiled, TOKEN_PARSE) as $token) {
        if (is_array($token) && $token[0] === T_INLINE_HTML
            && preg_match('/<\?php|@(php|endphp|error|enderror|foreach|endforeach)\b|\{\{/', $token[1])) {
            fwrite(STDERR, "FAIL: Uncompiled PHP/Blade in {$path} near compiled line {$token[2]}.\n");
            exit(1);
        }
    }
    echo "PASS: {$path}\n";
}
