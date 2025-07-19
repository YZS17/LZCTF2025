#!/bin/bash

echo $GZCTF_FLAG > /flag
unset FLAG

chmod 400 /flag
chmod 4755 /readflag

apache2-foreground