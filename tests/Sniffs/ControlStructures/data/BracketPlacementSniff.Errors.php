<?php declare(strict_types=1);

do {} // End Do
while (0);
while (0) {} // End While
for ($i = 0; $i < 1; $i++) {} // End For
if (false) {} /* End If */ elseif (false) {} /* End ElseIf */ else {} // End Else
foreach ([1, 2, 3] as $key => $val) {} // End ForEach
switch (false) { default: break; } // End Switch
try {} /* End Try */ catch (\Throwable) {} /* End Catch */ finally {} // End Finally

if (false) {
	strlen('123');
} else {
	strlen('456');
}
