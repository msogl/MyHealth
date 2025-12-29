<?php

namespace Myhealth\Daos;

#[\AllowDynamicProperties]
class AccountDAO
{
    public $AccountID = 0;
    public $Firstname = '';
    public $MemberID = 0;
    public $Username = '';
    public $Email = '';
    public $Password = '';
    public $Nickname = '';
    public $CreatedDateTime = "";
    public $LastUpdateDateTime = '';
    public $NextPasswordChangeDate = '';
    public $ChangeNext = 0;
    public $Confirmed = 0;
    public $Salt = '';
    public $Version = '';
    public $ResetCode = '';
    public $ResetStartDate = '';
    public $ResetRequested = 0;
    public $MFAEnabled = 0;
    public $MFACode = null;
    public $MFAStartDate = null;
    public $OTPSecret = null;
    public $Active = 0;
    public $ConfirmStartDate = null;
}