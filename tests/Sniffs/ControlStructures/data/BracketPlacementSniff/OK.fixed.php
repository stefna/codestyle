<?php declare(strict_types=1);

do {}
while (0);

while (0) {}

for ($i = 0; $i < 1; $i++) {
	$i++;
}

if (false) {
}
elseif (false) {
}
else {
}


foreach ([1, 2, 3] as $key => $val) {
}

switch (false) {
	default:
		break;
}

try {
}
catch (\Throwable) {}
finally {}

if (false) { // Valid comment
	strlen('123');
}
else { // Also valid
	strlen('456');
}
