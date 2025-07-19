gcc -fno-stack-protector -no-pie -o pwn5 pwn5.c
patchelf --set-interpreter ./ld-2.27.so pwn5
patchelf --replace-needed libc.so.6 ./libc-2.27.so pwn5
seccomp-tools dump ./pwn5
checksec pwn5