<?php

require("../src/SFU/CAS/Options.php");
require("../src/SFU/CAS/AuthInfo.php");
require("../src/SFU/CAS/CAS.php");

CAS\CAS::requireLogin();

print("<pre> Login successful. Session: \n\n");
print_r($_SESSION);