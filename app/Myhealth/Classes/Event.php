<?php

namespace Myhealth\Classes;

class Event
{
	const EVENT_LOGIN_SUCCESS = 1;
	const EVENT_LOGIN_FAIL = 2;
	const EVENT_LOGOFF = 3;
	const EVENT_ACCOUNT_CREATED = 4;
	const EVENT_EMAIL_CONFIRMED = 5;
	const EVENT_PASSWORD_CHANGE = 6;
	const EVENT_CLAIM_BROWSE = 7;
	const EVENT_REFERRAL_BROWSE = 8;
	const EVENT_CLAIM_VIEW = 9;
	const EVENT_CLAIM_VIEW_FAILED = 10;
	const EVENT_REFERRAL_VIEW = 11;
	const EVENT_REFERRAL_VIEW_FAILED = 12;
	const EVENT_PASSWORD_EXPIRED = 13;
	const EVENT_PASSWORD_CHANGE_REQUIRED = 14;
	const EVENT_PASSWORD_CHANGED = 15;
	const EVENT_PASSWORD_RESET = 16;
	const EVENT_IMMUNIZATIONS_BROWSE = 17;
	const EVENT_VITAL_STATS_VIEW = 18;
	const EVENT_TRACK_VITAL_STATS = 19;
	const EVENT_TRACK_LAB_RESULTS = 20;
	const EVENT_ACCOUNT_UPDATED = 21;
	const EVENT_REF_AUTH_VIEW = 22;
	const EVENT_TRACK_GLUCOSE = 23;
	const EVENT_TRACK_IMMUNIZATION = 24;
	const EVENT_LAB_BROWSE = 25;
	const EVENT_LAB_VIEW = 26;
	const EVENT_LAB_VIEW_FAILED = 27;
	const EVENT_MFA_SUCCESS = 28;
	const EVENT_MFA_FAIL = 29;
	const EVENT_MFA_ENABLED = 30;
	const EVENT_MFA_DISABLED = 31;
	const EVENT_MFA_REVOKED = 32;
	const EVENT_MFA_TRUST_ENABLED = 33;
	const EVENT_MFA_TRUST = 34;
	const EVENT_ACCOUNT_LOCKED = 35;
    const EVENT_SMS_SEND_SUCCESS = 36;
	const EVENT_SMS_SEND_FAIL = 37;
    const EVENT_FILE_DOWNLOAD_SUCCESS = 38;
    const EVENT_FILE_DOWNLOAD_FAILED = 39;
    const EVENT_AGREEMENT_REQUIRED = 40;
	const EVENT_UNAUTHORIZED = 998;
	const EVENT_DEBUG = 999;

	public static $description = array(
		self::EVENT_LOGIN_SUCCESS=>'Logged in',
        self::EVENT_LOGIN_FAIL=>'Login failed',
        self::EVENT_LOGOFF=>'Logged off',
		self::EVENT_ACCOUNT_CREATED=>'Account created',
        self::EVENT_EMAIL_CONFIRMED=>'Email address confirmed',
        self::EVENT_PASSWORD_CHANGE=>'Password changed',
        self::EVENT_CLAIM_BROWSE=>'Viewed claim history',
        self::EVENT_REFERRAL_BROWSE=>'Viewed referral history',
        self::EVENT_CLAIM_VIEW=>'Viewed claim',
        self::EVENT_CLAIM_VIEW_FAILED=>'Viewed claim failed',
        self::EVENT_REFERRAL_VIEW=>'Viewed referral',
        self::EVENT_REFERRAL_VIEW_FAILED=>'Viewed referral failed',
        self::EVENT_PASSWORD_EXPIRED=>'Password expired',
        self::EVENT_PASSWORD_CHANGE_REQUIRED=>'Password change required',
        self::EVENT_PASSWORD_CHANGED=>'Password changed',
        self::EVENT_PASSWORD_RESET=>'Password reset requested',
        self::EVENT_IMMUNIZATIONS_BROWSE=>'Viewed immunizations',
        self::EVENT_VITAL_STATS_VIEW=>'Viewed vital stats',
        self::EVENT_TRACK_VITAL_STATS=>'Tracked vital stats',
        self::EVENT_TRACK_LAB_RESULTS=>'Tracked lab results',
        self::EVENT_ACCOUNT_UPDATED=>'Account updated',
        self::EVENT_REF_AUTH_VIEW=>'Viewed Ref/Auth',
        self::EVENT_TRACK_GLUCOSE=>'Tracked glucose',
        self::EVENT_TRACK_IMMUNIZATION=>'Tracked immunization',
        self::EVENT_LAB_BROWSE=>'Viewed lab results list',
        self::EVENT_LAB_VIEW=>'Viewed lab detail',
        self::EVENT_LAB_VIEW_FAILED=>'Viewed lab detail failed',
		self::EVENT_MFA_SUCCESS=>'MFA succeeded',
		self::EVENT_MFA_FAIL=>'MFA failed',
		self::EVENT_MFA_ENABLED=>'MFA enabled',
		self::EVENT_MFA_DISABLED=>'MFA disabled',
		self::EVENT_MFA_REVOKED=>'MFA revoked',
		self::EVENT_MFA_TRUST_ENABLED=>'MFA trust browser enabled',
		self::EVENT_MFA_TRUST=>'MFA succeeded (browser trust)',
		self::EVENT_ACCOUNT_LOCKED=>'Account locked',
        self::EVENT_SMS_SEND_SUCCESS=>'SMS Send Success',
		self::EVENT_SMS_SEND_FAIL=>'SMS Send Fail',
		self::EVENT_FILE_DOWNLOAD_SUCCESS=>'File downloaded',
        self::EVENT_FILE_DOWNLOAD_FAILED=>'File download failed',
        self::EVENT_AGREEMENT_REQUIRED=>"Agreement required",
		self::EVENT_UNAUTHORIZED=>'Unauthorized',
        self::EVENT_DEBUG=>'Debug',
	);
}