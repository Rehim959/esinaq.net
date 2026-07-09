<div class="page">
    <table class="table">
        <thead><tr><th>ID</th><th>Ad</th><th>E-poçt</th><th>Telefon</th><th>Uşaq</th><th>Tarix</th></tr></thead>
        <tbody>
        <?php foreach ($parents as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
                <td><?= e($p['email']) ?></td>
                <td><?= e($p['phone'] ?? '—') ?></td>
                <td><?= (int)$p['child_count'] ?></td>
                <td><?= e(format_date($p['created_at'], 'd.m.Y')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
