<?php

namespace Ddys\Laravel\View\Components;

class RequestForm extends Widget
{
    public function __construct(array $params = [])
    {
        parent::__construct('request_form', $params);
    }
}

