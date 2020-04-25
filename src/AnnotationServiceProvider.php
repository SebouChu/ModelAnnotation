<?php

namespace sebouchu\ModelAnnotation;

use Illuminate\Support\ServiceProvider;
use Log;

class AnnotationServiceProvider extends ServiceProvider {
    protected $commands = [
        'sebouchu\ModelAnnotation\AnnotateCommand'
    ];

    public function register() {
        $this->commands($this->commands);
    }
}