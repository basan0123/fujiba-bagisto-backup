<?php

// vendor/ is shared with the live tree, so its PSR-4 map points at the live
// package. Prepend a loader that resolves this checkout's package + tests first.
$loader = require __DIR__.'/../../../../vendor/autoload.php';
$loader->addPsr4('Fujiba\\AutoPoster\\', __DIR__.'/../src', true);
$loader->addPsr4('Fujiba\\AutoPoster\\Tests\\', __DIR__, true);
