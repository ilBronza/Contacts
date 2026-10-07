<?php

$root = dirname(__DIR__);
// Keep Testbench's generated manifests out of the shared vendor directory.
$runtime = sys_get_temp_dir() . '/contacts-tests-' . getmypid();
if (! is_dir($runtime))
    mkdir($runtime, 0777, true);
foreach (['APP_SERVICES_CACHE' => 'services.php', 'APP_PACKAGES_CACHE' => 'packages.php', 'APP_CONFIG_CACHE' => 'config.php', 'APP_ROUTES_CACHE' => 'routes.php', 'APP_EVENTS_CACHE' => 'events.php'] as $key => $file) {
    $_ENV[$key] = $_SERVER[$key] = $runtime . '/' . $file;
    putenv($key . '=' . $runtime . '/' . $file);
}
$autoload = $root . '/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
} else {
    // The shared workspace already contains the resolved vendor tree.
    $shared = dirname($root) . '/.ibtest/vendor/autoload.php';
    if (! is_file($shared))
        throw new RuntimeException('No Composer autoloader or shared .ibtest vendor found.');

    $loader = require $shared;
    foreach (glob(dirname($root) . '/*/composer.json') as $manifest) {
        $directory = dirname($manifest);
        $composer = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
        foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $paths) {
            $loader->addPsr4($namespace, array_map(static fn ($path) => $directory . '/' . $path, (array) $paths), true);
        }
        foreach ($composer['autoload']['files'] ?? [] as $file)
            require_once $directory . '/' . $file;
    }
    $loader->addPsr4('IlBronza\\Contacts\\Tests\\', __DIR__, true);
    $loader->addPsr4('App\\', __DIR__ . '/Fixtures/App', true);
}
