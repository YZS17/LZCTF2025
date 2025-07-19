<?php
session_start();

// Logout handling - must be before any output
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// 简单的认证机制
$isAuthenticated = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 硬编码的管理员凭据（故意设置为弱密码）
    if ($username === 'admin' && $password === 'docuview123') {
        $_SESSION['admin_logged_in'] = true;
        $isAuthenticated = true;
    } else {
        $error = 'Invalid credentials';
    }
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    $isAuthenticated = true;
}

// 获取系统信息
function getSystemInfo() {
    return [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
        'loaded_extensions' => get_loaded_extensions(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

// 获取缓存统计
function getCacheStats() {
    $cacheDir = '/tmp/docuview_cache';
    $stats = [
        'cache_enabled' => is_dir($cacheDir),
        'cache_files' => 0,
        'total_size' => 0,
        'last_cleanup' => 'Never'
    ];
    
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        $stats['cache_files'] = count($files);
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
        }
    }
    
    return $stats;
}

// 获取最近的预览日志
function getRecentPreviews() {
    // 模拟的预览日志
    return [
        [
            'timestamp' => '2024-12-19 10:30:15',
            'url' => 'https://docs.google.com/document/d/example',
            'format' => 'auto',
            'status' => 'success',
            'ip' => '192.168.1.100'
        ],
        [
            'timestamp' => '2024-12-19 10:25:42',
            'url' => 'https://drive.google.com/file/d/example',
            'format' => 'image',
            'status' => 'success',
            'ip' => '10.0.0.50'
        ],
        [
            'timestamp' => '2024-12-19 10:20:18',
            'url' => 'https://example.com/malicious.php',
            'format' => 'text',
            'status' => 'blocked',
            'ip' => '172.16.0.10'
        ]
    ];
}

if ($isAuthenticated) {
    $systemInfo = getSystemInfo();
    $cacheStats = getCacheStats();
    $recentPreviews = getRecentPreviews();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DocuView</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
        }
        
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .login-form {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 400px;
            margin: 50px auto;
        }
        
        .info-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
        }
        
        .status-success {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-blocked {
            color: #dc3545;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-file-alt text-primary me-2"></i>
                DocuView
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Home</a>
                <?php if ($isAuthenticated): ?>
                    <a class="nav-link" href="?logout=1">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (!$isAuthenticated): ?>
        <!-- Login Form -->
        <div class="admin-header">
            <div class="container text-center">
                <h1><i class="fas fa-shield-alt me-3"></i>Admin Panel</h1>
                <p class="lead">Secure access to DocuView administration</p>
            </div>
        </div>
        
        <div class="container">
            <div class="login-form">
                <h3 class="text-center mb-4">Administrator Login</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Default credentials: admin / docuview123
                    </small>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Admin Dashboard -->
        <div class="admin-header">
            <div class="container">
                <h1><i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard</h1>
                <p class="lead">DocuView System Management</p>
            </div>
        </div>
        
        <div class="container my-5">
            <!-- Statistics Cards -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-4">
                        <i class="fas fa-file-alt text-primary fa-3x mb-3"></i>
                        <h4><?= $cacheStats['cache_files'] ?></h4>
                        <p class="text-muted">Cached Documents</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-center p-4">
                        <i class="fas fa-hdd text-success fa-3x mb-3"></i>
                        <h4><?= number_format($cacheStats['total_size'] / 1024, 1) ?>KB</h4>
                        <p class="text-muted">Cache Size</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-center p-4">
                        <i class="fas fa-server text-warning fa-3x mb-3"></i>
                        <h4><?= $systemInfo['php_version'] ?></h4>
                        <p class="text-muted">PHP Version</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-center p-4">
                        <i class="fas fa-memory text-info fa-3x mb-3"></i>
                        <h4><?= $systemInfo['memory_limit'] ?></h4>
                        <p class="text-muted">Memory Limit</p>
                    </div>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="row mb-5">
                <div class="col-lg-6">
                    <div class="info-table">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <tr>
                                    <th>Server Software</th>
                                    <td><?= htmlspecialchars($systemInfo['server_software']) ?></td>
                                </tr>
                                <tr>
                                    <th>Document Root</th>
                                    <td><code><?= htmlspecialchars($systemInfo['document_root']) ?></code></td>
                                </tr>
                                <tr>
                                    <th>Server Name</th>
                                    <td><?= htmlspecialchars($systemInfo['server_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Server Port</th>
                                    <td><?= htmlspecialchars($systemInfo['server_port']) ?></td>
                                </tr>
                                <tr>
                                    <th>Max Execution Time</th>
                                    <td><?= $systemInfo['max_execution_time'] ?>s</td>
                                </tr>
                                <tr>
                                    <th>Upload Max Size</th>
                                    <td><?= $systemInfo['upload_max_filesize'] ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="info-table">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>PHP Extensions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach (array_slice($systemInfo['loaded_extensions'], 0, 20) as $ext): ?>
                                    <div class="col-6 mb-1">
                                        <span class="badge bg-light text-dark"><?= $ext ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($systemInfo['loaded_extensions']) > 20): ?>
                                    <div class="col-12">
                                        <small class="text-muted">... and <?= count($systemInfo['loaded_extensions']) - 20 ?> more</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="info-table">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Preview Activity</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>URL</th>
                                        <th>Format</th>
                                        <th>Status</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPreviews as $preview): ?>
                                        <tr>
                                            <td><?= $preview['timestamp'] ?></td>
                                            <td>
                                                <code style="font-size: 0.8rem;">
                                                    <?= htmlspecialchars(substr($preview['url'], 0, 50)) ?>
                                                    <?= strlen($preview['url']) > 50 ? '...' : '' ?>
                                                </code>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= $preview['format'] ?></span>
                                            </td>
                                            <td>
                                                <span class="<?= $preview['status'] === 'success' ? 'status-success' : 'status-blocked' ?>">
                                                    <i class="fas fa-<?= $preview['status'] === 'success' ? 'check' : 'times' ?> me-1"></i>
                                                    <?= ucfirst($preview['status']) ?>
                                                </span>
                                            </td>
                                            <td><code><?= $preview['ip'] ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Debug Information -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Debug Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> Debug mode is enabled. This may expose sensitive information.
                            </div>
                            <pre class="bg-light p-3 rounded"><code><?php
                                echo "Current working directory: " . getcwd() . "\n";
                                echo "PHP SAPI: " . php_sapi_name() . "\n";
                                echo "Operating System: " . PHP_OS . "\n";
                                echo "Architecture: " . php_uname('m') . "\n";
                                echo "Hostname: " . php_uname('n') . "\n";
                                echo "Kernel: " . php_uname('r') . "\n";
                                ?></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>