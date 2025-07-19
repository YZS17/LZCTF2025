FROM ubuntu:22.04

# 制作者信息
LABEL auther_template="CTFer"

# apt更换镜像源，并安装相关依赖
RUN sed -i 's@//.*archive.ubuntu.com@//mirrors.ustc.edu.cn@g' /etc/apt/sources.list && \
    sed -i 's@//.*security.ubuntu.com@//mirrors.ustc.edu.cn@g' /etc/apt/sources.list
RUN apt-get update && apt-get -y dist-upgrade && \
    apt-get install -y lib32z1 xinetd cmake make gcc g++ libpcre3-dev zlib1g-dev libpcre2-dev \
    gosu libcap2-bin && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# 部署xinetd服务
RUN echo "Blocked by ctf_xinetd" > /etc/banner_fail

# 复制容器启动脚本
COPY ./service/docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh

# 部署程序
COPY ./src/lighttpd /lighttpd

WORKDIR /lighttpd
RUN cd lighttpd-1.4.76 && \
    rm -rf CMakeCache.txt CMakeFiles/ && \
    cmake -DCMAKE_INSTALL_PREFIX=/usr/local -Wno-dev . && \
    make -j$(nproc) && \
    make install

# 授予lighttpd绑定到特权端口的能力 (例如 80)
# 即使以非root用户运行，也能绑定到这些端口
RUN setcap 'cap_net_bind_service=+ep' /usr/local/sbin/lighttpd
# 初始化flag
RUN touch /flag && \
    chmod 744 /flag

EXPOSE 80

# 指定容器入口点
ENTRYPOINT ["/bin/bash","/docker-entrypoint.sh"]
