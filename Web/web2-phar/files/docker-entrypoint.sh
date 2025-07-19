#!/bin/bash

# 写入 flag 到 Web 目录
echo "$GZCTF_FLAG" > /var/www/html/flag_xu17_7777777777
unset GZCTF_FLAG

# 设置权限：root 和 www-data 可读，其他用户不可读
chown root:www-data /var/www/html/flag_xu17_7777777777
chmod 640 /var/www/html/flag_xu17_7777777777  # root(rw), www-data(r), others(0)

# 启动 Apache
apache2-foreground