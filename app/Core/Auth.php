<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function attemptAdmin(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        Session::set('admin_id', (int) $admin['id']);
        Session::set('admin_name', $admin['full_name']);
        Session::set('admin_email', $admin['email']);
        Session::remove('parent_id');
        Session::remove('child_id');
        return true;
    }

    public static function attemptParent(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare('SELECT * FROM parents WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $parent = $stmt->fetch();

        if (!$parent || !password_verify($password, $parent['password_hash'])) {
            return false;
        }

        Session::set('parent_id', (int) $parent['id']);
        Session::set('parent_name', $parent['first_name'] . ' ' . $parent['last_name']);
        Session::set('parent_email', $parent['email']);
        Session::remove('admin_id');
        Session::remove('child_id');
        return true;
    }

    public static function attemptChild(string $token, string $password): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM children WHERE access_token = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$token]);
        $child = $stmt->fetch();

        if (!$child) {
            return null;
        }

        if (!hash_equals(mb_strtolower($child['password_hint']), mb_strtolower(trim($password)))) {
            return null;
        }

        Session::set('child_id', (int) $child['id']);
        Session::set('child_token', $token);
        Session::set('child_name', $child['first_name']);
        Session::remove('admin_id');
        Session::remove('parent_id');
        return $child;
    }

    public static function adminId(): ?int
    {
        $id = Session::get('admin_id');
        return is_int($id) || is_numeric($id) ? (int) $id : null;
    }

    public static function parentId(): ?int
    {
        $id = Session::get('parent_id');
        return is_int($id) || is_numeric($id) ? (int) $id : null;
    }

    public static function childId(): ?int
    {
        $id = Session::get('child_id');
        return is_int($id) || is_numeric($id) ? (int) $id : null;
    }

    public static function requireAdmin(): void
    {
        if (!self::adminId()) {
            redirect('/admin/login');
        }
    }

    public static function requireParent(): void
    {
        if (!self::parentId()) {
            redirect('/valideyn/giris');
        }
    }

    public static function logout(): void
    {
        $locale = Session::get('locale');
        Session::destroy();
        Session::start();
        if (is_string($locale) && in_array($locale, ['az', 'ru'], true)) {
            Session::set('locale', $locale);
            Lang::setLocale($locale);
        }
    }
}
