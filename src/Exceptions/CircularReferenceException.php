<?php

namespace Nevadskiy\Tree\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CircularReferenceException extends RuntimeException
{
    /**
     * Make a new exception instance.
     */
    public function __construct(Model $model)
    {
        parent::__construct(sprintf('Circular reference detected for model [%s].', $model->getKey()));
    }
}
