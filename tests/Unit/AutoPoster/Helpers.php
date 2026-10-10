<?php

namespace Tests\Unit\AutoPoster;

use ReflectionMethod;
use ReflectionProperty;

function callProtected(object $obj, string $method, array $args = [])
{
    $m = new ReflectionMethod($obj, $method);
    $m->setAccessible(true);

    return $m->invokeArgs($obj, $args);
}

function setProtected(object $obj, string $prop, $value): void
{
    $p = new ReflectionProperty($obj, $prop);
    $p->setAccessible(true);
    $p->setValue($obj, $value);
}
