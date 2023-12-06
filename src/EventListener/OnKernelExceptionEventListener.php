<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener]
class OnKernelExceptionEventListener
{
    public function __invoke(ExceptionEvent $exceptionEvent)
    {
        dump($exceptionEvent->getThrowable());
    }
}
