#!/bin/bash

echo $GZCTF_FLAG > /flag
unset FLAG

chmod 777 /flag

apache2-foreground