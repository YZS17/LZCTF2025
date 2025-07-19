<?php
// 允许上传的文件类型
$allowed_extensions = array("png", "jpg", "jpeg", "gif");

// 获取上传文件的文件名和扩展名
$file_name = $_FILES["file"]["name"];
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
// echo $file_extension;

// 检查上传文件的扩展名是否合法
if (!in_array($file_extension, $allowed_extensions)) {
	echo "Illegal file!!!";
	exit;
}

// 将上传的文件移动到指定目录
$upload_path = "uploads/";//修改
$target_file = $upload_path . basename($file_name);
if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
	echo "The picture was uploaded successfully! The path is:".$upload_path.$file_name;
	echo "</br>";

} else {
	echo "The file type is incorrect!";
}
?>
