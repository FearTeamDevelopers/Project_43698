<?php

namespace THCFrame\Events;

use THCFrame\Events\Observable;

/**
 *  Basic interface for observer objects
 */
interface ObserverInterface
{
    public function update(Observable $observable);
}
