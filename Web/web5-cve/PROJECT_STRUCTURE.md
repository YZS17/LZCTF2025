# DocuView CTF Challenge - 项目结构说明

本文档详细说明了DocuView CTF题目的项目结构和各文件的作用。

## 项目目录结构

```
docuview-challenge/
├── README.md                    # 主要说明文档
├── PROJECT_STRUCTURE.md         # 项目结构说明（本文件）
├── docker-compose.yml           # Docker Compose配置
├── Dockerfile                   # Docker镜像构建文件
├── deploy.sh                    # Linux/macOS部署脚本
├── deploy.bat                   # Windows部署脚本
├── exp_docuview.py             # 完整版利用脚本
├── simple_exp_docuview.py      # 简化版利用脚本
└── src/                        # 网站源代码目录
    ├── index.php               # 主页面
    ├── preview.php             # 文档预览功能（漏洞入口）
    └── admin.php               # 管理员面板
```

## 文件详细说明

### 核心文件

#### `src/index.php`
- **作用**: 网站主页面
- **功能**: 
  - 展示DocuView文档预览服务介绍
  - 提供文档预览表单界面
  - 包含导航、功能介绍、统计信息等
- **安全性**: 无直接安全问题，主要用于展示

#### `src/preview.php`
- **作用**: 文档预览核心功能（**主要漏洞点**）
- **功能**:
  - 接收用户提交的URL
  - 执行文档内容获取（SSRF漏洞点）
  - 处理不同格式的文档
  - 缓存管理
- **漏洞**:
  - SSRF: `file_get_contents($_POST['url'])`
  - CVE-2024-2961: 通过php://filter触发iconv()漏洞
- **关键代码**:
  ```php
  $content = file_get_contents($url); // SSRF漏洞点
  ```

#### `src/admin.php`
- **作用**: 管理员后台面板
- **功能**:
  - 系统信息展示
  - 缓存统计
  - 预览活动日志
  - 调试信息（故意暴露敏感信息）
- **认证**: 弱密码 `admin/docuview123`
- **用途**: 信息收集，了解系统环境

### 部署文件

#### `docker-compose.yml`
- **作用**: Docker Compose服务定义
- **配置**:
  - 服务名: `docuview`
  - 端口映射: `8080:80`
  - 环境变量: `FLAG=flag{d0cuv13w_cv3_2024_2961_55rf_t0_rc3_ch41n}`
  - 网络配置: 独立网络

#### `Dockerfile`
- **作用**: Docker镜像构建配置
- **基础镜像**: `php:8.1-apache`
- **关键配置**:
  - 安装必要的PHP扩展
  - 配置Apache和PHP
  - 创建flag文件
  - 设置权限
  - 启用危险的PHP配置（allow_url_fopen等）

### 部署脚本

#### `deploy.sh` (Linux/macOS)
- **作用**: 自动化部署和管理脚本
- **功能**:
  - `build`: 构建Docker镜像
  - `start`: 启动服务
  - `stop`: 停止服务
  - `restart`: 重启服务
  - `logs`: 查看日志
  - `status`: 查看状态
  - `clean`: 清理环境
  - `test`: 自动测试
  - `shell`: 进入容器

#### `deploy.bat` (Windows)
- **作用**: Windows版本的部署脚本
- **功能**: 与deploy.sh相同，适配Windows命令行

### 利用脚本

#### `exp_docuview.py`
- **作用**: 完整版漏洞利用脚本
- **特点**:
  - 详细的漏洞检测流程
  - 多种利用载荷
  - 完整的错误处理
  - 调试信息输出
- **适用场景**: 学习研究、详细分析

#### `simple_exp_docuview.py`
- **作用**: 简化版快速利用脚本
- **特点**:
  - 快速flag提取
  - 多种提取方法
  - 简洁输出
  - 易于使用
- **适用场景**: 快速获取flag、比赛使用

### 文档文件

#### `README.md`
- **作用**: 主要说明文档
- **内容**:
  - 题目概述
  - 漏洞原理
  - 部署方法
  - 解题思路
  - EXP使用说明
  - 技术细节

#### `PROJECT_STRUCTURE.md`
- **作用**: 项目结构说明（本文件）
- **内容**: 详细的文件结构和作用说明

## 漏洞利用链

```
1. 访问主页 (index.php)
   ↓
2. 提交恶意URL到预览功能 (preview.php)
   ↓
3. 触发SSRF漏洞 (file_get_contents)
   ↓
4. 利用php://filter协议
   ↓
5. 触发CVE-2024-2961 (iconv缓冲区溢出)
   ↓
6. 实现远程代码执行
   ↓
7. 获取flag
```

## 关键配置说明

### PHP配置
- `allow_url_fopen = On`: 允许URL文件操作
- `allow_url_include = On`: 允许URL包含
- 这些配置使得SSRF和php://filter利用成为可能

### Apache配置
- 启用了`.htaccess`支持
- 配置了URL重写规则
- 允许所有请求访问

### 权限配置
- `/tmp/docuview_cache`: 777权限，用于缓存
- `/var/www/html/uploads`: 777权限，用于上传
- Flag文件: 644权限，可读取

## 安全注意事项

⚠️ **警告**: 本题目包含真实的安全漏洞，仅用于教学和CTF比赛。请勿在生产环境中部署！

### 包含的漏洞
1. **SSRF**: 未过滤的URL请求
2. **CVE-2024-2961**: PHP iconv()缓冲区溢出
3. **信息泄露**: 管理面板暴露系统信息
4. **弱密码**: 管理员使用弱密码
5. **危险配置**: PHP配置允许危险操作

### 防护建议
1. 对用户输入进行严格验证
2. 限制可访问的URL协议和域名
3. 及时更新PHP版本
4. 使用强密码
5. 最小权限原则
6. 禁用不必要的PHP功能

## 学习价值

本题目适合学习以下内容：
1. **SSRF漏洞**: 原理、检测、利用、防护
2. **CVE-2024-2961**: 具体漏洞分析
3. **PHP安全**: 常见配置问题
4. **Docker部署**: 容器化应用部署
5. **渗透测试**: 完整的利用链构建

## 扩展学习

可以基于本题目进行以下扩展：
1. 添加WAF绕过技术
2. 实现更复杂的利用链
3. 添加其他类型的漏洞
4. 研究不同的防护方案
5. 分析真实世界的类似漏洞