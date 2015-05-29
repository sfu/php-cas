<?php

require("../src/CASOptions.php");
require("../src/CASAuthInfo.php");
require("../src/CAS.php");

SFU\CAS::requireLogin();

print("<pre> Login successful. Session: \n\n");
print_r($_SESSION);