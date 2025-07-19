# IOT2

如何运行此docker环境？

1. 编译image

```
docker build -t iot2:1.0 .
```

2. 80端口启动容器后即可访问

```
docker run -d -p 80:80 iot2:1.0
```
