<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocuView - Smart Document Preview Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .preview-form {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-file-alt text-primary me-2"></i>
                DocuView
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#features">Features</a>
                <a class="nav-link" href="#about">About</a>
                <a class="nav-link" href="admin.php">Admin</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Smart Document Preview Platform</h1>
                    <p class="lead mb-4">Preview any document instantly with our advanced cloud-based technology. Support for PDF, Word, Excel, PowerPoint, images and more.</p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-light text-dark px-3 py-2">PDF</span>
                        <span class="badge bg-light text-dark px-3 py-2">DOCX</span>
                        <span class="badge bg-light text-dark px-3 py-2">XLSX</span>
                        <span class="badge bg-light text-dark px-3 py-2">PPTX</span>
                        <span class="badge bg-light text-dark px-3 py-2">Images</span>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 8rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Preview Form -->
    <section class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="preview-form">
                    <h3 class="text-center mb-4">Preview Your Document</h3>
                    <form action="preview.php" method="POST" id="previewForm">
                        <div class="mb-4">
                            <label for="documentUrl" class="form-label fw-semibold">
                                <i class="fas fa-link me-2"></i>Document URL
                            </label>
                            <input type="url" class="form-control" id="documentUrl" name="url" 
                                   placeholder="https://example.com/document.pdf" required>
                            <div class="form-text">Enter the URL of the document you want to preview</div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="format" class="form-label fw-semibold">
                                    <i class="fas fa-cog me-2"></i>Output Format
                                </label>
                                <select class="form-select" id="format" name="format">
                                    <option value="auto">Auto Detect</option>
                                    <option value="image">Image Preview</option>
                                    <option value="text">Text Extract</option>
                                    <option value="thumbnail">Thumbnail</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="quality" class="form-label fw-semibold">
                                    <i class="fas fa-sliders-h me-2"></i>Quality
                                </label>
                                <select class="form-select" id="quality" name="quality">
                                    <option value="high">High Quality</option>
                                    <option value="medium" selected>Medium Quality</option>
                                    <option value="low">Low Quality</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cache" name="cache" checked>
                                <label class="form-check-label" for="cache">
                                    Enable caching for faster subsequent previews
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-eye me-2"></i>Generate Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 mt-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="fw-bold">Powerful Features</h2>
                    <p class="text-muted">Everything you need for document preview and management</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-bolt text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Lightning Fast</h5>
                            <p class="card-text">Advanced caching and optimization for instant document previews</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-shield-alt text-success mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Secure & Private</h5>
                            <p class="card-text">Enterprise-grade security with end-to-end encryption</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-magic text-warning mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Smart Detection</h5>
                            <p class="card-text">Automatic format detection and intelligent processing</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-number">1M+</div>
                    <p class="text-muted">Documents Processed</p>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">50+</div>
                    <p class="text-muted">File Formats</p>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">99.9%</div>
                    <p class="text-muted">Uptime</p>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">24/7</div>
                    <p class="text-muted">Support</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>DocuView</h5>
                    <p class="text-muted">Smart Document Preview Platform</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; 2024 DocuView. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('previewForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>