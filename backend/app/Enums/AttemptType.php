<?php

namespace App\Enums;

/**
 * Art einer Lerneinheit: zu einem Thema oder Herausforderung aus mehreren Themen (E-045).
 * Muss zu quiz_attempts_attempt_type_check passen.
 */
enum AttemptType: string
{
    case Standard = 'standard';
    case Challenge = 'challenge';
}
