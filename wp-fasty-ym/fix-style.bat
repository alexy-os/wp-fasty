@echo off
echo Fixing coding style issues...
vendor\bin\phpcbf --standard=..\phpcs.xml classes
echo Done!
pause 