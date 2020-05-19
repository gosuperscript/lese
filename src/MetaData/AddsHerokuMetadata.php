<?php

namespace DigitalRisks\Lese\MetaData;

trait AddsHerokuMetadata
{
    /**
     * @metadata
     */
    public function collectHerokuMetadata()
    {
        return ['heroku' => collect($_ENV)->filter(function ($value, $key) {
            return strpos($key, 'HEROKU_') === 0;
        })->toArray()];
    }
}
