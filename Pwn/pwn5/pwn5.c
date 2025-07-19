// gcc -fno-stack-protector -no-pie -o pwn5 pwn5.c
// patchelf --set-interpreter ./ld-2.27.so pwn5
// patchelf --replace-needed libc.so.6 ./libc-2.27.so pwn5
// seccomp-tools dump ./pwn5


#include<stdio.h>
#include <stdio.h>
#include<unistd.h>
#include <dirent.h>
#include <sys/stat.h>
#include <sys/prctl.h>
#include <linux/filter.h>
#include <linux/seccomp.h>
#include <stdlib.h>

char *heaparray[0x10];
size_t chunksize[0x10];

void add(){
    int size;
    int idx;
    puts("index:");
    scanf("%d",&idx);
    if(idx < 0 || idx >= 0x10){
        puts("Out of bound!");
        _exit(0);
    }
    puts("size:");
    scanf("%d",&size);
    heaparray[idx]=(char *)malloc(size);
    chunksize[idx]=size;
}

void delete(){
    int idx ;
    char buf[4];
    printf("index:\n");
    read(0,buf,4);
    idx = atoi(buf);
    if(idx < 0 || idx >= 0x10){
        puts("Out of bound!");
        _exit(0);
    }
    // if(heaparray[idx]){
        free(heaparray[idx]);
        // chunksize[idx] = 0 ;
        // heaparray[idx]=NULL;
        puts("Done !");
    // }else{
    //     puts("No such heap !");
    // }
}

void edit(){
    int idx ;
    char buf[4];
    printf("index:\n");
    read(0,buf,4);
    idx = atoi(buf);
    if(idx < 0 || idx >= 0x10){
        puts("Out of bound!");
        _exit(0);
    }
    // if(heaparray[idx]){
        int size;
        puts("length:\n");
        scanf("%d",&size);
        printf("content:\n");
        read(0,heaparray[idx],size);
        puts("Done !");
    // }else{
    //     puts("No such heap !");
    // }
}

void show(){
    int idx ;
    char buf[4];
    printf("index:\n");
    read(0,buf,4);
    idx = atoi(buf);
    if(idx < 0 || idx >= 0x10){
        puts("Out of bound!");
        _exit(0);
    }
    // if(heaparray[idx]){
        puts(heaparray[idx]);
    // }else{
    //     puts("No such heap !");
    // }
}

void exit_p(){
    puts("Bye!");
    _exit(0);
}

void menu(void){
    puts("choice:");
    puts("1.add");
    puts("2.delete");
    puts("3.edit");
    puts("4.show");
    puts("5.exit");
}

int main()
{
    setvbuf(stdin, 0LL, 2, 0LL);
    setvbuf(stdout, 0LL, 2, 0LL);
    setvbuf(stderr, 0LL, 2, 0LL);
    struct sock_filter filter[] = {
        BPF_STMT(BPF_LD+BPF_W+BPF_ABS,4),
        BPF_JUMP(BPF_JMP+BPF_JEQ,0xc000003e,0,2),
        BPF_STMT(BPF_LD+BPF_W+BPF_ABS,0),
        BPF_JUMP(BPF_JMP+BPF_JEQ,59,0,1),
        BPF_STMT(BPF_RET+BPF_K,SECCOMP_RET_KILL),
        BPF_STMT(BPF_RET+BPF_K,SECCOMP_RET_ALLOW),
    };
    struct sock_fprog prog = {
        .len = (unsigned short)(sizeof(filter)/sizeof(filter[0])),
        .filter = filter,
    };
    prctl(PR_SET_NO_NEW_PRIVS,1,0,0,0);
    prctl(PR_SET_SECCOMP,SECCOMP_MODE_FILTER,&prog);
    int n;
    while(1)
    {
        menu();
        scanf("%d",&n);
        switch(n)
        {
            case 1:add();break;
            case 2:delete();break;
            case 3:edit();break;
            case 4:show();break;
            case 5:exit_p();break;
            default:puts("error");
        }
    }
    return 0;
}


