<?php

namespace App\Modules\Movement\Exceptions;

use DomainException;

/** A request transition that §8.4 does not allow, or whose guard fails. The message is user-facing. */
class IllegalMovementTransition extends DomainException
{
}
