// gcc -m32 -Wl,-z,relro -fno-stack-protector -no-pie -o pwn2 pwn2.c

#include <endian.h>
#include <stdio.h>
#include <unistd.h>
#include <string.h>

char s1[] = "Welcome, ctfer!\n";
char s2[] = "Good luck!\n";
char Garbage1[0x200];
char s[0x240];
char Garbage2[0x200];
void vul()
{
    char buf[24]; 
    write(1, s1, strlen(s1));
    read(0, &s, 0x240);

    write(1, s2, strlen(s2));
    read(0, buf, 0x28);
    return ;
}

int main()
{
    vul();
    puts("bye!");
    return 0;
}