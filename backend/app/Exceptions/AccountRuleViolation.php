<?php

namespace App\Exceptions;

use RuntimeException;

/** Eine Schutzregel für Konten verhindert die Änderung (E-027). Die Meldung ist für Menschen gedacht. */
class AccountRuleViolation extends RuntimeException {}
