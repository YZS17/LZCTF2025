<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
    // 处理登录逻辑
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == "admin" && $password == "123456"){
	$_SESSION['user'] = $username;
	header("Location: dashboard.php");
	exit();
	}

    echo '<script>alert("登录失败，admin login only!");</script>';
}

}
?>

<!DOCTYPE html>
<html>
<head>
    <title>XU17-Website</title>
    <meta charset="UTF-8">
    <style>
        body {
            background-image: url('static/art02.jpg'); /* 替换成你的背景图片文件路径 */
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
        <h1>XU17-Website</h1>
        <h2>请登录你的账号</h2>
        <form method="post" action="index.php">
            <input type="text" name="username" placeholder="用户名" required><br>
            <input type="password" name="password" placeholder="密码" required><br>
            <button type="submit" name="login">登录</button>
        </form>

        <h2>没有帐号？</h2>
        <p><a href="register.php">点击这里注册</a></p>
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
