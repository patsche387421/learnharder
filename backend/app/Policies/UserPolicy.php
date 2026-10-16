<?php

namespace App\Policies;

/**
 * Konten verwaltet nur der Admin (Gate::before). Die Schutzregeln für Admins stehen in App\Support\Accounts (E-027).
 */
class UserPolicy extends SchoolStructurePolicy {}
