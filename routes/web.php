<?php

use Myhealth\Classes\Route;
use Myhealth\Classes\LoginState;
use Myhealth\Classes\Permission;
use Myhealth\Controllers\FyiController;
use Myhealth\Controllers\LabController;
use Myhealth\Controllers\MfaController;
use Myhealth\Controllers\AjaxController;
use Myhealth\Controllers\HomeController;
use Myhealth\Controllers\TotpController;
use Myhealth\Controllers\UserController;
use Myhealth\Controllers\ClaimController;
use Myhealth\Controllers\EventController;
use Myhealth\Controllers\LoginController;
use Myhealth\Controllers\TrackController;
use Myhealth\Controllers\SearchController;
use Myhealth\Controllers\AccountController;
use Myhealth\Controllers\ContactController;
use Myhealth\Controllers\DownloadController;
use Myhealth\Controllers\PasswordController;
use Myhealth\Controllers\ReferralController;
use Myhealth\Controllers\HeartbeatController;
use Myhealth\Controllers\VitalStatsController;
use Myhealth\Controllers\EmailConfirmController;
use Myhealth\Controllers\ImmunizationController;
use Myhealth\Controllers\RegistrationController;
use Myhealth\Controllers\ForgotPasswordController;

/**
 * Support for old bookmarks
 */
Route::get(['login.asp', 'index.asp'], function() {
    redirect(str_replace('.asp', '', Route::getCurrentRoute()));
});

Route::get(['/index', '/home'], [ HomeController::class, 'index' ]);

/**
 * Special route for index.php to prevent "index.php" from showing
 * in the address bar. Re-route it to home.
 */
Route::get('/', function() {
    if (scriptName() === 'index.php') {
        redirect('home');
    }
    else {
        (new HomeController())->index();
    }
});

Route::get('/login', [ LoginController::class, 'login' ]);
Route::get(['/logoff','/logout'], [ LoginController::class, 'logout' ]);
Route::get('/agreement', [ LoginController::class, 'agreement' ]);

Route::get('/forgot-password', [ ForgotPasswordController::class, 'forgotPassword' ]);
Route::get('/verify-reset', [ ForgotPasswordController::class, 'verifyReset' ]);

Route::get('/register', function() { redirect('register1', true); });
Route::any('/register1', [ RegistrationController::class, 'register1' ]);
Route::any('/register2', [ RegistrationController::class, 'register2' ]);
Route::any('/register3', [ RegistrationController::class, 'register3' ]);
Route::any('/save-registration', [ RegistrationController::class, 'save' ]);
Route::get('/register-complete', [ RegistrationController::class, 'registerComplete']);
Route::get('/check-username', [ RegistrationController::class, 'checkUsername' ]);

Route::get('/email-confirm', [ EmailConfirmController::class, 'confirm' ]);

Route::get('/privacy', function() {
    render('privacy', 'Privacy', [ 'client' => client() ]);
});

Route::get('/heartbeat', [ HeartbeatController::class, 'heartbeat' ]);

/**
 * These routes can be in three different modes:
 * authenticated (My Account)
 * partially authenticated (password expired, change next)
 * not logged in at all (forgot password)
 */
Route::any('/change-password-cancel', [ PasswordController::class, 'changePasswordCancel' ]);
Route::any('/change-password-action', [ PasswordController::class, 'changePasswordAction' ]);

if (authenticated()) {
    Route::get('/new-member', [ HomeController::class, 'newMember' ]);
    Route::get('/my-claims', [ ClaimController::class, 'myClaims' ]);
    Route::get('/claim-detail', [ ClaimController::class, 'claimDetail' ]);
    Route::get('/my-referrals', [ ReferralController::class, 'myReferrals' ]);
    Route::get('/referral-detail', [ ReferralController::class, 'referralDetail' ]);
    Route::get('/reprint', [ ReferralController::class, 'reprint' ]);

    Route::get('/my-vital-stats', [ VitalStatsController::class, 'myVitalStats' ]);
    
    Route::get('/track-vitals', [ TrackController::class, 'trackVitals' ]);
    Route::any('/track-vitals-save', [ TrackController::class, 'saveVitals' ]);
    Route::get('/track-glucose', [ TrackController::class, 'trackGlucose' ]);
    Route::any('/track-glucose-save', [ TrackController::class, 'saveGlucose' ]);
    Route::get('/track-lab-results', [ TrackController::class, 'trackLabResults' ]);
    Route::any('/track-lab-results-save', [ TrackController::class, 'saveLabResults' ]);
    
    Route::get('/my-immunizations', [ ImmunizationController::class, 'myImmunizations' ]);
    Route::any('/immunization-save', [ ImmunizationController::class, 'saveImmunization' ]);

    Route::get('/my-labs', [ LabController::class, 'myLabs' ]);
    Route::get('/lab-detail', [ LabController::class, 'labDetail' ]);

    Route::get('/my-account', [ AccountController::class, 'myAccount' ]);
    Route::any('/my-account-save', [ AccountController::class, 'saveMyAccount' ]);

    Route::get('/doctor-search', [ SearchController::class, 'doctorSearch' ]);

    Route::get('/contact', [ ContactController::class, 'start' ]);
    Route::get('/contact-send', [ ContactController::class, 'send' ]);

    Route::get('/fyi', [ FyiController::class, 'messages' ]);
    Route::any('/fyi-save', [ FyiController::class, 'save' ]);
    Route::any('/fyi-delete', [ FyiController::class, 'delete' ]);
    
    Route::get('/view-logs', [ EventController::class, 'viewLogs' ]);

    /**
     * Services
     */
    Route::get('/service/download', [ DownloadController::class, 'download' ]);

    if (Permission::isAdmin()) {
        Route::any('/fyi-edit', [ FyiController::class, 'edit' ]);

        /**
         * User edit functions
         */
        Route::get('/users', [ UserController::class, 'users' ]);
        Route::get('/user-edit', [ UserController::class, 'userEdit' ]);
        Route::any('/user-save', [ UserController::class, 'userSave' ]);
        Route::any('/revoke-mfa', [ UserController::class, 'revokeMfa' ]);
        Route::any('/enable-disable-account', [ AjaxController::class, 'enableDisableAccount' ]);
        Route::any('/get-member-info', [ AjaxController::class, 'getMemberInfo' ]);

        Route::get('/user-review', [ UserController::class, 'userReview' ]);
    }

    if (Permission::isDeveloper()) {
        Route::get('/sysinfo', function() {
            render('sysinfo', 'System Information', [ 'client' => client() ]);
        });
    }

    /**
     * Auth functions
     */
    Route::get('/change-password', [ PasswordController::class, 'changePassword' ]);
    Route::get('/mfa', [ MfaController::class, 'mfa' ]);
    Route::any('/mfa-verify',  [ MfaController::class, 'verify' ]);
    Route::get('/totp', [ TotpController::class, 'totp' ]);

    /**
     * These pages will seem to not exist if there's no mfauser in the session
     */
    if (_session('mfauser') !== '') {
        Route::any('/mfa-select',  [ MfaController::class, 'mfaSelect' ]);
        Route::any('/totp-verify', [ TotpController::class, 'verify' ]);
        Route::get('/totp-setup-success', [ TotpController::class, 'setupSuccess' ]);
    }
}

/**
 * Authenticated, but not fully. Usually this is due to
 * the password needing to be changed, or MFA needing to
 * be setup on login.
 */
if (partially_authenticated()) {
    Route::get('/change-password', [ PasswordController::class, 'changePassword' ]);
    Route::get('/mfa', [ MfaController::class, 'mfa' ]);
    Route::any('/mfa-verify',  [ MfaController::class, 'verify' ]);
    Route::get('/totp', [ TotpController::class, 'totp' ]);

    /**
     * These pages will seem to not exist if there's no mfauser in the session
     */
    if (_session('mfauser') !== '') {
        Route::any('/mfa-select',  [ MfaController::class, 'mfaSelect' ]);
        Route::any('/totp-verify', [ TotpController::class, 'verify' ]);
        Route::get('/totp-setup-success', [ TotpController::class, 'setupSuccess' ]);
    }
}

/**
 * Special case. We don't even want to consider this partically authenticated.
 */
if (_session('login_state') === LoginState::NOT_CONFIRMED) {
    Route::get('/resend-confirmation', [ EmailConfirmController::class, 'resendEmail' ]);
}

/**
 * If we get this far, it's not a valid route
 */
http_response_code(404);
render('404', '404 Page not found');