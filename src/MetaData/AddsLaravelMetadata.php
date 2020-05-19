<?php

namespace DigitalRisks\Lese\MetaData;

trait AddsLaravelMetadata
{
    /**
     * @metadata
     */
    public function collectLaravelMetadata()
    {
        return [
            'laravel' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
            ],
        ];
    }
}
