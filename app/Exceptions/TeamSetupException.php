<?php

namespace App\Exceptions;

/** A team-setup rule the client broke; same shape as a quiz refusal (error key, status, context). */
class TeamSetupException extends QuizRuleException
{
}
