# DocuView - CVE-2024-2961 CTF Challenge

## 题目概述

**DocuView** 是一个基于CVE-2024-2961漏洞的全新Web安全CTF题目。这是一个在线文档预览服务平台，用户可以通过URL预览各种文档和图片。题目采用了全新的SSRF入口设计，通过文档预览功能触发CVE-2024-2961漏洞。

## 题目设计理念

### 场景设定
- **服务名称**: DocuView - 智能文档预览平台
- **核心功能**: 在线文档预览、URL导入、格式转换
- **漏洞入口**: 文档URL预览功能中的SSRF
- **攻击链**: SSRF → PHP Filter Chain → CVE-2024-2961 RCE

### 创新设计点
1. **多重SSRF入口**: 文档预览、缩略图生成、格式检测
2. **伪装合法功能**: 看似正常的文档预览服务
3. **复杂攻击路径**: 需要绕过多层检测机制
4. **真实场景模拟**: 模拟企业级文档管理系统

## 漏洞原理

### CVE-2024-2961 利用链

1. **SSRF入口**: `preview.php` 中的文档URL预览功能
2. **绕过检测**: 通过多种技巧绕过URL过滤
3. **PHP Filter链**: 构造复杂的 `php://filter` 过滤器链
4. **字符集转换**: UTF-8 → ISO-2022-CN-EXT 转换触发溢出
5. **堆内存控制**: 通过特殊字符 `劄` 精确控制堆布局
6. **代码执行**: 覆盖函数指针实现任意代码执行

### 关键技术点

- **多层URL解析**: 支持重定向、短链接、编码绕过
- **文件类型检测**: 基于Content-Type和文件头的检测机制
- **缓存机制**: 预览结果缓存，增加利用复杂度
- **错误处理**: 详细的错误信息可能泄露内部信息

## 部署说明

### 环境要求
- PHP 8.1+ (存在CVE-2024-2961漏洞的版本)
- Apache/Nginx Web服务器
- 支持php://filter和各种PHP扩展

### 快速部署（推荐）

使用提供的部署脚本：

```bash
# 给脚本执行权限
chmod +x deploy.sh

# 构建并启动题目
./deploy.sh build
./deploy.sh start

# 查看状态
./deploy.sh status

# 测试题目
./deploy.sh test
```

### 手动部署

```bash
# 构建并启动容器
docker-compose build
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down
```

### 访问地址

- **主页**: http://localhost:8080
- **文档预览**: http://localhost:8080/preview.php
- **管理面板**: http://localhost:8080/admin.php
  - 用户名: `admin`
  - 密码: `docuview123`

## 解题思路

### 1. 信息收集
- 探索文档预览功能
- 分析URL参数和请求方式
- 测试各种文档类型支持

### 2. SSRF检测
- 测试内网地址访问
- 尝试file://协议读取本地文件
- 检测URL过滤机制

### 3. 绕过防护
- URL编码绕过
- 重定向绕过
- 协议混淆

### 4. 漏洞利用
- 构造CVE-2024-2961 payload
- 通过php://filter链触发漏洞
- 实现远程代码执行

## EXP使用说明

### 方法一：简化版EXP（推荐）

快速获取flag的脚本：

```bash
# 安装依赖
pip3 install requests

# 运行简化版EXP
python3 simple_exp_docuview.py http://localhost:8080
```

**特点**：
- 自动尝试多种flag提取方法
- 包含文件读取、环境变量、命令执行等方式
- 输出简洁，适合快速获取flag

### 方法二：完整版EXP

功能完整的利用脚本：

```bash
# 运行完整版EXP
python3 exp_docuview.py http://localhost:8080
```

**特点**：
- 详细的漏洞检测和利用过程
- 多种载荷和绕过技术
- 完整的错误处理和调试信息
- 适合学习和研究

### 手动利用步骤

1. **访问文档预览页面**
   ```
   http://localhost:8080/preview.php
   ```

2. **测试SSRF漏洞**
   - URL: `file:///etc/passwd`
   - 格式: `text`
   - 提交预览请求

3. **利用CVE-2024-2961**
   - URL: `php://filter/convert.base64-encode/resource=/flag`
   - 或: `file:///flag`
   - 获取flag内容

### 预期输出

成功利用后应该看到：
```
[+] FLAG FOUND: flag{d0cuv13w_cv3_2024_2961_55rf_t0_rc3_ch41n}
```

## 预期Flag

```
flag{d0cu_v13w_cv3_2024_2961_ssrf_t0_rc3}
```

## 难度等级

**Medium-Hard** - 需要深入理解SSRF、PHP Filter链和CVE-2024-2961漏洞原理

## 学习价值

1. **SSRF漏洞利用**: 学习现代SSRF攻击技巧
2. **PHP安全**: 深入理解PHP内存管理和漏洞利用
3. **Filter链构造**: 掌握复杂的PHP过滤器链攻击
4. **真实场景**: 模拟真实的企业级应用漏洞

---

**题目作者**: Security Research Team  
**创建日期**: 2024-12-19  
**版本**: v1.0  
**标签**: Web, PHP, CVE-2024-2961, SSRF, RCE, Filter Chain