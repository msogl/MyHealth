<?php

namespace Myhealth\Classes;

class LoginState
{
    public const LOGGED_IN = 1;
    public const LOGIN_FAILED = 2;
    public const PASSWORD_CHANGE_REQUIRED = 3;
    public const PASSWORD_EXPIRED = 4;
    public const NOT_CONFIRMED = 5;
    public const LOCKED = 6;
    public const SUSPENDED = 7;
    public const MFA_REQUIRED = 8;
    public const AGREEMENT_REQUIRED = 9;
}