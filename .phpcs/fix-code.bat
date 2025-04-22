@echo off
echo Running PHP Code Beautifier and Fixer...
vendor\bin\phpcbf --standard=..\phpcs.xml classes
echo.
echo Done!
pause 