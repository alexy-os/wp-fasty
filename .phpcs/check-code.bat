@echo off
echo Running PHP CodeSniffer...
vendor\bin\phpcs --standard=..\phpcs.xml classes
echo.
echo Done!
pause 