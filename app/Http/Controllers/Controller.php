<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Provides $this->authorize() for controllers that check a policy against a
    // specific model instance (for example "can this customer see this ticket").
    use AuthorizesRequests;
}
