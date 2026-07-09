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

        if (isset($admin['is_active']) && !(int) $admin['is_active']) {
            return false;
        }

        $role = (string) ($admin['role'] ?? 'super_admin');
        if (!in_array($role, ['super_admin', 'moderator'], true)) {
            $role = 'super_admin';
        }

        Session::regenerate();
        Session::set('admin_id', (int) $admin['id']);
        Session::set('admin_name', $admin['full_name']);
        Session::set('admin_email', $admin['email']);
        Session::set('admin_role', $role);
        Session::remove('parent_id');
        Session::remove('child_id');
        Session::remove('child_token');
        Session::remove('child_name');
        Session::remove('parent_name');
        Session::remove('parent_email');
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

        Session::regenerate();
        Session::set('parent_id', (int) $parent['id']);
        Session::set('parent_name', person_full_name($parent));
        Session::set('parent_email', $parent['email']);
        Session::remove('admin_id');
        Session::remove('admin_name');
        Session::remove('admin_email');
        Session::remove('admin_role');
        Session::remove('child_id');
        Session::remove('child_token');
        Session::remove('child_name');
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

        $stored = (string) $child['password_hint'];
        $ok = false;

        // Prefer hashed passwords; keep legacy plaintext hints for existing rows.
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            $ok = password_verify($password, $stored);
        } else {
            $ok = hash_equals(mb_strtolower($stored), mb_strtolower(trim($password)));
            if ($ok) {
                // Upgrade legacy plaintext to hash on successful login
                $hash = password_hash(trim($password), PASSWORD_DEFAULT);
                Database::connection()->prepare('UPDATE children SET password_hint = ? WHERE id = ?')
                    ->execute([$hash, $child['id']]);
                $child['password_hint'] = $hash;
            }
        }

        if (!$ok) {
            return null;
        }

        Session::regenerate();
        Session::set('child_id', (int) $child['id']);
        Session::set('child_token', $token);
        Session::set('child_name', $child['first_name']);
        Session::remove('admin_id');
        Session::remove('admin_name');
        Session::remove('admin_email');
        Session::remove('admin_role');
        Session::remove('parent_id');
        Session::remove('parent_name');
        Session::remove('parent_email');
        return $child;
    }

    public static function adminId(): ?int
    {
        $id = Session::get('admin_id');
        return is_int($id) || is_numeric($id) ? (int) $id : null;
    }

    public static function adminRole(): string
    {
        $role = Session::get('admin_role');
        if (is_string($role) && in_array($role, ['super_admin', 'moderator'], true)) {
            return $role;
        }
        // Legacy sessions before role column: treat as super_admin
        return self::adminId() ? 'super_admin' : '';
    }

    public static function isSuperAdmin(): bool
    {
        return self::adminId() !== null && self::adminRole() === 'super_admin';
    }

    public static function isModerator(): bool
    {
        return self::adminId() !== null && self::adminRole() === 'moderator';
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

    public static function requireSuperAdmin(): void
    {
        self::requireAdmin();
        if (!self::isSuperAdmin()) {
            Session::flash('error', __('err_forbidden'));
            redirect('/admin');
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
