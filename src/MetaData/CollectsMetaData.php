<?php

namespace DigitalRisks\Lese\MetaData;

use ReflectionClass;

trait CollectsMetaData
{
    public function collectMetaData(): array
    {
        return collect((new ReflectionClass($this))->getMethods())
            ->filter(function ($method) {
                return strpos($method->getDocComment(), '@metadata') !== false;
            })
            ->flatMap(function ($method) {
                return $method->invoke($this);
            })
            ->all();
    }
}
