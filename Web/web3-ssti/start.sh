#!/bin/bash

echo $GZCTF_FLAG > /flag.txt
unset FLAG

chmod 644 /flag.txt

python3 -m flask run --host=0.0.0.0 --port=80