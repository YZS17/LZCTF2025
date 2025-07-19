<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // 处理注册逻辑
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = [
        'username' => $username,
        'password' => $password,
    ];


    echo '<script>alert("注册成功");window.location.href = "index.php";</script>';
    
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>注册</title>
    <meta charset="UTF-8">
    <style>
        body {
            background-image: url('static/art01.jpg'); /* 替换成你的背景图片文件路径 */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            margin: 100px auto;
            max-width: 400px;
        }

        h1 {
            text-align: center;
        }

        form {
            text-align: center;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>注册</h1>
        <form method="post" action="register.php">
            <input type="text" name="username" placeholder="用户名" required><br>
            <input type="password" name="password" placeholder="密码" required><br>
            <button type="submit" name="register">注册</button>
        </form>
        <p><a href="index.php">返回首页</a></p>
    </div>

    <script>
        // 使用JavaScript可以添加更多动态效果
        document.addEventListener("DOMContentLoaded", function () {
            const container = document.querySelector(".container");
            container.style.opacity = "0";

            setTimeout(function () {
                container.style.transition = "opacity 0.5s";
                container.style.opacity = "1";
            }, 100);
        });
    </script>
</body>
</html>
