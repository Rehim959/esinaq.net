<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Core\View;
use App\Services\MailService;

final class AuthController
{
    public function showRegister(): void
    {
        if (Auth::parentId()) {
            redirect('/valideyn');
        }
        View::render('auth/register', ['title' => __('parent_register_title')]);
    }

    public function register(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf'));
            redirect('/qeydiyyat');
        }

        $rateKey = RateLimiter::clientKey('register');
        if (RateLimiter::tooManyAttempts($rateKey, 5, 3600)) {
            Session::flash('error', __('err_rate_limit', ['n' => '60']));
            redirect('/qeydiyyat');
        }
        RateLimiter::hit($rateKey, 3600);

        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $patronymic = trim((string) ($_POST['patronymic'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phoneOp = trim((string) ($_POST['phone_op'] ?? ''));
        $phoneNum = trim((string) ($_POST['phone_num'] ?? ''));
        $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirmation'] ?? '');

        flash_old($_POST);

        if ($first === '' || $last === '' || $patronymic === '' || $email === '' || $password === '') {
            Session::flash('error', __('err_required'));
            redirect('/qeydiyyat');
        }

        $phone = build_az_phone($phoneOp, $phoneNum, $phoneRaw);
        if ($phone === null) {
            Session::flash('error', __('err_phone'));
            redirect('/qeydiyyat');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', __('err_email'));
            redirect('/qeydiyyat');
        }

        if (strlen($password) < 8) {
            Session::flash('error', __('err_password_len'));
            redirect('/qeydiyyat');
        }

        if ($password !== $password2) {
            Session::flash('error', __('err_password_match'));
            redirect('/qeydiyyat');
        }

        $pdo = Database::connection();
        $check = $pdo->prepare('SELECT id FROM parents WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            // Generic message — avoid email enumeration
            Session::flash('error', __('err_register_failed'));
            redirect('/qeydiyyat');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO parents (email, password_hash, first_name, last_name, patronymic, phone, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, NULL)'
        );
        $stmt->execute([$email, $hash, $first, $last, $patronymic, $phone]);

        clear_old();
        (new MailService())->welcomeParent($email, $first);

        Auth::attemptParent($email, $password);
        Session::flash('success', __('ok_register'));
        redirect('/valideyn');
    }

    public function showLogin(): void
    {
        if (Auth::parentId()) {
            redirect('/valideyn');
        }
        View::render('auth/login', ['title' => __('parent_login')]);
    }

    public function login(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/valideyn/giris');
        }

        $rateKey = RateLimiter::clientKey('parent_login');
        if (RateLimiter::tooManyAttempts($rateKey, 8, 900)) {
            $wait = RateLimiter::availableIn($rateKey, 900);
            Session::flash('error', __('err_rate_limit', ['n' => (string) max(1, (int) ceil($wait / 60))]));
            redirect('/valideyn/giris');
        }

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attemptParent($email, $password)) {
            RateLimiter::hit($rateKey, 900);
            Session::flash('error', __('err_login'));
            flash_old(['email' => $email]);
            redirect('/valideyn/giris');
        }

        RateLimiter::clear($rateKey);
        clear_old();
        redirect('/valideyn');
    }

    public function logout(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/');
        }
        Auth::logout();
        redirect('/');
    }

    public function showForgot(): void
    {
        View::render('auth/forgot', ['title' => __('forgot_password')]);
    }

    public function forgot(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/sifremi-unutdum');
        }

        $rateKey = RateLimiter::clientKey('forgot');
        if (RateLimiter::tooManyAttempts($rateKey, 5, 3600)) {
            Session::flash('error', __('err_rate_limit', ['n' => '60']));
            redirect('/sifremi-unutdum');
        }
        RateLimiter::hit($rateKey, 3600);

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id FROM parents WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            // Invalidate previous unused tokens
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE email = ? AND used_at IS NULL')
                ->execute([$email]);
            $token = generate_token(32);
            $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([$email, $token]);
            (new MailService())->passwordReset($email, $token);
        }

        Session::flash('success', __('ok_reset_sent'));
        redirect('/sifremi-unutdum');
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        View::render('auth/reset', ['title' => __('new_password'), 'token' => $token]);
    }

    public function reset(): void
    {
        if (!Session::verifyCsrf($_POST['_csrf'] ?? null)) {
            Session::flash('error', __('err_csrf_short'));
            redirect('/sifremi-unutdum');
        }

        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirmation'] ?? '');

        if (strlen($password) < 8 || $password !== $password2) {
            Session::flash('error', __('err_password_reset'));
            redirect('/sifre-berpa?token=' . urlencode($token));
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            Session::flash('error', __('err_reset_invalid'));
            redirect('/sifremi-unutdum');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE parents SET password_hash = ? WHERE email = ?')->execute([$hash, $row['email']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE email = ?')->execute([$row['email']]);

        Session::flash('success', __('ok_password_updated'));
        redirect('/valideyn/giris');
    }
}
