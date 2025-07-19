<?php
session_start();

// 错误处理
ini_set('display_errors', 0);
error_reporting(0);

// 安全配置
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

/**
 * 文档预览处理类
 * 核心功能：通过URL获取文档并生成预览
 * 漏洞点：URL过滤不完善，存在SSRF风险
 */
class DocumentPreview {
    private $allowedHosts = [
        'docs.google.com',
        'drive.google.com', 
        'onedrive.live.com',
        'dropbox.com',
        'amazonaws.com',
        'cloudfront.net'
    ];
    
    private $blockedIPs = [
        '127.0.0.1',
        'localhost',
        '0.0.0.0',
        '::1'
    ];
    
    private $allowedSchemes = ['http', 'https'];
    private $maxFileSize = 50 * 1024 * 1024; // 50MB
    private $cacheDir = '/tmp/docuview_cache';
    
    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * 主要的文档预览方法
     * 这里是SSRF漏洞的入口点
     */
    public function previewDocument($url, $format = 'auto', $quality = 'medium', $useCache = true) {
        try {
            // // 第一层：基础URL验证
            // if (!$this->validateURL($url)) {
            //     throw new Exception('Invalid URL format');
            // }
            
            // // 第二层：安全检查（可被绕过）
            // if (!$this->securityCheck($url)) {
            //     throw new Exception('Security check failed');
            // }
            
            // 第三层：获取文档内容（SSRF漏洞点）
            $content = $this->fetchDocument($url);
            
            if ($content === false) {
                throw new Exception('Failed to fetch document');
            }
            
            // 第四层：内容处理
            $result = $this->processContent($content, $format, $quality);
            
            // 缓存结果
            if ($useCache) {
                $this->cacheResult($url, $result);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return $this->handleError($e->getMessage());
        }
    }
    
    /**
     * URL基础验证
     * 检查URL格式和协议
     */
    private function validateURL($url) {
        if (empty($url)) {
            return false;
        }
        
        // 解析URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return false;
        }
        
        // 检查协议
        if (!in_array(strtolower($parsed['scheme']), $this->allowedSchemes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 安全检查（存在绕过可能）
     * 这里的检查不够严格，可以被绕过
     */
    private function securityCheck($url) {
        $parsed = parse_url($url);
        $host = strtolower($parsed['host']);
        
        // 检查是否为IP地址
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            // 简单的IP黑名单检查（可绕过）
            if (in_array($host, $this->blockedIPs)) {
                return false;
            }
            
            // 检查私有IP范围（不完整，可绕过）
            if (strpos($host, '192.168.') === 0 || 
                strpos($host, '10.') === 0 || 
                strpos($host, '172.16.') === 0) {
                return false;
            }
        }
        
        // 域名白名单检查（可通过子域名绕过）
        $allowed = false;
        foreach ($this->allowedHosts as $allowedHost) {
            if (strpos($host, $allowedHost) !== false) {
                $allowed = true;
                break;
            }
        }
        
        // 如果不在白名单中，进行额外检查
        if (!$allowed) {
            // 这里有一个逻辑漏洞：只检查了部分危险情况
            // 可以通过各种编码和绕过技巧来突破
            if (strpos($url, 'file://') !== false || 
                strpos($url, 'ftp://') !== false ||
                strpos($url, 'gopher://') !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 获取文档内容
     * 这里是实际的SSRF漏洞点
     */
    private function fetchDocument($url) {
        // 创建上下文选项
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'DocuView/1.0 (Document Preview Service)',
                'follow_location' => true,
                'max_redirects' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        // 关键漏洞：直接使用file_get_contents获取URL内容
        // 这里没有进一步的安全检查，可以利用各种协议
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            // 尝试使用curl作为备选方案
            return $this->fetchWithCurl($url);
        }
        
        // 检查文件大小
        if (strlen($content) > $this->maxFileSize) {
            throw new Exception('File too large');
        }
        
        return $content;
    }
    
    /**
     * 使用cURL获取内容（备选方案）
     */
    private function fetchWithCurl($url) {
        if (!function_exists('curl_init')) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'DocuView/1.0 (Document Preview Service)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($content === false || $httpCode >= 400) {
            return false;
        }
        
        return $content;
    }
    
    /**
     * 处理文档内容
     */
    private function processContent($content, $format, $quality) {
        // 检测文件类型
        $mimeType = $this->detectMimeType($content);
        
        switch ($format) {
            case 'image':
                return $this->generateImagePreview($content, $mimeType, $quality);
            case 'text':
                return $this->extractText($content, $mimeType);
            case 'thumbnail':
                return $this->generateThumbnail($content, $mimeType);
            default:
                return $this->autoProcess($content, $mimeType, $quality);
        }
    }
    
    /**
     * 检测MIME类型
     */
    private function detectMimeType($content) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($content);
    }
    
    /**
     * 自动处理内容
     */
    private function autoProcess($content, $mimeType, $quality) {
        // 根据MIME类型自动选择处理方式
        if (strpos($mimeType, 'image/') === 0) {
            return $this->generateImagePreview($content, $mimeType, $quality);
        } elseif (strpos($mimeType, 'text/') === 0) {
            return $this->extractText($content, $mimeType);
        } else {
            // 对于其他类型，尝试生成缩略图
            return $this->generateThumbnail($content, $mimeType);
        }
    }
    
    /**
     * 生成图片预览
     */
    private function generateImagePreview($content, $mimeType, $quality) {
        if (strpos($mimeType, 'image/') !== 0) {
            throw new Exception('Not an image file');
        }
        
        // 直接返回图片内容（base64编码）
        return [
            'type' => 'image',
            'mime_type' => $mimeType,
            'data' => base64_encode($content),
            'size' => strlen($content)
        ];
    }
    
    /**
     * 提取文本内容
     */
    private function extractText($content, $mimeType) {
        if (strpos($mimeType, 'text/') === 0) {
            // 直接返回文本内容
            return [
                'type' => 'text',
                'mime_type' => $mimeType,
                'content' => $content,
                'length' => strlen($content)
            ];
        } else {
            // 尝试从二进制文件中提取文本
            $text = $this->extractTextFromBinary($content);
            return [
                'type' => 'text',
                'mime_type' => 'text/plain',
                'content' => $text,
                'length' => strlen($text)
            ];
        }
    }
    
    /**
     * 从二进制文件提取文本
     */
    private function extractTextFromBinary($content) {
        // 简单的文本提取（仅提取可打印字符）
        return preg_replace('/[^\x20-\x7E\x0A\x0D]/', '', $content);
    }
    
    /**
     * 生成缩略图
     */
    private function generateThumbnail($content, $mimeType) {
        // 简化的缩略图生成
        return [
            'type' => 'thumbnail',
            'mime_type' => $mimeType,
            'info' => 'Thumbnail generation not implemented for this file type',
            'size' => strlen($content)
        ];
    }
    
    /**
     * 缓存结果
     */
    private function cacheResult($url, $result) {
        $cacheKey = md5($url);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';
        file_put_contents($cacheFile, serialize($result));
    }
    
    /**
     * 错误处理
     */
    private function handleError($message) {
        return [
            'type' => 'error',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? '';
    $format = $_POST['format'] ?? 'auto';
    $quality = $_POST['quality'] ?? 'medium';
    $useCache = isset($_POST['cache']);
    
    if (empty($url)) {
        $error = 'Please provide a document URL';
    } else {
        $preview = new DocumentPreview();
        $result = $preview->previewDocument($url, $format, $quality, $useCache);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Preview - DocuView</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
    <style>
        .preview-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 30px 0;
        }
        
        .result-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .text-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
        }
        
        .info-badge {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
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
                <a class="nav-link" href="admin.php">Admin</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if (isset($error)): ?>
                    <div class="preview-container">
                        <div class="error-message text-center">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h4>Error</h4>
                            <p><?= htmlspecialchars($error) ?></p>
                            <a href="index.php" class="btn btn-primary">Try Again</a>
                        </div>
                    </div>
                <?php elseif (isset($result)): ?>
                    <div class="preview-container">
                        <div class="result-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3><i class="fas fa-eye me-2"></i>Preview Result</h3>
                                <span class="info-badge"><?= ucfirst($result['type']) ?></span>
                            </div>
                            <small class="text-muted">URL: <?= htmlspecialchars($url) ?></small>
                        </div>
                        
                        <?php if ($result['type'] === 'error'): ?>
                            <div class="error-message">
                                <h5><i class="fas fa-times-circle me-2"></i>Processing Error</h5>
                                <p><?= htmlspecialchars($result['message']) ?></p>
                                <small>Timestamp: <?= $result['timestamp'] ?></small>
                            </div>
                        <?php elseif ($result['type'] === 'image'): ?>
                            <div class="text-center">
                                <img src="data:<?= $result['mime_type'] ?>;base64,<?= $result['data'] ?>" 
                                     class="preview-image" alt="Document Preview">
                                <div class="mt-3">
                                    <small class="text-muted">
                                        Type: <?= $result['mime_type'] ?> | 
                                        Size: <?= number_format($result['size']) ?> bytes
                                    </small>
                                </div>
                            </div>
                        <?php elseif ($result['type'] === 'text'): ?>
                            <div>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Type: <?= $result['mime_type'] ?> | 
                                        Length: <?= number_format($result['length']) ?> characters
                                    </small>
                                </div>
                                <div class="text-content"><?= htmlspecialchars($result['content']) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <i class="fas fa-file-alt fa-5x text-muted mb-3"></i>
                                <h5>Preview Not Available</h5>
                                <p class="text-muted"><?= htmlspecialchars($result['info'] ?? 'Unable to generate preview for this file type') ?></p>
                                <?php if (isset($result['size'])): ?>
                                    <small class="text-muted">File size: <?= number_format($result['size']) ?> bytes</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Preview Another Document
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
</body>
</html>