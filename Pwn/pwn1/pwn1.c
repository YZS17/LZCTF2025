// gcc -o pwn1 pwn1.c -s
#include <stdio.h>
#include <unistd.h>
#include <sys/mman.h>

void init()
{
    setbuf(stdin,  0);
    setbuf(stdout, 0);
    setbuf(stderr, 0);
}
int main(void)
{
    void *land;
    void *addr = (void *)0x123456;
    init();
    puts("Welcome to 2025LZCTF!");
    land = mmap(addr, 0x1000, 7, 34, -1, 0);
    read(0, land, 0x14);
    ((void(*)())land)();
    
    return 0;
}