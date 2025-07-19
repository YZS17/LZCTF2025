#!/bin/sh

# Check the environment variables for the flag and assign to INSERT_FLAG
if [ "$DASFLAG" ]; then
    INSERT_FLAG="$DASFLAG"
    export DASFLAG=no_FLAG
    DASFLAG=no_FLAG
elif [ "$FLAG" ]; then
    INSERT_FLAG="$FLAG"
    export FLAG=no_FLAG
    FLAG=no_FLAG
elif [ "$GZCTF_FLAG" ]; then
    INSERT_FLAG="$GZCTF_FLAG"
    export GZCTF_FLAG=no_FLAG
    GZCTF_FLAG=no_FLAG
else
    INSERT_FLAG="LZCTF{I-lOve-PAnd@_r0utE!aO2w5}"
fi

# 将FLAG写入文件 (以root权限)
echo "$INSERT_FLAG" > /flag # 使用 > 直接写入文件，如果不需要输出到stdout

# 设置/flag文件权限为只读 (owner=read, group=read, other=read)
chmod 444 /flag

# 以 www-data 用户身份在前台启动 lighttpd
# exec 会让 lighttpd 进程替换当前的 shell 进程
# -D 标志确保 lighttpd 在前台运行，而不是作为守护进程
exec gosu www-data /usr/local/sbin/lighttpd -D -f /lighttpd/config/lighttpd.conf

# 由于使用了 exec，并且 lighttpd -D 在前台运行，
# 下面的 sleep infinity 将不会被执行，也不再需要。
# sleep infinity;