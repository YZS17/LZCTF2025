#!/bin/sh

echo $GZCTF_FLAG > /flag.txt
unset FLAG

chmod 622 /flag.txt

# Start the application in production mode
exec npm start