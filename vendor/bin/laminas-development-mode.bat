@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../laminas/laminas-development-mode/bin/laminas-development-mode
php "%BIN_TARGET%" %*
