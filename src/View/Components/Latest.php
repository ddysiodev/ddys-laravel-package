<?php

namespace Ddys\Laravel\View\Components;

class Latest extends Widget
{
    public function __construct(array $params = [])
    {
        parent::__construct('latest', $params);
    }
}

