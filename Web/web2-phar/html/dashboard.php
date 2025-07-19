<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>XU17-Website</title>
    <meta charset="UTF-8">
    <style>
        body {
            background-image: url('static/8.jpg');
            background-size: cover;
            font-family: Arial, sans-serif;
        }

        .header {
            text-align: right;
            padding: 10px;
        }

        .welcome {
            float: right;
            margin-right: 10px;
        }

        .logout {
            float: right;
            margin-right: 10px;
        }

        .dashboard-title {
            text-align: center;
            margin-top: 30px;
            font-size: 24px;
        }

        .upload-form {
            text-align: center;
            margin-top: 20px;
        }

        .file-input {
            display: none;
        }

        .file-label {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .file-label:hover {
            background-color: #2980b9;
        }

        .file-preview {
            text-align: center;
            margin-top: 20px;
        }

        .error-message {
            color: red;
        }

        /* 新增样式，用于居中图片 */
        .centered-image {
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome">欢迎, <?php echo $_SESSION['user']; ?>!</div>
        <div class="logout"><a href="logout.php">退出</a></div>
    </div>

    <h1 class="dashboard-title">XU17-Website</h1>

    <h2>今日两份风景照片请查收</h2>


    <a href="/show.php?file=static/a5.jpg">点击查看风景照片</a><br>
    <a href="/show.php?file=static/a6.jpg">点击查看风景照片第二份</a><br>

    <style>
    .space-between {
        margin-bottom: 20px;
    }
    </style>

    <div class="space-between"></div>

    <h2>上传一些照片为本站做出贡献吧！</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="file">选择图片：</label>
        <input type="file" name="file" id="file" accept="image/*" required>
        <br>
        <input type="submit" value="上传">
    </form>
</body>
</html>
