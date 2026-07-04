<?php

namespace Ddys\Laravel\View\Components;

class Movies extends Widget
{
    public function __construct(array $params = [])
    {
        parent::__construct('movies', $params);
    }
}

