<?php
require_once __DIR__ . '/../app/Core/Autoloader.php';
\App\Core\Autoloader::register(__DIR__ . '/../app');
require_once __DIR__ . '/../app/Core/helpers.php';
\App\Core\Env::load(__DIR__ . '/../.env');

$action = $argv[1] ?? 'status';

if ($action === 'status') {
    $userCount = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
    $tenantCount = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM tenants")['cnt'];
    $funnelCount = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM funnels")['cnt'];
    echo json_encode([
        'status' => 'ok',
        'users' => (int)$userCount,
        'tenants' => (int)$tenantCount,
        'funnels' => (int)$funnelCount,
    ]);
    exit(0);
}

if ($action === 'get_latest_otp') {
    $email = $argv[2] ?? '';
    $row = \App\Core\Database::fetchOne("SELECT otp_code FROM email_verifications WHERE email = ? ORDER BY id DESC LIMIT 1", [$email]);
    echo json_encode([
        'found' => (bool)$row,
        'otp_code' => $row['otp_code'] ?? null,
    ]);
    exit(0);
}

if ($action === 'check_funnel') {
    $slug = $argv[2] ?? '';
    $funnel = \App\Core\Database::fetchOne("SELECT * FROM funnels WHERE slug = ?", [$slug]);
    if (!$funnel) {
        echo json_encode(['found' => false]);
        exit(0);
    }
    $nodes = \App\Core\Database::query("SELECT id, node_type, title FROM funnel_nodes WHERE funnel_id = ?", [$funnel['id']]);
    $edges = \App\Core\Database::query("SELECT id, source_node_id, target_node_id FROM funnel_edges WHERE funnel_id = ?", [$funnel['id']]);
    echo json_encode([
        'found' => true,
        'funnel' => $funnel,
        'nodes_count' => count($nodes),
        'edges_count' => count($edges),
        'nodes' => $nodes,
    ]);
    exit(0);
}

if ($action === 'check_lead') {
    $email = $argv[2] ?? '';
    $val = \App\Core\Database::fetchOne("SELECT * FROM form_submission_values WHERE value_text = ?", [$email]);
    if (!$val) {
        $sub = \App\Core\Database::fetchOne("SELECT * FROM form_submissions ORDER BY id DESC LIMIT 1");
        echo json_encode([
            'found' => (bool)$sub,
            'submission' => $sub,
            'by' => 'latest'
        ]);
        exit(0);
    }
    $sub = \App\Core\Database::fetchOne("SELECT * FROM form_submissions WHERE id = ?", [$val['form_submission_id']]);
    $vals = \App\Core\Database::query("SELECT * FROM form_submission_values WHERE form_submission_id = ?", [$val['form_submission_id']]);
    echo json_encode([
        'found' => true,
        'submission' => $sub,
        'values_count' => count($vals),
    ]);
    exit(0);
}

if ($action === 'check_email_log') {
    $email = $argv[2] ?? '';
    $log = \App\Core\Database::fetchOne("SELECT * FROM email_logs WHERE recipient_email = ? ORDER BY id DESC LIMIT 1", [$email]);
    echo json_encode([
        'found' => (bool)$log,
        'log' => $log,
    ]);
    exit(0);
}

if ($action === 'ensure_test_tenant') {
    $email = 'template_user@filterfunnel.test';
    $password = 'Password123!';
    $user = \App\Core\Database::fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    if (!$user) {
        \App\Core\Database::query(
            "INSERT INTO users (name, email, password_hash, email_verified_at, is_platform_admin, status) VALUES (?, ?, ?, NOW(), 0, 'active')",
            ['Template Tester', $email, password_hash($password, PASSWORD_BCRYPT)]
        );
        $userId = \App\Core\Database::getConnection()->lastInsertId();
    } else {
        $userId = $user['id'];
        \App\Core\Database::query(
            "UPDATE users SET password_hash = ?, email_verified_at = NOW(), is_platform_admin = 0, status = 'active' WHERE id = ?",
            [password_hash($password, PASSWORD_BCRYPT), $userId]
        );
    }

    $membership = \App\Core\Database::fetchOne("SELECT * FROM tenant_memberships WHERE user_id = ?", [$userId]);
    if (!$membership) {
        $slug = 'template-ws-' . time();
        \App\Core\Database::query(
            "INSERT INTO tenants (name, slug, status) VALUES (?, ?, 'active')",
            ['Template Test Workspace', $slug]
        );
        $tenantId = \App\Core\Database::getConnection()->lastInsertId();
        \App\Core\Database::query(
            "INSERT INTO tenant_memberships (tenant_id, user_id, role) VALUES (?, ?, 'owner')",
            [$tenantId, $userId]
        );
    }
    echo json_encode(['status' => 'ready', 'email' => $email, 'password' => $password]);
    exit(0);
}

if ($action === 'integrity_check') {
    $orphanedNodes = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM funnel_nodes fn LEFT JOIN funnels f ON fn.funnel_id = f.id WHERE f.id IS NULL")['cnt'];
    $orphanedEdges = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM funnel_edges fe LEFT JOIN funnels f ON fe.funnel_id = f.id WHERE f.id IS NULL")['cnt'];
    $orphanedMembers = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM tenant_memberships tm LEFT JOIN users u ON tm.user_id = u.id WHERE u.id IS NULL")['cnt'];
    $orphanedSubmissions = \App\Core\Database::fetchOne("SELECT COUNT(*) as cnt FROM form_submissions fs LEFT JOIN funnels f ON fs.funnel_id = f.id WHERE f.id IS NULL")['cnt'];

    echo json_encode([
        'orphaned_nodes' => (int)$orphanedNodes,
        'orphaned_edges' => (int)$orphanedEdges,
        'orphaned_members' => (int)$orphanedMembers,
        'orphaned_submissions' => (int)$orphanedSubmissions,
        'integrity_passed' => ($orphanedNodes == 0 && $orphanedEdges == 0 && $orphanedMembers == 0 && $orphanedSubmissions == 0),
    ]);
    exit(0);
}
