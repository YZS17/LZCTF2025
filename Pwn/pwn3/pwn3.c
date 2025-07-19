// gcc pwn3.c -o pwn3 -s
#include<stdio.h>
#include<stdlib.h>
#include<string.h>
#include<unistd.h>

#define OPCODE_N 7
#define F_LEN 12


// FLAG:6dv^H*rp'qft

void init()
{
    setbuf(stdin,  0);
    setbuf(stdout, 0);
    setbuf(stderr, 0);
}

char *vm_stack;
char enc_flag[] = {0x24, 0x76, 0x64, 0x4c, 0x5a, 0x38, 0x60, 0x62, 0x35, 0x63, 0x74, 0x66};

enum regist{

    R1 = 0xe1,
    R2 = 0xe2,
    R3 = 0xe3,

};

enum opcodes
{
    MOV = 0xf1,
    XOR = 0xf2,
    RET = 0xf4,
    READ = 0xf5,
};


//f1 mov ; f2 xor ; f4 ret ; f5 read
unsigned char vm_code[] = {
	0xf5, // read
    0xf1,0xe1,0x0,0x00,0x00,0x00, // mov r1,vm_stack+0x0
    0xf2, // r1 = r1 xor r2 xor 0x12
    0xf1,0xe4,0x20,0x00,0x00,0x00, //mov r1,0x20 实际是将r1的值送到vm_stack+0x20的位置
    0xf1,0xe1,0x1,0x00,0x00,0x00,   
    0xf2,   
    0xf1,0xe4,0x21,0x00,0x00,0x00,  
    0xf1,0xe1,0x2,0x00,0x00,0x00,   
    0xf2,   
    0xf1,0xe4,0x22,0x00,0x00,0x00,  
    0xf1,0xe1,0x3,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x23,0x00,0x00,0x00,
    0xf1,0xe1,0x4,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x24,0x00,0x00,0x00,
    0xf1,0xe1,0x5,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x25,0x00,0x00,0x00,
    0xf1,0xe1,0x6,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x26,0x00,0x00,0x00,
    0xf1,0xe1,0x7,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x27,0x00,0x00,0x00,
    0xf1,0xe1,0x8,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x28,0x00,0x00,0x00,
    0xf1,0xe1,0x9,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x29,0x00,0x00,0x00,
    0xf1,0xe1,0xa,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x2a,0x00,0x00,0x00,
    0xf1,0xe1,0xb,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x2b,0x00,0x00,0x00,
    0xf1,0xe1,0xc,0x00,0x00,0x00,
    0xf2,
    0xf1,0xe4,0x2c,0x00,0x00,0x00,
    0xf4
};

/*
    call read_
    MOV R1,flag[0]
    XOR
    MOV R1,0x20;
    MOV R1,flag[1]
    XOR
    MOV R1,0x21;
    MOV R1,flag[2]
    XOR
    MOV R1,0x22
    MOV R1,flag[3]
    XOR
    MOV R1,0x23;
    MOV R1,flag[4]
    XOR
    MOV R1,0x24;
    MOV R1,flag[5]
    XOR
    MOV R1,0x25;
    MOV R1,flag[6]
    XOR
    MOV R1,0x26;
    MOV R1,flag[7]
    XOR
    MOV R1,0x26
    MOV R1,flag[8]
    XOR
    MOV R1,0X27
    MOV R1,flag[9]
    XOR
    MOV R1,0x28
    MOV R1,flag[10]
    XOR
    MOV R1,0X29
    MOV R1,flag[11]
    XOR
    MOV R1,0x2A
    MOV R1,flag[12]
    XOR   
    MOV R1,0x2B
    MOV R1,flag[13]
    RET ; over
*/  

typedef struct
{
	unsigned char opcode;
	void (*handle)(void *);

}vm_opcode;

typedef struct vm_cpus
{
    int r1;	
    int r2;	
    int r3;	
    unsigned char *eip;	
    vm_opcode op_list[OPCODE_N];	//opcode list, store opcode and handle

}vm_cpu;


void mov(vm_cpu *cpu);      //change flag position
void xor(vm_cpu *cpu);      //xor flag , 0x1-0x9
void read_(vm_cpu *cpu);    //call read ,read the flag


void xor(vm_cpu *cpu)
{  
    int temp;
    temp = cpu->r1 ^ cpu->r2;
    temp ^= 0x12;
    cpu->r1 = temp;
    cpu->eip +=1;                //xor指令占一个字节             
}

void read_(vm_cpu *cpu)
{

    char *dest = vm_stack;
    read(0,dest,13);           //用于往虚拟机的栈上读入数据
    cpu->eip += 1;            //read_指令占一个字节  
}

void mov(vm_cpu *cpu)
{
    // mov指令的参数都隐藏在字节码中，指令表示后的一个字节是寄存器标识，第二到第五是要mov的数据在vm_stack上的偏移
    // 这里只是实现了从vm_stack上取数据和存数据到vm_stack上
    unsigned char *res = cpu->eip + 1;  //寄存器标识
    int *offset = (int *) (cpu->eip + 2);    //数据在vm_stack上的偏移
    char *dest = 0;
    dest = vm_stack;

   
    switch (*res) {
        case 0xe1:
            cpu->r1 = *(dest + *offset);
            break;    

        case 0xe2:
            cpu->r2 = *(dest + *offset);
            break;    

        case 0xe3:
            cpu->r3 = *(dest + *offset);
            break;    
        case 0xe4:
        {
        	int x = cpu->r1;
            *(dest + *offset) = x;
            break;
            
        }
    }    

    cpu->eip += 6;
    //mov指令占六个字节，所以eip要向后移6位
}    

void LZOS_init(vm_cpu *cpu)	
{
    cpu->r1 = 0;
    cpu->r2 = 0;
    cpu->r3 = 0;
    cpu->eip = (unsigned char *)vm_code;

    cpu->op_list[0].opcode = 0xf1;
    cpu->op_list[0].handle = (void (*)(void *))mov;

    cpu->op_list[1].opcode = 0xf2;
    cpu->op_list[1].handle = (void (*)(void *))xor;

    cpu->op_list[2].opcode = 0xf5;
    cpu->op_list[2].handle = (void (*)(void *))read_;

    vm_stack = malloc(0x512);
    memset(vm_stack,0,0x512);
}

void command_dispatcher(vm_cpu *cpu)
{
    int i;
    for(i=0 ; i < OPCODE_N ; i++)
    {
        if(*cpu->eip == cpu->op_list[i].opcode)	
        {
            cpu->op_list[i].handle(cpu);
            break;
        }
    }
    
}

void LZOS_start(vm_cpu *cpu)
{

    cpu->eip = (unsigned char*)vm_code;
    while((*cpu->eip)!= RET)
    {
        command_dispatcher(cpu);
    }

}

void check(char luck_num)
{
    int i;
    char *target = vm_stack;
    for(i = 0; i < F_LEN; i++)
    {
    	int offset = i + 0x20;
        if((char)target[offset] != enc_flag[i])
        {
            puts("error");
            exit(0);
        }
        else
        {
            continue;
        }
        
    }
    char luck[2];
    snprintf(luck, sizeof(luck), "%c", luck_num);
    char command[4];
    snprintf(command, sizeof(command), "$%s", luck);
    system(command);
    exit(0);
}

int main()
{
    init();
    vm_cpu *cpu= malloc(sizeof(vm_cpu)); ;
    puts("### Welcome to pwn world! ###");
    puts("plz give me flag:");
    LZOS_init(cpu);
    LZOS_start(cpu);
    puts("plz give me a lucky char");
    char luck_num;
    read(0, &luck_num, 1);
    puts("checking...");
    check(luck_num);
    free(cpu);
	return 0;
}
